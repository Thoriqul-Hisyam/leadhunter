<?php

namespace App\Services\Scraping;

use App\Exceptions\ScrapeFailedException;
use App\Services\NodeScriptRunner;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Str;

/**
 * Scraping DOM Google Maps memakai scripts/scrape-gmaps.js (gratis, tapi rapuh terhadap
 * perubahan tampilan Google Maps dan melanggar ToS Google).
 */
class PuppeteerGoogleMapsSource implements LeadSource
{
    public function __construct(protected NodeScriptRunner $runner)
    {
    }

    public function name(): string
    {
        return 'google_maps';
    }

    public function search(string $query, int $limit, callable $onPlace): void
    {
        $timeout = (int) config('leadhunter.scraper.timeout', 840);

        try {
            $result = $this->runner->run('scrape-gmaps.js', [$query, $limit], $timeout, function (string $line) use ($onPlace) {
                if (! str_starts_with($line, 'LEAD_ROW:')) {
                    return;
                }

                $item = json_decode(substr($line, 9), true);
                if (is_array($item) && ! empty($item['name'])) {
                    $onPlace($item);
                }
            });
        } catch (ProcessTimedOutException $e) {
            throw new ScrapeFailedException("Scraping melebihi batas waktu {$timeout} detik. Lead yang sudah ditemukan tetap tersimpan.", 0, $e);
        }

        if ($result->exitCode() === 3 || str_contains($result->errorOutput(), 'BLOCKED:')) {
            throw new ScrapeFailedException(str_contains($result->errorOutput(), 'BLOCKED:consent')
                ? 'Google Maps tertahan di halaman persetujuan cookie. Coba lagi, atau gunakan SCRAPER_DRIVER=google_places.'
                : 'Google Maps menampilkan CAPTCHA (terdeteksi sebagai bot). Tunggu beberapa jam dan kurangi frekuensi scraping, atau gunakan SCRAPER_DRIVER=google_places / apify.');
        }

        if ($result->failed()) {
            $error = trim(Str::afterLast(trim($result->errorOutput()), "\n")) ?: "exit code {$result->exitCode()}";

            throw new ScrapeFailedException('Puppeteer gagal: '.Str::limit($error, 300));
        }
    }
}
