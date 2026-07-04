import puppeteer from 'puppeteer';

(async () => {
    const query = process.argv[2];
    if (!query) { console.log('[]'); process.exit(0); }

    let browser;
    try {
        const path = await import('path');
        const { fileURLToPath } = await import('url');
        const __dirname = path.dirname(fileURLToPath(import.meta.url));

        // Use a 100% unique profile folder for every request to avoid collisions
        const uniqueProfile = path.join(__dirname, '../storage/framework/profile_' + Date.now() + '_' + Math.floor(Math.random() * 10000));
        
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

        // Go to Google Maps search
        await page.goto(`https://www.google.com/maps/search/${encodeURIComponent(query)}`, {
            waitUntil: 'domcontentloaded', timeout: 60000
        });

        // Handle cookie consent if appears
        try {
            const btn = await page.$('button[aria-label="Accept all"], button[aria-label="Terima semua"]');
            if (btn) { await btn.click(); await delay(2000); }
        } catch(e) {}

        // Wait for results to show up
        try {
            await page.waitForSelector('a[href*="/maps/place/"]', { timeout: 15000 });
        } catch(e) {}
        await delay(2000);

        // Scroll the results panel to load more items
        try {
            await page.evaluate(async () => {
                const scrollableDiv = document.querySelector('div[role="feed"]');
                if (scrollableDiv) {
                    for (let i = 0; i < 15; i++) { // Scroll 15 kali untuk memuat ~100 data
                        scrollableDiv.scrollBy(0, 2000);
                        await new Promise(r => setTimeout(r, 1500));
                    }
                }
            });
        } catch(e) {}

        // Collect result links from the list
        const resultLinks = await page.evaluate(() => {
            const links = document.querySelectorAll('a[href*="/maps/place/"]');
            const seen = new Set();
            const out = [];
            for (const a of links) {
                const label = a.getAttribute('aria-label');
                const href = a.href;
                if (label && !seen.has(label)) {
                    seen.add(label);
                    out.push({ name: label, href });
                }
            }
            return out.slice(0, 100); // Maksimal 100 leads sekali scrape
        });

        const results = [];

        // Click each result to get details
        for (let i = 0; i < resultLinks.length; i++) {
            try {
                await page.goto(resultLinks[i].href, { waitUntil: 'domcontentloaded', timeout: 30000 });
                await delay(2000);

                const detail = await page.evaluate(() => {
                    const getText = (sel) => {
                        const el = document.querySelector(sel);
                        return el?.textContent?.trim() || el?.getAttribute('aria-label')?.replace(/^.*?:\s*/, '') || null;
                    };
                    const getHref = (sel) => {
                        const el = document.querySelector(sel);
                        return el?.href || null;
                    };

                    // Business name from h1
                    const name = document.querySelector('h1')?.textContent?.trim() || '';

                    // Address
                    const address = getText('button[data-item-id="address"]');

                    // Phone
                    let phone = getText('button[data-item-id^="phone"]');
                    if (phone) phone = phone.replace(/[^0-9+\-\s()]/g, '').trim();

                    // Website
                    const website = getHref('a[data-item-id="authority"]');

                    // Category
                    const category = document.querySelector('button[jsaction*="category"]')?.textContent?.trim() || '';

                    // Try to find an email address in the visible text
                    const bodyText = document.body.innerText;
                    const emailMatch = bodyText.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
                    const email = emailMatch ? emailMatch[0] : null;

                    return { name, address, phone, website, category, email };
                });

                if (detail.name) {
                    let email = detail.email;
                    let phone = detail.phone;
                    
                    // If email or phone is missing, and we have a valid website, crawl it!
                    if ((!email || !phone) && detail.website) {
                        const isSocial = ['instagram.com', 'facebook.com', 'linktr.ee', 'wa.me', 'twitter.com', 'tiktok.com', 'bit.ly', 'youtube.com', 'google.com'].some(d => detail.website.includes(d));
                        if (!isSocial) {
                            let webPage = null;
                            try {
                                webPage = await browser.newPage();
                                await webPage.setRequestInterception(true);
                                webPage.on('request', (req) => {
                                    if (['image', 'stylesheet', 'font', 'media'].includes(req.resourceType())) {
                                        req.abort();
                                    } else {
                                        req.continue();
                                    }
                                });
                                
                                let currentUrl = detail.website.startsWith('http') ? detail.website : `https://${detail.website}`;
                                await webPage.goto(currentUrl, { waitUntil: 'domcontentloaded', timeout: 12000 });
                                
                                const extractData = async (wp) => {
                                    return await wp.evaluate(() => {
                                        const clone = document.body.cloneNode(true);
                                        clone.querySelectorAll('script, style, svg, link, iframe').forEach(el => el.remove());
                                        const text = clone.innerText || '';
                                        const html = clone.innerHTML || '';
                                        
                                        const emailMatches = html.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g) || [];
                                        const cleanEmails = Array.from(new Set(emailMatches)).filter(e => {
                                            const ext = e.split('.').pop().toLowerCase();
                                            return !['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'].includes(ext);
                                        });
                                        
                                        const telLinks = Array.from(document.querySelectorAll('a[href^="tel:"]'))
                                            .map(a => a.getAttribute('href').replace('tel:', '').trim())
                                            .filter(t => t.length > 5);
                                            
                                        const textMatches = text.match(/(?:\+62|62|0)(?:\s|-|\.)*(?:\d(?:\s|-|\.)*){8,13}/g) || [];
                                        const cleanPhones = textMatches.map(p => p.replace(/[^0-9+]/g, '').trim())
                                            .filter(p => p.length >= 9 && p.length <= 15 && (p.startsWith('0') || p.startsWith('+62') || p.startsWith('62')));
                                            
                                        return {
                                            emails: cleanEmails,
                                            phones: Array.from(new Set([...telLinks, ...cleanPhones])),
                                            contactLinks: Array.from(document.querySelectorAll('a'))
                                                .map(a => ({ href: a.href, text: a.innerText.toLowerCase() }))
                                                .filter(l => l.href && (l.text.includes('contact') || l.text.includes('hubungi') || l.text.includes('about') || l.text.includes('tentang') || l.href.includes('contact') || l.href.includes('hubungi') || l.href.includes('about')))
                                                .map(l => l.href)
                                        };
                                    });
                                };
                                
                                let homepageData = await extractData(webPage);
                                if (!email && homepageData.emails.length > 0) email = homepageData.emails[0];
                                if (!phone && homepageData.phones.length > 0) phone = homepageData.phones[0];
                                
                                // If still missing either, check first contact page
                                if ((!email || !phone) && homepageData.contactLinks.length > 0) {
                                    const firstContactLink = Array.from(new Set(homepageData.contactLinks))[0];
                                    if (firstContactLink && firstContactLink !== currentUrl) {
                                        try {
                                            await webPage.goto(firstContactLink, { waitUntil: 'domcontentloaded', timeout: 10000 });
                                            let contactPageData = await extractData(webPage);
                                            if (!email && contactPageData.emails.length > 0) email = contactPageData.emails[0];
                                            if (!phone && contactPageData.phones.length > 0) phone = contactPageData.phones[0];
                                        } catch (contactErr) {}
                                    }
                                }
                            } catch (webErr) {
                                // ignore website crawl errors
                            } finally {
                                if (webPage) {
                                    await webPage.close();
                                }
                            }
                        }
                    }
                    
                    detail.email = email;
                    detail.phone = phone;
                    results.push(detail);
                    
                    // Print lead immediately to stdout for real-time Laravel storage
                    console.log("LEAD_ROW:" + JSON.stringify(detail));
                }
            } catch(e) {
                // skip this result
            }
        }

        console.log(JSON.stringify(results));
    } catch(e) {
        console.error(e.message);
        console.log('[]');
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

function delay(ms) { return new Promise(r => setTimeout(r, ms)); }
