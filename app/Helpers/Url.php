<?php

namespace App\Helpers;

class Url
{
    /**
     * Rapikan URL website: tambahkan https:// jika tidak ada skema, tolak skema selain http/https
     * (mencegah link javascript: atau data: tersimpan sebagai website lead).
     */
    public static function normalize(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            // "javascript:alert(1)" tidak punya "//", jadi cek skema berbahaya secara eksplisit.
            if (preg_match('/^(javascript|data|vbscript|file):/i', $url)) {
                return null;
            }
            $url = 'https://'.ltrim($url, '/');
        }

        $parts = parse_url($url);

        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }

        return $url;
    }

    /**
     * True jika URL memakai http/https dan host-nya me-resolve ke IP publik.
     * Dipakai sebelum crawler membuka URL milik pihak luar (mencegah SSRF ke jaringan internal).
     */
    public static function isPublicHttpUrl(string $url, ?callable $resolver = null): bool
    {
        $parts = parse_url($url);

        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower(trim($parts['host'] ?? '', '[]'));

        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $resolver ??= fn (string $h) => gethostbynamel($h) ?: [];
            $ips = $resolver($host);
        }

        if (empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }
}
