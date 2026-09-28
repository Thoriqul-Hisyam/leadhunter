<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Deskripsi bawaan lama (Inggris) → Indonesia. Deskripsi yang sudah diubah pengguna dibiarkan. */
    protected array $descriptions = [
        'admin' => [
            'Full access to system features, role configurations, and user accounts management',
            'Akses penuh ke semua fitur, pengaturan, role, dan manajemen akun pengguna',
        ],
        'user' => [
            'Standard access to lead scraping, email campaign pipelines, template builders, and outreach generators',
            'Akses standar: scraping lead, campaign, template, dan pembuatan serta pengiriman outreach',
        ],
    ];

    public function up(): void
    {
        foreach ($this->descriptions as $slug => [$old, $new]) {
            DB::table('roles')->where('slug', $slug)->where('description', $old)->update(['description' => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->descriptions as $slug => [$old, $new]) {
            DB::table('roles')->where('slug', $slug)->where('description', $new)->update(['description' => $old]);
        }
    }
};
