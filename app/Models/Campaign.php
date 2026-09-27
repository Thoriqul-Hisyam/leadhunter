<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Campaign extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'generation_total' => 'integer',
        'generation_done' => 'integer',
        'generation_failed' => 'integer',
    ];

    public function outreachMessages()
    {
        return $this->hasMany(OutreachMessage::class);
    }

    public function isGenerating(): bool
    {
        return $this->generation_done + $this->generation_failed < $this->generation_total;
    }

    /**
     * Tambah jumlah pesan AI yang sedang diantrekan untuk campaign ini.
     */
    public function queueGeneration(int $count): void
    {
        if ($count <= 0) {
            return;
        }

        // Mulai hitungan baru jika batch sebelumnya sudah selesai.
        if (! $this->fresh()->isGenerating()) {
            $this->update(['generation_total' => 0, 'generation_done' => 0, 'generation_failed' => 0]);
        }

        $this->increment('generation_total', $count);
    }

    public function recordGeneration(bool $success): void
    {
        DB::table('campaigns')->where('id', $this->id)->increment($success ? 'generation_done' : 'generation_failed');
    }
}
