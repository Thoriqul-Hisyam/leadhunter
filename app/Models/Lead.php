<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rating' => 'float',
        'reviews_count' => 'integer',
    ];

    /**
     * Tahapan pipeline, berurutan. "lost" berada di luar urutan maju.
     */
    public const STAGES = [
        'new' => 'Baru',
        'contacted' => 'Dihubungi',
        'replied' => 'Membalas',
        'meeting' => 'Meeting',
        'deal' => 'Deal',
        'lost' => 'Batal',
    ];

    public function outreachMessages()
    {
        return $this->hasMany(OutreachMessage::class);
    }

    public function notes()
    {
        return $this->hasMany(LeadNote::class)->latest();
    }

    /**
     * Nama bisnis tanpa embel-embel SEO dari Google Maps, untuk dipakai di pesan.
     * "Moonchelle Beauty Clinic | Klinik Kecantikan Surabaya" → "Moonchelle Beauty Clinic"
     * "Teeth Care Gresik (drg. Yanti)" → "Teeth Care Gresik"
     */
    public function displayName(): string
    {
        $name = trim((string) $this->business_name);
        $short = trim(preg_split('/\s+[|\-–—:]\s+|\s*\(/u', $name)[0] ?? $name);

        return mb_strlen($short) >= 3 ? $short : $name;
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->pipeline_stage ?? 'new'] ?? ucfirst((string) $this->pipeline_stage);
    }

    /**
     * Majukan stage secara otomatis (misalnya saat pesan terkirim atau dibalas),
     * tanpa pernah memundurkan stage yang sudah diatur manual.
     */
    public function advanceStage(string $stage): void
    {
        $order = array_keys(self::STAGES);
        $current = $this->pipeline_stage ?? 'new';

        if ($current === 'lost') {
            return;
        }

        if (array_search($stage, $order, true) > array_search($current, $order, true)) {
            $this->update(['pipeline_stage' => $stage]);
        }
    }

    /**
     * Filter umum untuk halaman leads & export.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('niche', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        foreach (['email', 'phone', 'website'] as $field) {
            $value = $filters["has_{$field}"] ?? null;

            if ($value === 'yes') {
                $query->whereNotNull($field)->where($field, '!=', '');
            } elseif ($value === 'no') {
                $query->where(fn ($q) => $q->whereNull($field)->orWhere($field, ''));
            }
        }

        // Kompatibel dengan filter lama ?no_website=1
        if (! empty($filters['no_website'])) {
            $query->where(fn ($q) => $q->whereNull('website')->orWhere('website', ''));
        }

        if (! empty($filters['stage']) && array_key_exists($filters['stage'], self::STAGES)) {
            $query->where('pipeline_stage', $filters['stage']);
        }

        if (! empty($filters['niche'])) {
            $query->where('niche', $filters['niche']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        return $query;
    }
}
