<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Placeholder yang bisa dipakai di template, beserta penjelasannya (ditampilkan di form template).
     */
    public const PLACEHOLDERS = [
        'business_name' => 'Nama bisnis lead',
        'city' => 'Kota lead',
        'niche' => 'Niche / bidang lead',
        'website' => 'Website lead',
        'phone' => 'Telepon lead',
        'offer' => 'Penawaran (default dari Pengaturan)',
        'sender_name' => 'Identitas pengirim, mis. "Thoriq dari Lefateach"',
        'company_name' => 'Nama usaha Anda',
        'company_website' => 'Website usaha Anda',
    ];

    /**
     * Render the template placeholders using the lead and extra data.
     */
    public static function render(string $text, Lead $lead, array $extras = []): string
    {
        $placeholders = [
            'business_name' => $lead->business_name,
            'city' => $lead->city,
            'niche' => $lead->niche,
            'website' => $lead->website ?: '',
            'phone' => $lead->phone ?: '',
            'offer' => ($extras['offer'] ?? null) ?: Setting::defaultOffer(),
            'sender_name' => ($extras['sender_name'] ?? null) ?: Setting::senderIdentity(),
            'company_name' => Setting::get('company_name', ''),
            'company_website' => Setting::get('company_website', ''),
        ];

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($placeholders) {
            $key = trim($matches[1]);
            return array_key_exists($key, $placeholders) ? ($placeholders[$key] ?? '') : $matches[0];
        }, $text);
    }
}
