<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('outreach_messages', function (Blueprint $table) {
            $table->string('mode')->default('ai')->after('status'); // template, ai, hybrid
            $table->foreignId('template_id')->nullable()->after('mode')->constrained('message_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outreach_messages', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['mode', 'template_id']);
        });
    }
};
