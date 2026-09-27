<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutreachMessage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'replied_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * pending = draft siap kirim, queued = masuk antrean kirim otomatis.
     */
    public const STATUSES = ['pending', 'queued', 'sent', 'failed', 'replied'];

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
