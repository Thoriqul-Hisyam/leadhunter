import puppeteer from 'puppeteer';
import fs from 'fs';
import os from 'os';
import path from 'path';

const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

const CHROME_CANDIDATES = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/usr/bin/google-chrome',
    '/usr/bin/google-chrome-stable',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
];

export function resolveChromePath() {
    if (process.env.CHROME_PATH && fs.existsSync(process.env.CHROME_PATH)) {
        return process.env.CHROME_PATH;
    }
    // undefined = pakai Chrome bawaan Puppeteer (jika sudah di-download).
    return CHROME_CANDIDATES.find((p) => fs.existsSync(p));
}

/**
 * Buka Chrome headless dengan profil sementara yang unik per proses.
 * Selalu panggil close() di blok finally agar folder profil ikut terhapus.
 */
export async function launchBrowser() {
    const baseDir = process.env.PUPPETEER_PROFILE_DIR || path.join(os.tmpdir(), 'leadhunter-puppeteer');
    fs.mkdirSync(baseDir, { recursive: true });
    const profileDir = fs.mkdtempSync(path.join(baseDir, 'profile_'));

    const browser = await puppeteer.launch({
        headless: true,
        userDataDir: profileDir,
        executablePath: resolveChromePath(),
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu', '--disable-dev-shm-usage'],
    });

    const close = async () => {
        try { await browser.close(); } catch (e) {}
        try { fs.rmSync(profileDir, { recursive: true, force: true }); } catch (e) {}
    };

    return { browser, close };
}

export async function newPage(browser, { blockAssets = false } = {}) {
    const page = await browser.newPage();
    await page.setUserAgent(USER_AGENT);
    await page.setViewport({ width: 1280, height: 900 });

    if (blockAssets) {
        await page.setRequestInterception(true);
        page.on('request', (req) => {
            if (['image', 'stylesheet', 'font', 'media'].includes(req.resourceType())) {
                req.abort();
            } else {
                req.continue();
            }
        });
    }

    return page;
}

/**
 * Ambil email, nomor telepon, dan link halaman kontak dari halaman yang sedang terbuka.
 */
export async function extractContacts(page) {
    return page.evaluate(() => {
        const clone = document.body.cloneNode(true);
        clone.querySelectorAll('script, style, svg, link, iframe').forEach((el) => el.remove());
        const text = clone.innerText || '';
        const html = clone.innerHTML || '';

        const mailtoEmails = Array.from(document.querySelectorAll('a[href^="mailto:"]'))
            .map((a) => a.getAttribute('href').replace(/^mailto:/i, '').split('?')[0].trim());
        const htmlEmails = html.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g) || [];
        const emails = Array.from(new Set([...mailtoEmails, ...htmlEmails]))
            .map((e) => e.toLowerCase())
            .filter((e) => {
                const ext = e.split('.').pop();
                return !['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'].includes(ext)
                    && !/(example\.com|sentry|wixpress|domain\.com)/.test(e);
            });

        const telLinks = Array.from(document.querySelectorAll('a[href^="tel:"]'))
            .map((a) => a.getAttribute('href').replace('tel:', '').trim())
            .filter((t) => t.length > 5);

        const waLinks = Array.from(document.querySelectorAll('a[href*="wa.me/"], a[href*="api.whatsapp.com"]'))
            .map((a) => (a.getAttribute('href').match(/(?:wa\.me\/|phone=)(\+?\d{9,15})/) || [])[1])
            .filter(Boolean);

        const textMatches = text.match(/(?:\+62|62|0)(?:\s|-|\.)*(?:\d(?:\s|-|\.)*){8,13}/g) || [];
        const textPhones = textMatches
            .map((p) => p.replace(/[^0-9+]/g, '').trim())
            .filter((p) => p.length >= 9 && p.length <= 15 && (p.startsWith('0') || p.startsWith('+62') || p.startsWith('62')));

        const keywords = ['contact', 'kontak', 'hubungi', 'about', 'tentang'];
        const contactLinks = Array.from(document.querySelectorAll('a'))
            .map((a) => ({ href: a.href, text: (a.innerText || '').toLowerCase() }))
            .filter((l) => l.href && l.href.startsWith('http') && keywords.some((k) => l.text.includes(k) || l.href.toLowerCase().includes(k)))
            .map((l) => l.href);

        return {
            emails,
            phones: Array.from(new Set([...waLinks, ...telLinks, ...textPhones])),
            contactLinks: Array.from(new Set(contactLinks)),
        };
    });
}

const SOCIAL_DOMAINS = ['instagram.com', 'facebook.com', 'linktr.ee', 'wa.me', 'twitter.com', 'x.com', 'tiktok.com', 'bit.ly', 'youtube.com', 'google.com'];

export function isSocialUrl(url) {
    return SOCIAL_DOMAINS.some((d) => url.includes(d));
}

/**
 * Buka website bisnis (homepage + maksimal beberapa halaman kontak) untuk mencari email & telepon.
 */
export async function crawlForContacts(browser, url, { timeout = 15000, maxSubpages = 2 } = {}) {
    const startUrl = url.startsWith('http') ? url : `https://${url}`;
    const startHost = new URL(startUrl).hostname.replace(/^www\./, '');
    let email = null;
    let phone = null;
    let page = null;

    try {
        page = await newPage(browser, { blockAssets: true });
        await page.goto(startUrl, { waitUntil: 'domcontentloaded', timeout });

        const home = await extractContacts(page);
        email = home.emails[0] || null;
        phone = home.phones[0] || null;

        // Hanya ikuti link di domain yang sama.
        const subpages = home.contactLinks
            .filter((link) => {
                try { return new URL(link).hostname.replace(/^www\./, '') === startHost && link !== startUrl; } catch (e) { return false; }
            })
            .slice(0, maxSubpages);

        for (const link of subpages) {
            if (email && phone) break;
            try {
                await page.goto(link, { waitUntil: 'domcontentloaded', timeout });
                const data = await extractContacts(page);
                email = email || data.emails[0] || null;
                phone = phone || data.phones[0] || null;
            } catch (e) {
                // abaikan error di sub-halaman
            }
        }
    } finally {
        if (page) {
            try { await page.close(); } catch (e) {}
        }
    }

    return { email, phone };
}

export function delay(ms) {
    return new Promise((r) => setTimeout(r, ms));
}
