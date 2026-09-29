<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Default dipakai jika key belum pernah disimpan (misalnya di database test).
     */
    public const DEFAULTS = [
        'sender_name' => '',
        'company_name' => '',
        'company_website' => '',
        'company_phone' => '',
        'company_tagline' => '',
        'company_logo_url' => '',
        'default_offer' => 'Jasa Pembuatan Website Profesional',
        'followup_enabled' => '0',
        'followup_days' => '3',
        'weekly_report_enabled' => '1',

        // Koneksi AI (kosong = pakai nilai dari .env / config)
        'ai_base_url' => '',
        'ai_api_key' => '',
        'ai_model' => '',
        'ai_fast_model' => '',
        'ai_backup_base_url' => '',
        'ai_backup_api_key' => '',
        'ai_backup_model' => '',
        'ai_prompt_variants' => '1',
        'pagespeed_api_key' => '',

        // Email SMTP (kosong = pakai nilai dari .env / config)
        'mail_mailer' => '',
        'mail_host' => '',
        'mail_port' => '',
        'mail_username' => '',
        'mail_password' => '',
        'mail_from_address' => '',
        'mail_from_name' => '',
        'imap_enabled' => '',

        // WhatsApp gateway (kosong = pakai config/.env)
        'wa_driver' => '',
        'wa_token' => '',
        'wa_base_url' => '',
        'wa_sender' => '',
        'wa_hourly_limit' => '',
        'wa_daily_limit' => '',
        'wa_allow_landline' => '',
        'wa_webhook_token' => '',

        // Aturan pengiriman (kosong = pakai config/.env)
        'email_hourly_limit' => '',
        'email_daily_limit' => '',
        'send_window_start' => '',
        'send_window_end' => '',
        'send_weekdays_only' => '',

        // Kapan koneksi terakhir berhasil dites (untuk checklist onboarding)
        'ai_tested_at' => '',
        'mail_tested_at' => '',
        'wa_tested_at' => '',
    ];

    /**
     * Disimpan terenkripsi (APP_KEY) di database.
     */
    public const SECRETS = ['ai_api_key', 'ai_backup_api_key', 'mail_password', 'wa_token', 'pagespeed_api_key'];

    protected const CACHE_KEY = 'app_settings';

    /**
     * Semua setting sebagai array key => value (sudah digabung dengan default, secret sudah didekripsi).
     */
    public static function values(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->pluck('value', 'key')->toArray();
        });

        $values = array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null));

        foreach (self::SECRETS as $key) {
            $values[$key] = static::decrypt($values[$key] ?? '');
        }

        return $values;
    }

    public static function get(string $key, $default = null)
    {
        $values = static::values();

        return $values[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::SECRETS, true) && $value !== null && $value !== '') {
                $value = Crypt::encryptString((string) $value);
            }

            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function hasSecret(string $key): bool
    {
        return static::get($key) !== '';
    }

    protected static function decrypt(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // APP_KEY berubah: secret lama tidak bisa dibaca dan harus diisi ulang.
            return '';
        }
    }

    /**
     * Identitas pengirim, misalnya "Thoriq dari Lefateach".
     */
    public static function senderIdentity(): string
    {
        $name = trim((string) static::get('sender_name'));
        $company = trim((string) static::get('company_name'));

        if ($name && $company) {
            return "{$name} dari {$company}";
        }

        return $name ?: ($company ?: 'Tim Kami');
    }

    public static function defaultOffer(): string
    {
        return (string) static::get('default_offer') ?: 'Jasa Pembuatan Website Profesional';
    }

    public static function followupEnabled(): bool
    {
        return (bool) (int) static::get('followup_enabled');
    }

    public static function followupDays(): int
    {
        return max(1, (int) static::get('followup_days', 3));
    }
}
