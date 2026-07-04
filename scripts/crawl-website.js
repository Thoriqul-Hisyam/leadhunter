import puppeteer from 'puppeteer';

(async () => {
    const url = process.argv[2];
    if (!url) {
        console.log(JSON.stringify({ success: false, error: 'No URL provided' }));
        process.exit(0);
    }

    let browser;
    try {
        const path = await import('path');
        const { fileURLToPath } = await import('url');
        const __dirname = path.dirname(fileURLToPath(import.meta.url));

        const uniqueProfile = path.join(__dirname, '../storage/framework/crawl_profile_' + Date.now() + '_' + Math.floor(Math.random() * 10000));
        
        let launchOptions = {
            headless: 'new',
            userDataDir: uniqueProfile,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu', '--disable-dev-shm-usage'],
        };

        const fs = await import('fs');
        const defaultChromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
        if (fs.existsSync(defaultChromePath)) {
            launchOptions.executablePath = defaultChromePath;
        }

        browser = await puppeteer.launch(launchOptions);
        const page = await browser.newPage();
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        await page.setViewport({ width: 1280, height: 900 });

        // Intercept requests to save bandwidth & speed up
        await page.setRequestInterception(true);
        page.on('request', (req) => {
            if (['image', 'stylesheet', 'font', 'media'].includes(req.resourceType())) {
                req.abort();
            } else {
                req.continue();
            }
        });

        // Set timeout
        page.setDefaultNavigationTimeout(20000);

        let email = null;
        let phone = null;
        const crawledUrls = new Set();

        async function extractFromPage(p) {
            return await p.evaluate(() => {
                // Remove scripts, styles, svg to avoid false positives
                const clone = document.body.cloneNode(true);
                clone.querySelectorAll('script, style, svg, link, iframe').forEach(el => el.remove());
                const text = clone.innerText || '';
                const html = clone.innerHTML || '';

                // Extract emails
                const emailMatches = html.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g) || [];
                const cleanEmails = Array.from(new Set(emailMatches)).filter(e => {
                    const ext = e.split('.').pop().toLowerCase();
                    return !['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'].includes(ext);
                });

                // Extract phones from tel links
                const telLinks = Array.from(document.querySelectorAll('a[href^="tel:"]'))
                    .map(a => a.getAttribute('href').replace('tel:', '').trim())
                    .filter(t => t.length > 5);

                // Extract phones from text (regex targeting Indonesian / general formats)
                // Looks for numbers starting with +62, 62, 08, 021, etc.
                const textMatches = text.match(/(?:\+62|62|0)(?:\s|-|\.)*(?:\d(?:\s|-|\.)*){8,13}/g) || [];
                const cleanPhones = textMatches.map(p => p.replace(/[^0-9+]/g, '').trim())
                    .filter(p => {
                        return p.length >= 9 && p.length <= 15 && (p.startsWith('0') || p.startsWith('+62') || p.startsWith('62'));
                    });

                return {
                    emails: cleanEmails,
                    phones: Array.from(new Set([...telLinks, ...cleanPhones])),
                    contactLinks: Array.from(document.querySelectorAll('a'))
                        .map(a => ({ href: a.href, text: a.innerText.toLowerCase() }))
                        .filter(l => l.href && (l.text.includes('contact') || l.text.includes('hubungi') || l.text.includes('about') || l.text.includes('tentang') || l.text.includes('kami') || l.href.includes('contact') || l.href.includes('hubungi') || l.href.includes('about')))
                        .map(l => l.href)
                };
            });
        }

        // Navigate homepage
        let currentUrl = url.startsWith('http') ? url : `https://${url}`;
        crawledUrls.add(currentUrl);
        await page.goto(currentUrl, { waitUntil: 'domcontentloaded' });
        
        let data = await extractFromPage(page);
        if (data.emails.length > 0) email = data.emails[0];
        if (data.phones.length > 0) phone = data.phones[0];

        // If missing email or phone, check contact links
        if ((!email || !phone) && data.contactLinks.length > 0) {
            const uniqueLinks = Array.from(new Set(data.contactLinks)).slice(0, 3);
            for (const link of uniqueLinks) {
                if (crawledUrls.has(link)) continue;
                crawledUrls.add(link);
                try {
                    await page.goto(link, { waitUntil: 'domcontentloaded', timeout: 15000 });
                    let contactData = await extractFromPage(page);
                    if (!email && contactData.emails.length > 0) email = contactData.emails[0];
                    if (!phone && contactData.phones.length > 0) phone = contactData.phones[0];
                    if (email && phone) break;
                } catch (e) {
                    // skip subpage error
                }
            }
        }

        console.log("RESULT_JSON:" + JSON.stringify({ success: true, email, phone }));

    } catch (e) {
        console.log("RESULT_JSON:" + JSON.stringify({ success: false, error: e.message }));
    } finally {
        if (browser) {
            await browser.close();
            try {
                const fs = await import('fs');
                if (typeof uniqueProfile !== 'undefined') {
                    fs.rmSync(uniqueProfile, { recursive: true, force: true });
                }
            } catch (e) {}
        }
    }
})();
