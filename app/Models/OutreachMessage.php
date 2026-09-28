<?php

namespace App\Models;

use App\Models\Concerns\RecordsCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutreachMessage extends Model
{
    use HasFactory, RecordsCreator;

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'replied_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'attempts' => 'integer',
        'step' => 'integer',
        'needs_review' => 'boolean',
    ];

    /**
     * pending = draft siap kirim, queued = masuk antrean kirim otomatis.
     */
    public const STATUSES = ['pending', 'queued', 'sent', 'failed', 'replied'];

    public const STATUS_LABELS = [
        'pending' => 'Draft',
        'queued' => 'Antre',
        'sent' => 'Terkirim',
        'failed' => 'Gagal',
        'replied' => 'Dibalas',
    ];

    public static function statusLabelFor(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst((string) $status);
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    /**
     * Kategori balasan (hasil klasifikasi). auto_reply tidak dihitung sebagai reply.
     */
    public const REPLY_CATEGORIES = [
        'interested' => 'Tertarik',
        'pricing' => 'Tanya harga',
        'not_interested' => 'Tidak tertarik',
        'auto_reply' => 'Balasan otomatis',
        'other' => 'Lainnya',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    public function followupOf()
    {
        return $this->belongsTo(OutreachMessage::class, 'followup_of_id');
    }

    public function followups()
    {
        return $this->hasMany(OutreachMessage::class, 'followup_of_id');
    }

    public function isDelivered(): bool
    {
        return in_array($this->status, ['sent', 'replied'], true);
    }

    /** Langkah lanjutan sequence / follow-up (bukan pesan pembuka). */
    public function isSequenceStep(): bool
    {
        return $this->followup_of_id !== null || (int) $this->step > 1;
    }

    public function replyCategoryLabel(): ?string
    {
        return $this->reply_category ? (self::REPLY_CATEGORIES[$this->reply_category] ?? $this->reply_category) : null;
    }

    /**
     * Batalkan pesan lead yang belum terkirim (draft & antrean) agar tidak ada lagi yang dikirim.
     *
     * @param  bool  $sequenceOnly  hanya langkah lanjutan (pesan pembuka dibiarkan)
     * @param  string|null  $type  batasi ke satu channel
     * @return int jumlah pesan yang dibatalkan
     */
    public static function cancelOpenFor(int $leadId, string $reason, bool $sequenceOnly = false, ?string $type = null): int
    {
        return static::where('lead_id', $leadId)
            ->whereIn('status', ['queued', 'pending'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($sequenceOnly, fn ($q) => $q->where(fn ($q) => $q->whereNotNull('followup_of_id')->orWhere('step', '>', 1)))
            ->update([
                'status' => 'failed',
                'scheduled_at' => null,
                'last_error' => $reason,
                'attempts' => (int) config('leadhunter.sending.max_attempts', 3), // jangan di-retry otomatis
            ]);
    }

    public function markSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => $this->sent_at ?? now(),
            'scheduled_at' => null,
            'last_error' => null,
        ]);

        $this->lead?->advanceStage('contacted');
    }

    public function markReplied($at = null): void
    {
        $this->update([
            'status' => 'replied',
            'sent_at' => $this->sent_at ?? now(),
            'replied_at' => $this->replied_at ?? ($at ?? now()),
        ]);

        $this->lead?->advanceStage('replied');
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'scheduled_at' => null,
            'last_error' => mb_substr($error, 0, 2000),
        ]);
    }

    /**
     * Ubah status secara manual (tombol status / bulk action), menjaga kolom waktu tetap konsisten.
     */
    public function setStatus(string $status): void
    {
        match ($status) {
            'sent' => $this->markSent(),
            'replied' => $this->markReplied(),
            'pending' => $this->update(['status' => 'pending', 'scheduled_at' => null, 'replied_at' => null]),
            default => $this->update(['status' => $status]),
        };
    }
}
