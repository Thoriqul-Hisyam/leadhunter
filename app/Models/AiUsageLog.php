<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'success' => 'boolean',
        'duration_ms' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];

    public const FEATURES = [
        'outreach' => 'Pesan outreach',
        'followup' => 'Follow-up',
        'polish' => 'Rapikan draft',
        'template' => 'Template',
        'classify' => 'Klasifikasi balasan',
        'match' => 'Smart matching',
        'test' => 'Tes koneksi',
        'other' => 'Lainnya',
    ];

    /**
     * Catat satu panggilan. Pencatatan tidak boleh menggagalkan proses generate.
     */
    public static function record(array $attributes): void
    {
        try {
            static::create($attributes + ['created_at' => now()]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Ringkasan per hari untuk N hari terakhir (hari tanpa panggilan tetap ditampilkan).
     *
     * @return Collection<int, array{date: \Illuminate\Support\Carbon, calls: int, failed: int, backup: int, avg_seconds: float, tokens: int}>
     */
    public static function dailySummary(int $days = 7): Collection
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = static::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as calls')
            ->selectRaw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed')
            ->selectRaw("SUM(CASE WHEN provider = 'backup' THEN 1 ELSE 0 END) as backup")
            ->selectRaw('AVG(CASE WHEN success = 1 THEN duration_ms END) as avg_ms')
            ->selectRaw('SUM(COALESCE(total_tokens, 0)) as tokens')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        return collect(range($days - 1, 0))->map(function ($ago) use ($rows) {
            $date = now()->subDays($ago)->startOfDay();
            $row = $rows[$date->toDateString()] ?? null;

            return [
                'date' => $date,
                'calls' => (int) ($row->calls ?? 0),
                'failed' => (int) ($row->failed ?? 0),
                'backup' => (int) ($row->backup ?? 0),
                'avg_seconds' => round(((float) ($row->avg_ms ?? 0)) / 1000, 1),
                'tokens' => (int) ($row->tokens ?? 0),
            ];
        });
    }

    /**
     * Ringkasan per fitur untuk N hari terakhir.
     */
    public static function featureSummary(int $days = 7): Collection
    {
        return static::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->select('feature', DB::raw('COUNT(*) as calls'), DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed'))
            ->selectRaw('AVG(CASE WHEN success = 1 THEN duration_ms END) as avg_ms')
            ->selectRaw('SUM(COALESCE(total_tokens, 0)) as tokens')
            ->groupBy('feature')
            ->orderByDesc('calls')
            ->get()
            ->map(fn ($row) => [
                'label' => self::FEATURES[$row->feature] ?? $row->feature,
                'calls' => (int) $row->calls,
                'failed' => (int) $row->failed,
                'avg_seconds' => round(((float) $row->avg_ms) / 1000, 1),
                'tokens' => (int) $row->tokens,
            ]);
    }
}
