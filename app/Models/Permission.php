<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Semua permission yang dicek aplikasi (lewat Gate, middleware "can:" dan @can).
     */
    public const SLUGS = [
        'manage_leads',
        'manage_campaigns',
        'manage_templates',
        'send_outreach',
        'manage_users',
        'manage_roles',
        'manage_settings',
    ];

    /**
     * The roles that belong to the permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
