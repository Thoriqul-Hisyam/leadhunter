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
            $table->string('category')->nullable()->after('niche');
            $table->decimal('rating', 2, 1)->nullable()->after('source');
            $table->unsignedInteger('reviews_count')->nullable()->after('rating');
            $table->text('google_maps_url')->nullable()->after('reviews_count');
        });

        $this->mergeDuplicateLeads();

        Schema::table('leads', function (Blueprint $table) {
            $table->unique(['business_name', 'city'], 'leads_business_city_unique');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique('leads_business_city_unique');
            $table->dropColumn(['category', 'rating', 'reviews_count', 'google_maps_url']);
        });
    }

    /**
     * Gabungkan lead duplikat (nama bisnis + kota sama) ke lead dengan ID terkecil,
     * pindahkan pesan outreach-nya, dan isi kontak yang kosong dari duplikatnya.
     */
    protected function mergeDuplicateLeads(): void
    {
        $groups = DB::table('leads')
            ->select('business_name', 'city', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('business_name', 'city')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $keeper = DB::table('leads')->find($group->keep_id);
            $duplicates = DB::table('leads')
                ->where('business_name', $group->business_name)
                ->where('city', $group->city)
                ->where('id', '!=', $group->keep_id)
                ->get();

            $fill = [];
            foreach (['website', 'email', 'phone', 'address', 'niche'] as $field) {
                if (empty($keeper->{$field})) {
                    $value = $duplicates->pluck($field)->filter()->first();
                    if ($value) {
                        $fill[$field] = $value;
                    }
                }
            }
            if ($fill) {
                DB::table('leads')->where('id', $keeper->id)->update($fill);
            }

            $ids = $duplicates->pluck('id');
            DB::table('outreach_messages')->whereIn('lead_id', $ids)->update(['lead_id' => $keeper->id]);
            DB::table('leads')->whereIn('id', $ids)->delete();
        }
    }
};
