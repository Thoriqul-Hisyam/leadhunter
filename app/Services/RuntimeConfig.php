<?php

namespace App\Services;

use App\Models\Setting;
use Throwable;

/**
 * Koneksi AI & email diatur dari halaman Pengaturan (tabel settings), bukan .env.
 * Nilai dari database menimpa config saat aplikasi boot dan sebelum setiap job queue;
 * key yang kosong di database tetap memakai nilai config/.env.
 */
class RuntimeConfig
{
    public static function apply(): void
    {
        try {
            $s = Setting::values();
        } catch (Throwable) {
            // Tabel settings belum ada (sebelum migrate, atau saat test menyiapkan database).
            return;
        }

        $set = function (string $configKey, $value) {
            if ($value !== null && $value !== '') {
                config([$configKey => $value]);
            }
        };

        // AI
        $set('services.ai.base_url', $s['ai_base_url'] ? rtrim($s['ai_base_url'], '/') : '');
        $set('services.ai.key', $s['ai_api_key']);
        $set('services.ai.model', $s['ai_model']);

        // Email
        $set('mail.default', $s['mail_mailer']);
        $set('mail.mailers.smtp.host', $s['mail_host']);
        $set('mail.mailers.smtp.port', $s['mail_port'] !== '' ? (int) $s['mail_port'] : '');
        $set('mail.mailers.smtp.username', $s['mail_username']);
        $set('mail.mailers.smtp.password', $s['mail_password']);
        // Pengirim: kosong → pakai akun Gmail & identitas di Pengaturan, bukan default Laravel ("Example").
        $set('mail.from.address', $s['mail_from_address'] ?: $s['mail_username']);
        $senderName = trim(implode(' · ', array_filter([$s['sender_name'], $s['company_name']])));
        $set('mail.from.name', $s['mail_from_name'] ?: $senderName);

        if ($s['mail_port'] !== '') {
            // 465 = SSL langsung (smtps); 587 = STARTTLS (smtp)
            config(['mail.mailers.smtp.scheme' => (int) $s['mail_port'] === 465 ? 'smtps' : 'smtp']);
        }

        // Deteksi reply memakai akun Gmail yang sama dengan SMTP
        if ($s['imap_enabled'] !== '') {
            config(['leadhunter.imap.enabled' => (bool) (int) $s['imap_enabled']]);
        }
        $set('leadhunter.imap.username', $s['mail_username']);
        $set('leadhunter.imap.password', $s['mail_password']);

        // Mailer yang sudah dibuat memakai config lama; buang supaya dibuat ulang.
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->forgetMailers();
        }
    }
}
