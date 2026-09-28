<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ai' => [
        'base_url' => rtrim(env('AI_BASE_URL') ?: 'https://api.groq.com/openai/v1', '/'),
        'key' => env('AI_API_KEY') ?: env('GROQ_API_KEY'),
        'model' => env('AI_MODEL') ?: 'llama-3.1-8b-instant',
        'timeout' => (int) env('AI_TIMEOUT', 120),
        'retries' => (int) env('AI_RETRIES', 2),
        // Model cepat untuk klasifikasi balasan & smart matching (kosong = model utama)
        'fast_model' => env('AI_FAST_MODEL'),
        // Provider cadangan, dipakai otomatis saat provider utama gagal/timeout
        'backup' => [
            'base_url' => env('AI_BACKUP_BASE_URL') ? rtrim(env('AI_BACKUP_BASE_URL'), '/') : null,
            'key' => env('AI_BACKUP_API_KEY'),
            'model' => env('AI_BACKUP_MODEL'),
        ],
    ],

    // Audit website lead (opsional; tanpa key tetap bisa dengan kuota kecil)
    'pagespeed' => [
        'key' => env('PAGESPEED_API_KEY'),
    ],

    'google_places' => [
        'key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    'apify' => [
        'token' => env('APIFY_TOKEN'),
        'actor' => env('APIFY_ACTOR', 'compass~crawler-google-places'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
