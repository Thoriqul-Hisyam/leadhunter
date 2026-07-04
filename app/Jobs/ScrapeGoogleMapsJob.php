<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GoogleMapsScraperService;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class ScrapeGoogleMapsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $niche;
    public $location;

    /**
     * Set timeout job to 15 minutes to allow for large scraping tasks.
     */
    public $timeout = 900;

    public function __construct($niche, $location)
    {
        $this->niche = $niche;
        $this->location = $location;
    }

    public function handle(GoogleMapsScraperService $scraper): void
    {
        Log::info("Starting background scrape for {$this->niche} in {$this->location}");
        
        $results = $scraper->scrape($this->niche, $this->location);
        
        $count = 0;
        foreach ($results as $result) {
            Lead::firstOrCreate(
                ['business_name' => $result['business_name'], 'city' => $result['city']],
                $result
            );
            $count++;
        }
        
        Log::info("Background scrape completed. Inserted {$count} leads.");

        // Create 'success' status notification in database
        \App\Models\ScrapingNotification::create([
            'niche' => $this->niche,
            'location' => $this->location,
            'type' => 'success',
            'title' => 'Scraping Selesai',
            'message' => "Scraping leads untuk niche \"{$this->niche}\" di kota \"{$this->location}\" selesai! Daftar leads diperbarui.",
            'is_read' => false,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Create 'failed' status notification in database
        \App\Models\ScrapingNotification::create([
            'niche' => $this->niche,
            'location' => $this->location,
            'type' => 'failed',
            'title' => 'Scraping Gagal',
            'message' => "Scraping leads untuk niche \"{$this->niche}\" di kota \"{$this->location}\" gagal.",
            'is_read' => false,
        ]);
    }
}
