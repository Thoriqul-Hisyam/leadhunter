<?php

namespace App\Services\Scraping;

use App\Exceptions\ScrapeFailedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Apify actor Google Maps Scraper (default: compass~crawler-google-places).
 * Actor dijalankan sinkron lalu dataset-nya dibaca langsung.
 */
class ApifySource implements LeadSource
{
    public function name(): string
    {
        return 'apify';
    }

    public function search(string $query, int $limit, callable $onPlace): void
    {
        $token = config('services.apify.token');
        $actor = config('services.apify.actor', 'compass~crawler-google-places');

        if (! $token) {
            throw new ScrapeFailedException('APIFY_TOKEN belum diisi di .env.');
        }

        try {
            $response = Http::withToken($token)
                ->timeout(330)
                ->post("https://api.apify.com/v2/acts/{$actor}/run-sync-get-dataset-items?timeout=300", [
                    'searchStringsArray' => [$query],
                    'maxCrawledPlacesPerSearch' => $limit,
                    'language' => 'id',
                    'countryCode' => 'id',
                ]);
        } catch (ConnectionException $e) {
            throw new ScrapeFailedException('Tidak bisa terhubung ke Apify: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw new ScrapeFailedException('Apify error: '.Str::limit($response->json('error.message') ?? $response->body(), 300));
        }

        foreach (array_slice((array) $response->json(), 0, $limit) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $onPlace([
                'name' => $item['title'] ?? null,
                'address' => $item['address'] ?? null,
                'phone' => $item['phone'] ?? null,
                'website' => $item['website'] ?? null,
                'email' => $item['emails'][0] ?? $item['email'] ?? null,
                'category' => $item['categoryName'] ?? null,
                'rating' => $item['totalScore'] ?? null,
                'reviews_count' => $item['reviewsCount'] ?? null,
                'google_maps_url' => $item['url'] ?? null,
            ]);
        }
    }
}
