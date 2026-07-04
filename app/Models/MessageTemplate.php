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
            'offer' => $extras['offer'] ?? 'Jasa Pembuatan Website',
            'sender_name' => $extras['sender_name'] ?? 'Thoriq dari Lefateach',
        ];

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($placeholders) {
            $key = trim($matches[1]);
            return array_key_exists($key, $placeholders) ? ($placeholders[$key] ?? '') : $matches[0];
        }, $text);
    }
}
