<?php

namespace App\Services;

use App\Models\Lead;

/**
 * Pemeriksaan otomatis pesan buatan AI sebelum masuk daftar kirim:
 * panjang wajar, tanpa placeholder tersisa, tanpa frasa klise/artefak router,
 * dan tidak menyebut rating/jumlah ulasan/alamat lead (terasa seperti hasil scraping).
 */
class MessageQualityGate
{
    public const BANNED_PHRASES = [
        'semoga email ini', 'semoga pesan ini', 'saya harap anda', 'i hope this',
        'era digital', 'solusi digital', 'kehadiran digital', 'dunia digital', 'optimalkan',
        'sebagai ai', 'as an ai', 'model bahasa', 'skipped', 'berikut adalah', 'berikut ini adalah',
    ];

    /**
     * @return array<int, string> daftar masalah (kosong = lolos)
     */
    public function problems(string $message, ?string $subject, ?Lead $lead, string $channel): array
    {
        $problems = [];
        $text = $message."\n".$subject;
        $lower = mb_strtolower($text);

        if (preg_match('/\{\{[^}]*\}\}|\[[^\]\n]{2,40}\]|<[^>\n]{2,40}>/u', $text)) {
            $problems[] = 'Masih ada placeholder seperti [Nama] atau {{...}}';
        }

        $banned = array_values(array_filter(self::BANNED_PHRASES, fn ($phrase) => str_contains($lower, $phrase)));
        if (preg_match('/^\s*(→|->|=>)/mu', $message)) {
            $banned[] = '→';
        }
        if ($banned) {
            $problems[] = 'Frasa klise/artefak: "'.implode('", "', $banned).'"';
        }

        $words = str_word_count(preg_replace('/[^\pL\pN\s]/u', ' ', $message));
        [$min, $max] = $channel === 'email' ? [30, 190] : [12, 120];
        if ($words < $min) {
            $problems[] = "Terlalu pendek ({$words} kata)";
        } elseif ($words > $max) {
            $problems[] = "Terlalu panjang ({$words} kata)";
        }

        if ($lead) {
            $problems = array_merge($problems, $this->leakedData($text, $lead));
        }

        return $problems;
    }

    /**
     * Data mentah lead yang tidak boleh disebut (sudah diatur di prompt, dicek ulang di sini).
     */
    protected function leakedData(string $text, Lead $lead): array
    {
        $problems = [];

        if ($lead->rating) {
            $rating = number_format((float) $lead->rating, 1);
            if (preg_match('/(?<![\d.,])('.preg_quote($rating, '/').'|'.preg_quote(str_replace('.', ',', $rating), '/').')(?![\d])/u', $text)) {
                $problems[] = 'Menyebut angka rating';
            }
        }

        if ($lead->reviews_count && $lead->reviews_count >= 10) {
            $count = (string) $lead->reviews_count;
            $variants = array_unique([$count, number_format($lead->reviews_count, 0, ',', '.'), number_format($lead->reviews_count)]);
            foreach ($variants as $variant) {
                if (preg_match('/(?<![\d.,])'.preg_quote($variant, '/').'(?![\d])/u', $text)) {
                    $problems[] = 'Menyebut jumlah ulasan';
                    break;
                }
            }
        }

        // Bagian jalan dari alamat, mis. "Jl. Raya Rungkut Kidul No.21"
        $street = trim(explode(',', (string) $lead->address)[0] ?? '');
        if (mb_strlen($street) >= 10 && str_contains(mb_strtolower($text), mb_strtolower($street))) {
            $problems[] = 'Menyebut alamat';
        }

        return $problems;
    }
}
