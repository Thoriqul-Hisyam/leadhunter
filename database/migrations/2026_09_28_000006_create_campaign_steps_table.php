<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Langkah lanjutan sequence per campaign. Langkah 1 = pesan pembuka (dibuat saat generate).
        Schema::create('campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->string('channel', 10)->default('same'); // same | email | whatsapp
            $table->unsignedSmallInteger('delay_days');     // hari setelah langkah sebelumnya terkirim
            $table->boolean('auto_queue')->default(false);  // langsung masuk antrean kirim, tanpa review
            $table->timestamps();

            $table->unique(['campaign_id', 'step']);
        });

        // Follow-up lama dibuat sebelum kolom step ada.
        DB::table('outreach_messages')->whereNotNull('followup_of_id')->where('step', 1)->update(['step' => 2]);
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_steps');
    }
};
