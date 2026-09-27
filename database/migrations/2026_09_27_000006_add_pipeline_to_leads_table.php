<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // new → contacted → replied → meeting → deal (atau lost)
            $table->string('pipeline_stage')->default('new')->after('source');
            $table->index('pipeline_stage');
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // Isi stage awal dari riwayat outreach yang sudah ada.
        DB::table('leads')
            ->whereIn('id', DB::table('outreach_messages')->where('status', 'replied')->select('lead_id'))
            ->update(['pipeline_stage' => 'replied']);

        DB::table('leads')
            ->where('pipeline_stage', 'new')
            ->whereIn('id', DB::table('outreach_messages')->where('status', 'sent')->select('lead_id'))
            ->update(['pipeline_stage' => 'contacted']);
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_notes');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['pipeline_stage']);
            $table->dropColumn('pipeline_stage');
        });
    }
};
