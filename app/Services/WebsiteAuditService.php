<?php

namespace App\Services;

use App\Exceptions\CrawlException;
use App\Helpers\Url;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Audit singkat website lead memakai Google PageSpeed Insights (mobile):
 * skor performa 0–100 dan apakah halaman akhirnya memakai HTTPS.
 * Tanpa API key memakai kuota bersama yang sering habis; isi key (gratis) di Pengaturan.
 */
class WebsiteAuditService
{
    public const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * @return array{score: int, https: bool}
     *
     * @throws CrawlException
     */
    public function audit(?string $website): array
    {
        $url = Url::normalize($website);

        if (! $url || Url::socialPlatform($url)) {
            throw new CrawlException('Bukan website sendiri (kosong atau akun media sosial).');
        }

        try {
            $response = Http::timeout(120)->get(self::ENDPOINT, array_filter([
                'url' => $url,
                'strategy' => 'mobile',
                'category' => 'performance',
                'key' => config('services.pagespeed.key'),
            ]));
        } catch (ConnectionException $e) {
            throw new CrawlException('Tidak bisa menghubungi Google PageSpeed: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            $error = (string) ($response->json('error.message') ?? $response->body());

            // Tanpa key, semua pengguna berbagi kuota harian yang sangat kecil dan sering habis.
            if ($response->status() === 429 || str_contains($error, 'Quota exceeded')) {
                throw new CrawlException(config('services.pagespeed.key')
                    ? 'Kuota harian Google PageSpeed untuk API key ini habis. Coba lagi besok.'
                    : 'Kuota Google PageSpeed tanpa API key habis. Isi Google PageSpeed API key (gratis) di Pengaturan → Koneksi.');
            }

            throw new CrawlException('PageSpeed gagal: '.Str::limit($error, 200));
        }

        $score = $response->json('lighthouseResult.categories.performance.score');

        if (! is_numeric($score)) {
            throw new CrawlException('PageSpeed tidak mengembalikan skor untuk website ini.');
        }

        $finalUrl = (string) ($response->json('lighthouseResult.finalDisplayedUrl') ?? $response->json('lighthouseResult.finalUrl') ?? $url);

        return [
            'score' => (int) round($score * 100),
            'https' => str_starts_with(strtolower($finalUrl), 'https://'),
        ];
    }
}
