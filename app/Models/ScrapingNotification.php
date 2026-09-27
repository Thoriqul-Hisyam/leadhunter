<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notifikasi di ikon lonceng (scraping, generate pesan, follow-up, dll).
 */
class ScrapingNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'niche',
        'location',
        'type',
        'title',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * @param  string  $type  running|success|failed
     */
    public static function notify(string $type, string $title, string $message, ?string $niche = null, ?string $location = null): self
    {
        return static::create([
            'niche' => $niche ?? '-',
            'location' => $location ?? '-',
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
