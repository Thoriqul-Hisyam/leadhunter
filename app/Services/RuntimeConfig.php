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
        $set('services.ai.fast_model', $s['ai_fast_model']);
        $set('services.ai.backup.base_url', $s['ai_backup_base_url'] ? rtrim($s['ai_backup_base_url'], '/') : '');
        $set('services.ai.backup.key', $s['ai_backup_api_key']);
        $set('services.ai.backup.model', $s['ai_backup_model']);
        $set('services.pagespeed.key', $s['pagespeed_api_key']);

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

        // WhatsApp gateway
        $set('leadhunter.whatsapp.driver', $s['wa_driver']);
        $set('leadhunter.whatsapp.token', $s['wa_token']);
        $set('leadhunter.whatsapp.base_url', $s['wa_base_url'] ? rtrim($s['wa_base_url'], '/') : '');
        $set('leadhunter.whatsapp.hourly_limit', $s['wa_hourly_limit'] !== '' ? (int) $s['wa_hourly_limit'] : '');
        $set('leadhunter.whatsapp.daily_limit', $s['wa_daily_limit'] !== '' ? (int) $s['wa_daily_limit'] : '');
        $set('leadhunter.whatsapp.webhook_token', $s['wa_webhook_token']);
        if ($s['wa_allow_landline'] !== '') {
            config(['leadhunter.whatsapp.allow_landline' => (bool) (int) $s['wa_allow_landline']]);
        }

        // Aturan pengiriman
        $set('leadhunter.sending.hourly_limit', $s['email_hourly_limit'] !== '' ? (int) $s['email_hourly_limit'] : '');
        $set('leadhunter.sending.daily_limit', $s['email_daily_limit'] !== '' ? (int) $s['email_daily_limit'] : '');
        $set('leadhunter.sending.window_start', $s['send_window_start']);
        $set('leadhunter.sending.window_end', $s['send_window_end']);
        if ($s['send_weekdays_only'] !== '') {
            config(['leadhunter.sending.weekdays_only' => (bool) (int) $s['send_weekdays_only']]);
        }

        // Mailer yang sudah dibuat memakai config lama; buang supaya dibuat ulang.
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->forgetMailers();
        }
    }
}
