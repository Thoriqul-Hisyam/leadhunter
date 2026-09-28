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
        'daily_limit' => (int) env('OUTREACH_DAILY_LIMIT', 80),
        'min_gap_seconds' => (int) env('OUTREACH_MIN_GAP_SECONDS', 90),
        'max_gap_seconds' => (int) env('OUTREACH_MAX_GAP_SECONDS', 240),
        'max_attempts' => (int) env('OUTREACH_MAX_ATTEMPTS', 3),

        // Jendela kirim otomatis (waktu APP_TIMEZONE). Di luar jendela, antrean menunggu.
        'window_start' => env('OUTREACH_WINDOW_START', '08:00'),
        'window_end' => env('OUTREACH_WINDOW_END', '16:00'),
        'weekdays_only' => (bool) env('OUTREACH_WEEKDAYS_ONLY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway
    |--------------------------------------------------------------------------
    |
    | driver: manual (click-to-chat wa.me), fonnte, wablas. Diatur dari halaman
    | Pengaturan; nilai di sini hanya default. Batasnya sengaja lebih ketat dari email
    | karena gateway tidak resmi berisiko membuat nomor pengirim diblokir.
    |
    */

    'whatsapp' => [
        'driver' => env('WA_DRIVER', 'manual'),
        'token' => env('WA_TOKEN'),
        'base_url' => env('WA_BASE_URL'),
        'hourly_limit' => (int) env('WA_HOURLY_LIMIT', 10),
        'daily_limit' => (int) env('WA_DAILY_LIMIT', 50),
        'min_gap_seconds' => (int) env('WA_MIN_GAP_SECONDS', 180),
        'max_gap_seconds' => (int) env('WA_MAX_GAP_SECONDS', 480),
        'allow_landline' => (bool) env('WA_ALLOW_LANDLINE', false),
        'webhook_token' => env('WA_WEBHOOK_TOKEN'),
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
