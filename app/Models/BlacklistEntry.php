<?php

namespace App\Models;

use App\Helpers\Phone;
use Illuminate\Database\Eloquent\Model;

class BlacklistEntry extends Model
{
    protected $fillable = ['type', 'value', 'reason'];

    public static function normalize(string $type, string $value): string
    {
        return $type === 'phone'
            ? (Phone::toWhatsApp($value) ?? preg_replace('/\D+/', '', $value))
            : mb_strtolower(trim($value));
    }

    public static function add(string $type, string $value, ?string $reason = null): self
    {
        return static::firstOrCreate(
            ['type' => $type, 'value' => static::normalize($type, $value)],
            ['reason' => $reason]
        );
    }

    public static function contains(string $type, ?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return static::where('type', $type)->where('value', static::normalize($type, $value))->exists();
    }

    /**
     * True jika email atau nomor lead ini ada di blacklist untuk channel tersebut.
     */
    public static function blocks(Lead $lead, string $channel): bool
    {
        return $channel === 'whatsapp'
            ? static::contains('phone', $lead->phone)
            : static::contains('email', $lead->email);
    }
}
