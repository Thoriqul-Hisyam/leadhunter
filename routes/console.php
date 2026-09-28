<?php

use App\Jobs\HeartbeatJob;
use App\Services\SystemHealth;
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

// Detak scheduler + cek apakah setiap queue worker masih memproses job
Schedule::call(function () {
    SystemHealth::beatScheduler();

    foreach (SystemHealth::QUEUES as $queue) {
        HeartbeatJob::dispatch($queue);
    }
})->everyMinute()->name('heartbeat')->withoutOverlapping();

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

// Laporan mingguan ke email admin, Senin pagi
Schedule::command('leadhunter:weekly-report')->weeklyOn(1, '07:00')->withoutOverlapping();
