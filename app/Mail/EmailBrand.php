<?php

namespace App\Mail;

use App\Helpers\Phone;
use App\Helpers\Url;
use App\Models\Setting;

/**
 * Identitas visual email (outreach, laporan mingguan) dan halaman unsubscribe.
 * Palet & font mengikuti website perusahaan (lefateach.com); isi identitas diambil dari Pengaturan.
 * Warna ditulis hex solid (tanpa rgba) karena Outlook desktop tidak mendukung transparansi.
 */
class EmailBrand
{
    public const COLORS = [
        'page' => '#f4f4f8',        // latar di luar kartu
        'card' => '#ffffff',
        'soft' => '#f7f7fa',        // tile statistik
        'line' => '#e9e9ef',
        'ink' => '#121214',         // judul, tombol utama, footer
        'text' => '#364153',        // isi pesan
        'muted' => '#6a7282',
        'lime' => '#c8f828',
        'purple' => '#5433ff',
        'danger' => '#e11d48',
        'on_ink' => '#ffffff',      // teks di atas footer gelap
        'on_ink_muted' => '#a9a9ab',
        'on_ink_subtle' => '#7f7f82',
        'ink_line' => '#2a2a2d',
    ];

    /**
     * Orb kaca 3D seperti di website, di-render dari CSS morph-glass situs lalu dikompres (PNG palet, transparan).
     * Path relatif ke public/.
     */
    public const ORBS = [
        'lime' => 'images/email/orb-lime.png',
        'purple' => 'images/email/orb-purple.png',
    ];

    public const FONT ="'Outfit','Plus Jakarta Sans','Segoe UI',Helvetica,Arial,sans-serif";

    public const FONT_URL = 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap';

    public static function fromSettings(): array
    {
        $settings = Setting::values();
        $name = trim((string) $settings['company_name']);
        $phone = trim((string) $settings['company_phone']);
        $website = Url::normalize($settings['company_website']);

        return [
            'name' => $name ?: config('app.name'),
            'initial' => mb_strtoupper(mb_substr($name ?: config('app.name'), 0, 1)),
            'tagline' => trim((string) $settings['company_tagline']),
            'logo' => Url::normalize($settings['company_logo_url']),
            'phone' => $phone,
            'phone_href' => $phone !== '' ? 'tel:'.preg_replace('/[^0-9+]/', '', $phone) : null,
            // Tombol WhatsApp hanya untuk nomor seluler; nomor kantor tetap tampil sebagai telepon.
            'whatsapp_url' => Phone::isMobile($phone) ? Phone::whatsAppUrl($phone) : null,
            'website' => $website,
            'website_label' => $website ? preg_replace('#^https?://(www\.)?#i', '', rtrim($website, '/')) : null,
            'colors' => self::COLORS,
            'font' => self::FONT,
        ];
    }
}
