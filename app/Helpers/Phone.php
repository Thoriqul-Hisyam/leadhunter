<?php

namespace App\Helpers;

class Phone
{
    /**
     * Ubah nomor telepon Indonesia ke format internasional untuk wa.me.
     * Contoh: "0812-3456-789" → "628123456789", "+62 812 3456 789" → "628123456789",
     * "812 3456 789" → "628123456789". Nomor yang terlalu pendek dikembalikan null.
     */
    public static function toWhatsApp(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return strlen($digits) >= 9 && strlen($digits) <= 15 ? $digits : null;
    }

    /**
     * True untuk nomor seluler Indonesia (62 8xx). Nomor kantor seperti (031) 5964600 → false.
     * Catatan: WhatsApp Business bisa didaftarkan di nomor kantor, jadi false berarti "belum tentu WA",
     * bukan "pasti bukan WA". Pengiriman otomatis hanya ke nomor seluler kecuali diizinkan di Pengaturan.
     */
    public static function isMobile(?string $phone): bool
    {
        $number = static::toWhatsApp($phone);

        return $number !== null
            && str_starts_with($number, '628')
            && strlen($number) >= 10
            && strlen($number) <= 14;
    }

    public static function whatsAppUrl(?string $phone, string $message = ''): ?string
    {
        $number = static::toWhatsApp($phone);

        if (! $number) {
            return null;
        }

        return "https://wa.me/{$number}".($message !== '' ? '?text='.rawurlencode($message) : '');
    }
}
