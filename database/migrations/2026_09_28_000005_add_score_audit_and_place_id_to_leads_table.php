<?php

use App\Models\Lead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->default(0)->after('pipeline_stage')->index();

            // Audit website (Google PageSpeed, mobile)
            $table->unsignedTinyInteger('website_score')->nullable()->after('website');
            $table->boolean('website_https')->nullable()->after('website_score');
            $table->timestamp('website_audited_at')->nullable()->after('website_https');

            // ID tempat Google Maps: dedup cabang dengan nama sama di kota yang sama
            $table->string('place_id')->nullable()->after('google_maps_url');
        });

        // Isi place_id dari URL Google Maps yang sudah tersimpan
        $seen = [];
        DB::table('leads')->whereNotNull('google_maps_url')->orderBy('id')->chunkById(500, function ($leads) use (&$seen) {
            foreach ($leads as $lead) {
                $placeId = Lead::placeIdFromUrl($lead->google_maps_url);
                if ($placeId && ! isset($seen[$placeId])) {
                    $seen[$placeId] = true;
                    DB::table('leads')->where('id', $lead->id)->update(['place_id' => $placeId]);
                }
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique('leads_business_city_unique');
            $table->index(['business_name', 'city'], 'leads_business_city_index');
            $table->unique('place_id');
        });

        // Hitung skor awal
        Lead::query()->orderBy('id')->chunkById(200, function ($leads) {
            foreach ($leads as $lead) {
                DB::table('leads')->where('id', $lead->id)->update(['score' => $lead->computeScore()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique(['place_id']);
            $table->dropIndex('leads_business_city_index');
            $table->dropIndex(['score']);
            $table->dropColumn(['score', 'website_score', 'website_https', 'website_audited_at', 'place_id']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->unique(['business_name', 'city'], 'leads_business_city_unique');
        });
    }
};
