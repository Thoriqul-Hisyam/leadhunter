<?php

namespace App\Jobs;

use App\Exceptions\CrawlException;
use App\Models\Lead;
use App\Services\WebsiteAuditService;
use App\Services\WebsiteCrawlerService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Lengkapi satu lead di background: cari email/telepon dari website (crawl)
 * atau audit website (PageSpeed). Dijalankan massal lewat Bus::batch dari tabel Leads.
 */
class EnrichLeadJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ACTIONS = ['crawl', 'audit'];

    public $tries = 1;

    public $timeout = 180;

    public function __construct(public int $leadId, public string $action)
    {
        // Crawl membuka Chrome (berat) → antrean scraping; audit hanya panggilan HTTP → default.
        $this->onQueue($action === 'crawl' ? 'scraping' : 'default');
    }

    public function handle(WebsiteCrawlerService $crawler, WebsiteAuditService $auditor): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $lead = Lead::find($this->leadId);

        if (! $lead || ! $lead->website) {
            return;
        }

        try {
            if ($this->action === 'crawl') {
                $contacts = $crawler->crawl($lead->website);
                $lead->update(array_filter([
                    'email' => $lead->email ?: $contacts['email'],
                    'phone' => $lead->phone ?: $contacts['phone'],
                ]));
            } else {
                $audit = $auditor->audit($lead->website);
                $lead->update([
                    'website_score' => $audit['score'],
                    'website_https' => $audit['https'],
                    'website_audited_at' => now(),
                ]);
            }
        } catch (CrawlException) {
            // Website mati/tidak bisa diaudit: bukan kegagalan job, lanjut ke lead berikutnya.
        }
    }
}
