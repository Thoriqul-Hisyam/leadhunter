<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\BlacklistEntry;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\OutreachMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan 7 hari terakhir untuk email laporan mingguan.
 */
class WeeklyReport
{
    public function build(?Carbon $until = null): array
    {
        $until ??= now();
        $from = $until->copy()->subDays(7);
        $period = fn ($query, string $column = 'created_at') => $query->whereBetween($column, [$from, $until]);

        $sent = fn (string $type) => $period(OutreachMessage::where('type', $type)->whereIn('status', ['sent', 'replied']), 'sent_at')->count();
        $delivered = $sent('email') + $sent('whatsapp');
        $replied = $period(OutreachMessage::where('status', 'replied'), 'replied_at')->count();

        $categories = $period(OutreachMessage::whereNotNull('reply_category'), 'updated_at')
            ->selectRaw('reply_category, COUNT(*) as total')
            ->groupBy('reply_category')
            ->pluck('total', 'reply_category');

        $topCampaigns = Campaign::withCount([
            'outreachMessages as delivered_count' => fn ($q) => $period($q->whereIn('status', ['sent', 'replied']), 'sent_at'),
            'outreachMessages as replied_count' => fn ($q) => $period($q->where('status', 'replied'), 'replied_at'),
        ])
            ->get()
            ->filter(fn ($c) => $c->delivered_count > 0 || $c->replied_count > 0)
            ->sortByDesc(fn ($c) => [$c->replied_count, $c->delivered_count])
            ->take(3)
            ->values();

        $ai = $period(AiUsageLog::query())
            ->selectRaw('COUNT(*) as calls, SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed, AVG(CASE WHEN success = 1 THEN duration_ms END) as avg_ms')
            ->first();

        return [
            'from' => $from,
            'until' => $until,
            'leads' => [
                'new' => $period(Lead::query())->count(),
                'hot' => $period(Lead::where('score', '>=', Lead::HOT_SCORE))->count(),
                'total' => Lead::count(),
            ],
            'outreach' => [
                'email' => $sent('email'),
                'whatsapp' => $sent('whatsapp'),
                'replied' => $replied,
                'reply_rate' => $delivered ? round($replied / $delivered * 100, 1) : 0.0,
                'bounced' => $period(BlacklistEntry::where('reason', 'bounce'))->count(),
                'unsubscribed' => $period(BlacklistEntry::where('reason', 'like', 'unsubscribe%'))->count(),
            ],
            'categories' => $categories,
            'campaigns' => $topCampaigns,
            'todo' => [
                'needs_review' => OutreachMessage::where('status', 'pending')->where('needs_review', true)->count(),
                'pending' => OutreachMessage::where('status', 'pending')->count(),
                'hot_uncontacted' => Lead::where('score', '>=', Lead::HOT_SCORE)->where('pipeline_stage', 'new')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
            'ai' => [
                'calls' => (int) ($ai->calls ?? 0),
                'failed' => (int) ($ai->failed ?? 0),
                'avg_seconds' => round(((float) ($ai->avg_ms ?? 0)) / 1000, 1),
            ],
        ];
    }
}
