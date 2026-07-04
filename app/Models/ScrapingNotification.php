<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
