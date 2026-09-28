<?php

namespace App\Console\Commands;

use App\Models\AiUsageLog;
use App\Models\ScrapingNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupCommand extends Command
{
    protected $signature = 'leadhunter:cleanup {--notifications-days=30} {--ai-log-days=90}';

    protected $description = 'Hapus folder profil Chrome sisa scraping, notifikasi lama, dan log pemakaian AI lama';

    public function handle(): int
    {
        $removed = 0;
        $cutoff = time() - 3600; // profil yang masih dipakai proses berjalan tidak ikut dihapus

        $patterns = [
            storage_path('framework/puppeteer/profile_*'),
            // Folder dari versi script lama
            storage_path('framework/profile_*'),
            storage_path('framework/crawl_profile_*'),
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern, GLOB_ONLYDIR) ?: [] as $dir) {
                if (filemtime($dir) < $cutoff && File::deleteDirectory($dir)) {
                    $removed++;
                }
            }
        }

        $notifications = ScrapingNotification::where('created_at', '<', now()->subDays((int) $this->option('notifications-days')))->delete();

        $aiLogs = AiUsageLog::where('created_at', '<', now()->subDays((int) $this->option('ai-log-days')))->delete();

        $this->info("{$removed} folder profil Chrome, {$notifications} notifikasi lama, dan {$aiLogs} log AI lama dihapus.");

        return self::SUCCESS;
    }
}
