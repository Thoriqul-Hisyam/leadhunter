import { launchBrowser, crawlForContacts } from './lib/browser.js';

// Pemakaian: node scripts/crawl-website.js <url>
// Output: satu baris "RESULT_JSON:{...}".
(async () => {
    const url = process.argv[2];

    if (!url || !/^https?:\/\//i.test(url)) {
        console.log('RESULT_JSON:' + JSON.stringify({ success: false, error: 'URL harus diawali http:// atau https://' }));
        process.exitCode = 1;
        return;
    }

    let session = null;
    try {
        session = await launchBrowser();
        const { email, phone } = await crawlForContacts(session.browser, url, { timeout: 20000, maxSubpages: 3 });
        console.log('RESULT_JSON:' + JSON.stringify({ success: true, email, phone }));
    } catch (e) {
        console.log('RESULT_JSON:' + JSON.stringify({ success: false, error: e.message }));
        process.exitCode = 1;
    } finally {
        if (session) await session.close();
    }
})();
