<?php

use App\Helpers\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor telepon yang sudah dinormalisasi (628xxx) untuk mencocokkan balasan WhatsApp,
 * plus penanda nomor seluler (dipakai pengiriman otomatis & skor lead).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('whatsapp_number', 20)->nullable()->after('phone')->index();
            $table->boolean('phone_is_mobile')->default(false)->after('whatsapp_number');
        });

        DB::table('leads')->whereNotNull('phone')->orderBy('id')->chunkById(500, function ($leads) {
            foreach ($leads as $lead) {
                DB::table('leads')->where('id', $lead->id)->update([
                    'whatsapp_number' => Phone::toWhatsApp($lead->phone),
                    'phone_is_mobile' => Phone::isMobile($lead->phone),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_number']);
            $table->dropColumn(['whatsapp_number', 'phone_is_mobile']);
        });
    }
};
