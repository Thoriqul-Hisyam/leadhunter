<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Nilai awal = identitas yang sebelumnya di-hardcode di controller & email.
        $defaults = [
            'sender_name' => 'Thoriq',
            'company_name' => 'Lefateach',
            'company_website' => 'lefateach.com',
            'company_phone' => '0895-3655-00805',
            'company_tagline' => '#1 Jasa Website di Indonesia',
            'default_offer' => 'Jasa Pembuatan Website Profesional',
            'followup_enabled' => '0',
            'followup_days' => '3',
        ];

        $now = now();
        foreach ($defaults as $key => $value) {
            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
