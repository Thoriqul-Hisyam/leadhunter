<?php

namespace App\Helpers;

use App\Models\Lead;
use Illuminate\Http\Request;

/**
 * Lead yang dipilih di tabel: centang per baris (lead_ids[]) atau "pilih semua hasil filter"
 * (select_all=1 + parameter filter), supaya aksi massal tidak terbatas pada satu halaman.
 */
class LeadSelection
{
    public const FILTERS = ['search', 'no_website', 'has_email', 'has_phone', 'has_website', 'stage', 'niche', 'city', 'min_score'];

    public const MAX = 2000;

    /**
     * @return array<int, int>
     */
    public static function ids(Request $request): array
    {
        if ($request->boolean('select_all')) {
            return Lead::query()
                ->filter($request->only(self::FILTERS))
                ->orderByDesc('id')
                ->limit(self::MAX)
                ->pluck('id')
                ->all();
        }

        return array_values(array_unique(array_map('intval', (array) $request->input('lead_ids', []))));
    }
}
