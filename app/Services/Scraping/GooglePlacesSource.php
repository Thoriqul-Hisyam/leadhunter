<?php

namespace App\Services\Scraping;

use App\Exceptions\ScrapeFailedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Google Places API (Text Search, versi "New"). Resmi dan stabil, tapi berbayar
 * setelah kuota gratis. Maksimal 60 hasil per query (3 halaman x 20).
 */
class GooglePlacesSource implements LeadSource
{
    protected const ENDPOINT = 'https://places.googleapis.com/v1/places:searchText';

    protected const FIELDS = [
        'places.displayName', 'places.formattedAddress', 'places.nationalPhoneNumber',
        'places.internationalPhoneNumber', 'places.websiteUri', 'places.rating',
        'places.userRatingCount', 'places.googleMapsUri', 'places.primaryTypeDisplayName', 'nextPageToken',
    ];

    public function name(): string
    {
        return 'google_places';
    }

    public function search(string $query, int $limit, callable $onPlace): void
    {
        $key = config('services.google_places.key');

        if (! $key) {
            throw new ScrapeFailedException('GOOGLE_PLACES_API_KEY belum diisi di .env.');
        }

        $pageToken = null;
        $count = 0;

        do {
            try {
                $response = Http::withHeaders([
                    'X-Goog-Api-Key' => $key,
                    'X-Goog-FieldMask' => implode(',', self::FIELDS),
                ])->timeout(30)->post(self::ENDPOINT, array_filter([
                    'textQuery' => $query,
                    'languageCode' => 'id',
                    'regionCode' => 'ID',
                    'pageSize' => min(20, $limit - $count),
                    'pageToken' => $pageToken,
                ]));
            } catch (ConnectionException $e) {
                throw new ScrapeFailedException('Tidak bisa terhubung ke Google Places API: '.$e->getMessage(), 0, $e);
            }

            if ($response->failed()) {
                throw new ScrapeFailedException('Google Places API error: '.Str::limit($response->json('error.message') ?? $response->body(), 300));
            }

            foreach ($response->json('places', []) as $place) {
                $onPlace([
                    'name' => $place['displayName']['text'] ?? null,
                    'address' => $place['formattedAddress'] ?? null,
                    'phone' => $place['nationalPhoneNumber'] ?? $place['internationalPhoneNumber'] ?? null,
                    'website' => $place['websiteUri'] ?? null,
                    'email' => null,
                    'category' => $place['primaryTypeDisplayName']['text'] ?? null,
                    'rating' => $place['rating'] ?? null,
                    'reviews_count' => $place['userRatingCount'] ?? null,
                    'google_maps_url' => $place['googleMapsUri'] ?? null,
                ]);

                if (++$count >= $limit) {
                    return;
                }
            }

            $pageToken = $response->json('nextPageToken');
        } while ($pageToken);
    }
}
