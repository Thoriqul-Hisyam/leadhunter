import { launchBrowser, newPage, crawlForContacts, isSocialUrl, delay } from './lib/browser.js';

// Pemakaian: node scripts/scrape-gmaps.js "<niche> <lokasi>" [maxResults]
// Output (stdout):
//   LEAD_ROW:{...}   satu baris per bisnis, dicetak segera agar Laravel bisa menyimpan real-time
//   SUMMARY:{...}    ringkasan di akhir
// Exit code != 0 jika scraping gagal total (browser tidak bisa dibuka, Google Maps tidak bisa diakses, dll).
(async () => {
    const query = process.argv[2];
    const maxResults = Math.max(1, Math.min(parseInt(process.argv[3] || '100', 10) || 100, 200));

    if (!query) {
        console.error('Query kosong.');
        process.exitCode = 1;
        return;
    }

    let session = null;
    let found = 0;

    try {
        session = await launchBrowser();
        const page = await newPage(session.browser);

        await page.goto(`https://www.google.com/maps/search/${encodeURIComponent(query)}?hl=id`, {
            waitUntil: 'domcontentloaded',
            timeout: 60000,
        });

        // Tutup dialog persetujuan cookie jika muncul.
        try {
            const btn = await page.$('button[aria-label="Accept all"], button[aria-label="Terima semua"], form[action*="consent"] button');
            if (btn) { await btn.click(); await delay(2000); }
        } catch (e) {}

        // Google memblokir dengan CAPTCHA ("unusual traffic") atau tertahan di halaman consent.
        const blocked = await page.evaluate(() => {
            const text = (document.body?.innerText || '').toLowerCase();
            if (location.pathname.startsWith('/sorry') || text.includes('unusual traffic') || text.includes('lalu lintas yang tidak biasa') || document.querySelector('iframe[src*="recaptcha"]')) {
                return 'captcha';
            }
            if (location.hostname.startsWith('consent.')) {
                return 'consent';
            }
            return null;
        });

        if (blocked) {
            console.error(blocked === 'captcha' ? 'BLOCKED:captcha' : 'BLOCKED:consent');
            console.log('SUMMARY:' + JSON.stringify({ found: 0, blocked }));
            process.exitCode = 3;
            return;
        }

        try {
            await page.waitForSelector('a[href*="/maps/place/"]', { timeout: 15000 });
        } catch (e) {}
        await delay(2000);

        // Scroll panel hasil untuk memuat lebih banyak tempat.
        const scrolls = Math.ceil(maxResults / 7);
        try {
            await page.evaluate(async (times) => {
                const feed = document.querySelector('div[role="feed"]');
                if (!feed) return;
                for (let i = 0; i < times; i++) {
                    feed.scrollBy(0, 2000);
                    await new Promise((r) => setTimeout(r, 1500));
                    if (document.body.innerText.includes('Anda telah mencapai akhir daftar') || document.body.innerText.includes("You've reached the end of the list")) break;
                }
            }, scrolls);
        } catch (e) {}

        const resultLinks = await page.evaluate((limit) => {
            const seen = new Set();
            const out = [];
            for (const a of document.querySelectorAll('a[href*="/maps/place/"]')) {
                const label = a.getAttribute('aria-label');
                if (label && !seen.has(label)) {
                    seen.add(label);
                    out.push({ name: label, href: a.href });
                }
            }
            return out.slice(0, limit);
        }, maxResults);

        // Satu halaman tempat langsung (query sangat spesifik) tidak punya daftar hasil.
        if (resultLinks.length === 0 && page.url().includes('/maps/place/')) {
            resultLinks.push({ name: '', href: page.url() });
        }

        if (resultLinks.length === 0) {
            console.error('Tidak ada hasil di Google Maps (mungkin diblokir/CAPTCHA atau query tidak ditemukan).');
        }

        for (const link of resultLinks) {
            try {
                await page.goto(link.href, { waitUntil: 'domcontentloaded', timeout: 30000 });
                await delay(2000);

                const detail = await page.evaluate(() => {
                    const text = (sel) => {
                        const el = document.querySelector(sel);
                        return el?.textContent?.trim() || el?.getAttribute('aria-label')?.replace(/^.*?:\s*/, '') || null;
                    };

                    const name = document.querySelector('h1')?.textContent?.trim() || '';
                    const address = text('button[data-item-id="address"]');

                    let phone = text('button[data-item-id^="phone"]');
                    if (phone) phone = phone.replace(/[^0-9+\-\s()]/g, '').trim();

                    const website = document.querySelector('a[data-item-id="authority"]')?.href || null;
                    const category = document.querySelector('button[jsaction*="category"]')?.textContent?.trim() || null;

                    let rating = null;
                    const ratingText = document.querySelector('div.F7nice span[aria-hidden="true"]')?.textContent
                        || document.querySelector('span[role="img"][aria-label*="bintang"], span[role="img"][aria-label*="stars"]')?.getAttribute('aria-label');
                    if (ratingText) {
                        const m = ratingText.match(/\d+[.,]\d/);
                        if (m) rating = parseFloat(m[0].replace(',', '.'));
                    }

                    let reviewsCount = null;
                    const reviewLabel = Array.from(document.querySelectorAll('div.F7nice span[aria-label]'))
                        .map((s) => s.getAttribute('aria-label'))
                        .find((l) => /\d/.test(l) && /(ulasan|review)/i.test(l));
                    if (reviewLabel) reviewsCount = parseInt(reviewLabel.replace(/\D/g, ''), 10) || null;

                    const emailMatch = document.body.innerText.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);

                    return { name, address, phone, website, category, rating, reviews_count: reviewsCount, email: emailMatch ? emailMatch[0] : null };
                });

                if (!detail.name) continue;

                detail.google_maps_url = page.url().split('?')[0];

                // Cari email/telepon yang belum ada langsung dari website bisnisnya.
                if ((!detail.email || !detail.phone) && detail.website && !isSocialUrl(detail.website)) {
                    try {
                        const contacts = await crawlForContacts(session.browser, detail.website, { timeout: 12000, maxSubpages: 1 });
                        detail.email = detail.email || contacts.email;
                        detail.phone = detail.phone || contacts.phone;
                    } catch (e) {
                        // website tidak bisa dibuka: lanjut tanpa kontak tambahan
                    }
                }

                found++;
                console.log('LEAD_ROW:' + JSON.stringify(detail));
            } catch (e) {
                // lewati tempat ini
            }
        }

        console.log('SUMMARY:' + JSON.stringify({ links: resultLinks.length, found }));
    } catch (e) {
        console.error(e.message);
        console.log('SUMMARY:' + JSON.stringify({ found, error: e.message }));
        process.exitCode = 1;
    } finally {
        if (session) await session.close();
    }
})();
