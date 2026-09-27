<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seeder lama menaruh emoji di deskripsi role & permission (tampil di halaman Roles).
     */
    public function up(): void
    {
        $emoji = '/\s?[\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{2460}-\x{27BF}\x{2900}-\x{2BFF}\x{1F000}-\x{1FFFF}]\x{FE0F}?/u';

        foreach (['roles', 'permissions'] as $table) {
            foreach (DB::table($table)->whereNotNull('description')->get(['id', 'description']) as $row) {
                $clean = trim(preg_replace($emoji, '', $row->description));

                if ($clean !== $row->description) {
                    DB::table($table)->where('id', $row->id)->update(['description' => $clean]);
                }
            }
        }
    }

    public function down(): void
    {
        // Emoji tidak dikembalikan.
    }
};
