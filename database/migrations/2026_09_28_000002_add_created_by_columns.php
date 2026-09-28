<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catat siapa pembuat data sejak sekarang, sebagai persiapan mode tim (roadmap Fase 10).
 */
return new class extends Migration
{
    protected array $tables = ['leads', 'campaigns', 'outreach_messages', 'message_templates'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('created_by');
            });
        }
    }
};
