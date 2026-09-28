<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReportMail;
use App\Models\Setting;
use App\Models\User;
use App\Services\WeeklyReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWeeklyReport extends Command
{
    protected $signature = 'leadhunter:weekly-report {--to= : Kirim ke alamat ini saja (untuk uji coba)} {--force : Kirim walau dinonaktifkan di Pengaturan}';

    protected $description = 'Kirim ringkasan 7 hari terakhir ke email admin';

    public function handle(WeeklyReport $report): int
    {
        if (! $this->option('force') && ! $this->option('to') && Setting::get('weekly_report_enabled') !== '1') {
            $this->line('Laporan mingguan nonaktif (aktifkan di halaman Pengaturan).');

            return self::SUCCESS;
        }

        $recipients = $this->option('to')
            ? [$this->option('to')]
            : User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->pluck('email')->filter()->all();

        if (! $recipients) {
            $this->warn('Tidak ada admin dengan alamat email.');

            return self::SUCCESS;
        }

        try {
            Mail::to($recipients)->send(new WeeklyReportMail($report->build()));
        } catch (Throwable $e) {
            $this->error('Gagal mengirim laporan: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $this->info('Laporan mingguan dikirim ke '.count($recipients).' penerima.');

        return self::SUCCESS;
    }
}
