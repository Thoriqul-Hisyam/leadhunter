<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Langkah lanjutan sequence (langkah 2 dst.). Langkah 1 adalah pesan pembuka campaign.
 */
class CampaignStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'step' => 'integer',
        'delay_days' => 'integer',
        'auto_queue' => 'boolean',
    ];

    public const CHANNELS = [
        'same' => 'Kanal sebelumnya',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
    ];

    /** Langkah terakhir yang boleh dibuat (1 pembuka + 4 lanjutan). */
    public const MAX_STEP = 5;

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
