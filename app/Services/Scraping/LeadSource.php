<?php

namespace App\Services\Scraping;

use App\Exceptions\ScrapeFailedException;

/**
 * Sumber data bisnis (Google Maps via Puppeteer, Google Places API, Apify, ...).
 */
interface LeadSource
{
    /**
     * Nilai yang disimpan di kolom leads.source.
     */
    public function name(): string;

    /**
     * Cari bisnis dan panggil $onPlace untuk setiap hasil, segera setelah ditemukan.
     *
     * Format $place: name, address, phone, website, email, category, rating, reviews_count, google_maps_url
     * (semua kecuali name boleh null).
     *
     * @param  callable(array $place): void  $onPlace
     *
     * @throws ScrapeFailedException
     */
    public function search(string $query, int $limit, callable $onPlace): void;
}
