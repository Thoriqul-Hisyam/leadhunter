<?php

namespace App\Models;

use App\Helpers\Phone;
use App\Helpers\Url;
use App\Models\Concerns\RecordsCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory, RecordsCreator;

    protected $guarded = [];

    protected $casts = [
        'rating' => 'float',
        'reviews_count' => 'integer',
        'phone_is_mobile' => 'boolean',
        'score' => 'integer',
        'website_score' => 'integer',
        'website_https' => 'boolean',
        'website_audited_at' => 'datetime',
    ];

    /** Skor minimum untuk badge "Hot". */
    public const HOT_SCORE = 70;

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

    protected static function booted(): void
    {
        static::saving(function (Lead $lead) {
            if ($lead->isDirty('phone') || ! $lead->exists) {
                $lead->whatsapp_number = Phone::toWhatsApp($lead->phone);
                $lead->phone_is_mobile = Phone::isMobile($lead->phone);
            }

            if (! $lead->place_id && $lead->google_maps_url) {
                $lead->place_id = static::placeIdFromUrl($lead->google_maps_url);
            }

            $lead->score = $lead->computeScore();
        });
    }

    /**
     * Skor prioritas 0–100 untuk penawaran jasa website: semakin besar kebutuhan (belum punya
     * website / website lambat), semakin populer bisnisnya, dan semakin mudah dihubungi, semakin tinggi.
     */
    public function computeScore(): int
    {
        // Kebutuhan website (maks. 35)
        if (! $this->website) {
            $need = 35;
        } elseif (Url::socialPlatform($this->website)) {
            $need = 30;
        } elseif ($this->website_score !== null && $this->website_score < 50) {
            $need = 20;
        } elseif ($this->website_https === false) {
            $need = 15;
        } else {
            $need = 5;
        }

        // Popularitas di Google Maps (maks. 35)
        $reviews = (int) $this->reviews_count;
        $popularity = match (true) {
            $reviews >= 200 => 25,
            $reviews >= 50 => 18,
            $reviews >= 10 => 10,
            default => 0,
        };
        $popularity += match (true) {
            (float) $this->rating >= 4.5 => 10,
            (float) $this->rating >= 4.0 => 5,
            default => 0,
        };

        // Bisa dihubungi (maks. 25)
        $contact = ($this->phone_is_mobile ? 15 : ($this->phone ? 5 : 0)) + ($this->email ? 10 : 0);

        return min(100, $need + $popularity + $contact);
    }

    public function isHot(): bool
    {
        return $this->score >= self::HOT_SCORE;
    }

    /**
     * ID tempat dari URL Google Maps: ".../data=!4m7!3m6!1s0x2dd7fd63a3b99215:0x9733ecfa8aeda365!8m2..."
     * atau "...?cid=1234567890".
     */
    public static function placeIdFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (preg_match('/!1s(0x[0-9a-f]+:0x[0-9a-f]+)/i', $url, $m)) {
            return strtolower($m[1]);
        }

        if (preg_match('/[?&]cid=(\d+)/', $url, $m)) {
            return 'cid:'.$m[1];
        }

        return null;
    }

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

    /** Stage yang menghentikan sequence: lead sudah merespons atau sudah ditutup. */
    public const SEQUENCE_STOP_STAGES = ['replied', 'meeting', 'deal', 'lost'];

    /**
     * Alasan follow-up tidak boleh dibuat/dikirim lagi ke lead ini, atau null jika boleh.
     */
    public function sequenceStopReason(): ?string
    {
        if (in_array($this->pipeline_stage, self::SEQUENCE_STOP_STAGES, true)) {
            return 'Sequence dihentikan: lead sudah di stage '.$this->stageLabel().'.';
        }

        if ($this->outreachMessages()->where('status', 'replied')->exists()) {
            return 'Sequence dihentikan: lead sudah membalas.';
        }

        return null;
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

        if (! empty($filters['min_score'])) {
            $query->where('score', '>=', (int) $filters['min_score']);
        }

        return $query;
    }
}
