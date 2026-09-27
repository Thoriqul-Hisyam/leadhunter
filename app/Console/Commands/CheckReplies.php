<?php

namespace App\Console\Commands;

use App\Models\ScrapingNotification;
use App\Services\Replies\ImapMailbox;
use App\Services\Replies\ReplyDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CheckReplies extends Command
{
    protected $signature = 'outreach:check-replies {--days= : Periksa email N hari terakhir (default: sejak pengecekan terakhir)}';

    protected $description = 'Baca inbox via IMAP dan tandai outreach yang dibalas sebagai "replied"';

    public function handle(ImapMailbox $mailbox, ReplyDetector $detector): int
    {
        if (! config('leadhunter.imap.enabled')) {
            $this->line('IMAP nonaktif. Set IMAP_ENABLED=true di .env untuk mengaktifkan deteksi reply otomatis.');

            return self::SUCCESS;
        }

        $lastCheck = Cache::get('imap_last_checked_at');
        $since = $this->option('days')
            ? now()->subDays((int) $this->option('days'))
            : ($lastCheck ? now()->parse($lastCheck)->subDay() : now()->subDays((int) config('leadhunter.imap.lookback_days', 14)));

        try {
            $emails = $mailbox->fetchSince($since, fn (array $email) => $detector->match($email) !== null);
        } catch (Throwable $e) {
            $this->error('Gagal membaca inbox: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $matched = $detector->process($emails);
        Cache::forever('imap_last_checked_at', now()->toIso8601String());

        $this->info(count($emails)." email diperiksa, {$matched} outreach ditandai replied, {$detector->unsubscribed} minta berhenti.");

        if ($matched > 0) {
            ScrapingNotification::notify('success', 'Balasan Baru', "{$matched} lead membalas outreach Anda. Status otomatis diubah menjadi Replied.");
        }

        if ($detector->unsubscribed > 0) {
            ScrapingNotification::notify('success', 'Permintaan Berhenti', "{$detector->unsubscribed} penerima membalas minta berhenti dihubungi dan sudah dimasukkan ke blacklist.");
        }

        return self::SUCCESS;
    }
}
