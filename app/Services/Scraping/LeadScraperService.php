<?php

namespace App\Services\Scraping;

use App\Exceptions\CrawlException;
use App\Exceptions\ScrapeFailedException;
use App\Helpers\Url;
use App\Models\Lead;
use App\Services\WebsiteCrawlerService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class LeadScraperService
{
    public function __construct(protected WebsiteCrawlerService $crawler)
    {
    }

    public function source(?string $driver = null): LeadSource
    {
        return match ($driver ?? config('leadhunter.scraper.driver', 'puppeteer')) {
            'puppeteer' => app(PuppeteerGoogleMapsSource::class),
            'google_places' => app(GooglePlacesSource::class),
            'apify' => app(ApifySource::class),
            default => throw new InvalidArgumentException('SCRAPER_DRIVER tidak dikenal: '.config('leadhunter.scraper.driver')),
        };
    }

    /**
     * Cari bisnis lalu simpan setiap hasil ke tabel leads (real-time, satu per satu).
     *
     * @return array{found: int, created: int, updated: int, unchanged: int, skipped: int}
     *
     * @throws ScrapeFailedException
     */
    public function scrape(string $niche, string $location): array
    {
        $source = $this->source();
        $stats = ['found' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        $needsEmail = [];

        try {
            $source->search(
                trim($niche).' '.trim($location),
                (int) config('leadhunter.scraper.max_results', 100),
                function (array $place) use ($niche, $location, $source, &$stats, &$needsEmail) {
                    $stats['found']++;
                    [$outcome, $lead] = $this->saveLead($place, $niche, $location, $source->name());
                    $stats[$outcome]++;

                    if ($lead && $lead->website && ! $lead->email) {
                        $needsEmail[] = $lead;
                    }
                }
            );
        } catch (ScrapeFailedException $e) {
            throw $e->withStats($stats);
        }

        // Script Puppeteer sudah merayapi website sendiri; sumber API belum.
        if (! $source instanceof PuppeteerGoogleMapsSource) {
            $this->enrichEmails($needsEmail);
        }

        return $stats;
    }

    /**
     * @return array{0: string, 1: ?Lead} outcome: created|updated|unchanged|skipped
     */
    public function saveLead(array $place, string $niche, string $location, string $source): array
    {
        $name = trim(preg_replace('/[\x{E000}-\x{F8FF}]/u', '', (string) ($place['name'] ?? '')));

        if ($name === '') {
            return ['skipped', null];
        }

        $city = ucwords(strtolower(trim($location)));
        $address = trim(preg_replace('/[\x{E000}-\x{F8FF}]/u', '', (string) ($place['address'] ?? '')));
        $email = strtolower(trim((string) ($place['email'] ?? '')));
        $rating = is_numeric($place['rating'] ?? null) ? round((float) $place['rating'], 1) : null;

        $data = [
            'niche' => $niche,
            'category' => ($place['category'] ?? null) ?: null,
            'website' => Url::normalize($place['website'] ?? null),
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'phone' => trim((string) ($place['phone'] ?? '')) ?: null,
            'address' => $address ?: $city,
            'rating' => $rating !== null && $rating >= 0 && $rating <= 5 ? $rating : null,
            'reviews_count' => is_numeric($place['reviews_count'] ?? null) ? (int) $place['reviews_count'] : null,
            'google_maps_url' => ($place['google_maps_url'] ?? null) ?: null,
            'source' => $source,
        ];

        $lead = Lead::where('business_name', $name)->where('city', $city)->first();

        if (! $lead) {
            try {
                return ['created', Lead::create(['business_name' => $name, 'city' => $city] + $data)];
            } catch (UniqueConstraintViolationException) {
                return ['unchanged', Lead::where('business_name', $name)->where('city', $city)->first()];
            }
        }

        // Lead lama: hanya isi kolom yang masih kosong, kecuali rating yang selalu diperbarui.
        $updates = [];
        foreach ($data as $field => $value) {
            if ($value !== null && $value !== '' && empty($lead->{$field})) {
                $updates[$field] = $value;
            }
        }
        if ($data['rating'] !== null) {
            $updates['rating'] = $data['rating'];
            $updates['reviews_count'] = $data['reviews_count'];
        }

        $lead->fill($updates);

        if (! $lead->isDirty()) {
            return ['unchanged', $lead];
        }

        $lead->save();

        return ['updated', $lead];
    }

    /**
     * Cari email dari website untuk lead hasil sumber API, dibatasi waktu agar job tidak timeout.
     *
     * @param  array<int, Lead>  $leads
     */
    protected function enrichEmails(array $leads): void
    {
        $deadline = time() + (int) (config('leadhunter.scraper.timeout', 840) * 0.6);

        foreach ($leads as $lead) {
            if (time() >= $deadline) {
                break;
            }

            try {
                $contacts = $this->crawler->crawl($lead->website);
                $lead->update(array_filter([
                    'email' => $contacts['email'],
                    'phone' => $lead->phone ?: $contacts['phone'],
                ]));
            } catch (CrawlException $e) {
                Log::info("Crawl email gagal untuk lead {$lead->id}: {$e->getMessage()}");
            }
        }
    }
}
