<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outreach_messages', function (Blueprint $table) {
            // Sequence: 1 = pesan pembuka, 2+ = langkah lanjutan
            $table->unsignedTinyInteger('step')->default(1)->after('campaign_id');

            // Balasan: kategori hasil klasifikasi & cuplikan isi
            $table->string('reply_category', 30)->nullable()->after('replied_at');
            $table->text('reply_excerpt')->nullable()->after('reply_category');

            // Kualitas pesan AI
            $table->boolean('needs_review')->default(false)->after('mode');
            $table->string('prompt_variant', 30)->nullable()->after('needs_review');
        });
    }

    public function down(): void
    {
        Schema::table('outreach_messages', function (Blueprint $table) {
            $table->dropColumn(['step', 'reply_category', 'reply_excerpt', 'needs_review', 'prompt_variant']);
        });
    }
};
