<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
|
| Lokal: `php artisan schedule:work` (sudah termasuk di `composer run dev`).
| Server: cron `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`.
|
*/

// Kirim email antrean yang jatuh tempo (batas per jam + jeda acak)
Schedule::command('outreach:send-due')->everyMinute()->withoutOverlapping();

// Jadwalkan ulang email yang gagal karena error sementara
Schedule::command('outreach:retry-failed')->everyFifteenMinutes()->withoutOverlapping();

// Follow-up untuk lead yang belum membalas (aktif jika diaktifkan di Pengaturan)
Schedule::command('outreach:followups')->hourly()->withoutOverlapping();

// Deteksi reply via IMAP (aktif jika IMAP_ENABLED=true)
Schedule::command('outreach:check-replies')->everyTenMinutes()->withoutOverlapping();

// Bersihkan profil Chrome sisa scraping & notifikasi lama
Schedule::command('leadhunter:cleanup')->daily();
