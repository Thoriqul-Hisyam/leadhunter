<?php

namespace App\Jobs;

use App\Exceptions\ScrapeFailedException;
use App\Models\ScrapingNotification;
use App\Services\Scraping\LeadScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ScrapeGoogleMapsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $niche;
    public $location;

    /**
     * Scraping tidak di-retry otomatis: percobaan ulang akan membuka Google Maps dari awal.
     */
    public $tries = 1;

    /**
     * Harus lebih besar dari SCRAPER_TIMEOUT (840 detik) dan lebih kecil dari DB_QUEUE_RETRY_AFTER.
     */
    public $timeout = 900;

    public $failOnTimeout = true;

    public function __construct($niche, $location)
    {
        $this->niche = $niche;
        $this->location = $location;
        $this->onQueue('scraping');
    }

    public function handle(LeadScraperService $scraper): void
    {
        Log::info("Starting background scrape for {$this->niche} in {$this->location}");

        $stats = $scraper->scrape($this->niche, $this->location);

        Log::info('Background scrape completed.', $stats);

        $message = $stats['found'] === 0
            ? "Scraping \"{$this->niche}\" di \"{$this->location}\" selesai, tetapi tidak ada bisnis yang ditemukan. Coba kata kunci yang lebih umum."
            : "Scraping \"{$this->niche}\" di \"{$this->location}\" selesai: {$stats['found']} bisnis ditemukan, {$stats['created']} lead baru, {$stats['updated']} diperbarui.";

        ScrapingNotification::notify('success', 'Scraping Selesai', $message, $this->niche, $this->location);
    }

    public function failed(?Throwable $exception): void
    {
        $saved = $exception instanceof ScrapeFailedException ? ($exception->stats['created'] ?? 0) : 0;
        $reason = $exception ? Str::limit($exception->getMessage(), 200) : 'alasan tidak diketahui';

        Log::error("Scrape failed for {$this->niche} in {$this->location}: {$reason}");

        ScrapingNotification::notify(
            'failed',
            'Scraping Gagal',
            "Scraping \"{$this->niche}\" di \"{$this->location}\" gagal: {$reason}".($saved ? " ({$saved} lead baru sempat tersimpan.)" : ''),
            $this->niche,
            $this->location
        );
    }
}
