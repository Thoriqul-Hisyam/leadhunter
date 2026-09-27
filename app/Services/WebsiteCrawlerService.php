<?php

namespace App\Services;

use App\Exceptions\CrawlException;
use App\Helpers\Url;
use Illuminate\Process\Exceptions\ProcessTimedOutException;

/**
 * Buka website bisnis dengan Puppeteer (scripts/crawl-website.js) untuk mencari email & telepon.
 */
class WebsiteCrawlerService
{
    /** @var callable|null resolver DNS, bisa diganti di test */
    public $resolver = null;

    public function __construct(protected NodeScriptRunner $runner)
    {
    }

    /**
     * @return array{email: ?string, phone: ?string}
     *
     * @throws CrawlException
     */
    public function crawl(?string $website): array
    {
        $url = Url::normalize($website);

        if (! $url) {
            throw new CrawlException('URL website tidak valid (harus http/https).');
        }

        if (! Url::isPublicHttpUrl($url, $this->resolver)) {
            throw new CrawlException('Website mengarah ke alamat internal/privat atau domain-nya tidak ditemukan.');
        }

        $timeout = (int) config('leadhunter.crawler.timeout', 60);
        $data = null;

        try {
            $this->runner->run('crawl-website.js', [$url], $timeout, function (string $line) use (&$data) {
                if (str_starts_with($line, 'RESULT_JSON:')) {
                    $data = json_decode(substr($line, 12), true);
                }
            });
        } catch (ProcessTimedOutException) {
            throw new CrawlException("Website tidak merespons dalam {$timeout} detik.");
        }

        if (! is_array($data) || ! ($data['success'] ?? false)) {
            throw new CrawlException('Website tidak bisa dibuka'.(! empty($data['error']) ? ': '.$data['error'] : '.'));
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));

        return [
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
        ];
    }
}
