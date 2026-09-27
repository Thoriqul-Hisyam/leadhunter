<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Progres generate pesan AI yang berjalan di queue.
            $table->unsignedInteger('generation_total')->default(0)->after('location');
            $table->unsignedInteger('generation_done')->default(0)->after('generation_total');
            $table->unsignedInteger('generation_failed')->default(0)->after('generation_done');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['generation_total', 'generation_done', 'generation_failed']);
        });
    }
};
