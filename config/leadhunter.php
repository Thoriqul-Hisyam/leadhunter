<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lead Scraper
    |--------------------------------------------------------------------------
    |
    | driver: puppeteer (default, gratis, DOM scraping Google Maps),
    |         google_places (Google Places API, butuh GOOGLE_PLACES_API_KEY),
    |         apify (Apify actor, butuh APIFY_TOKEN).
    |
    */

    'scraper' => [
        'driver' => env('SCRAPER_DRIVER', 'puppeteer'),
        'max_results' => (int) env('SCRAPER_MAX_RESULTS', 100),
        'timeout' => (int) env('SCRAPER_TIMEOUT', 840),
    ],

    'crawler' => [
        'timeout' => (int) env('CRAWLER_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Node & Chrome (dipakai scraper Puppeteer dan crawler website)
    |--------------------------------------------------------------------------
    */

    'node_binary' => env('NODE_BINARY') ?: 'node',
    'chrome_path' => env('CHROME_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Pengiriman Outreach
    |--------------------------------------------------------------------------
    |
    | Batas aman Gmail: sekitar 20 email/jam untuk cold outreach. Jeda acak
    | di antara email membuat pola pengiriman tidak terlihat seperti bot.
    |
    */

    'sending' => [
        'hourly_limit' => (int) env('OUTREACH_HOURLY_LIMIT', 20),
        'min_gap_seconds' => (int) env('OUTREACH_MIN_GAP_SECONDS', 90),
        'max_gap_seconds' => (int) env('OUTREACH_MAX_GAP_SECONDS', 240),
        'max_attempts' => (int) env('OUTREACH_MAX_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deteksi Reply (IMAP)
    |--------------------------------------------------------------------------
    |
    | Untuk Gmail: aktifkan IMAP di pengaturan Gmail, lalu pakai App Password
    | yang sama dengan SMTP.
    |
    */

    'imap' => [
        'enabled' => (bool) env('IMAP_ENABLED', false),
        'host' => env('IMAP_HOST', 'imap.gmail.com'),
        'port' => (int) env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'username' => env('IMAP_USERNAME') ?: env('MAIL_USERNAME'),
        'password' => env('IMAP_PASSWORD') ?: env('MAIL_PASSWORD'),
        'folder' => env('IMAP_FOLDER', 'INBOX'),
        'lookback_days' => (int) env('IMAP_LOOKBACK_DAYS', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password admin default untuk db:seed (ganti sebelum deploy)
    |--------------------------------------------------------------------------
    */

    'admin_password' => env('ADMIN_PASSWORD') ?: 'admin123',

];
