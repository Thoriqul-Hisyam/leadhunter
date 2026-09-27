<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\OutreachMessage;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalLeads = Lead::count();
        $totalCampaigns = Campaign::count();

        $statusCounts = OutreachMessage::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $count = fn (string $status) => (int) ($statusCounts[$status] ?? 0);

        $totalOutreach = (int) $statusCounts->sum();
        $replied = $count('replied');
        $delivered = $count('sent') + $replied;
        $failed = $count('failed');
        $pending = $count('pending');
        $queued = $count('queued');

        $stats = [
            'total_leads' => $totalLeads,
            'leads_with_email' => Lead::whereNotNull('email')->where('email', '!=', '')->count(),
            'leads_with_phone' => Lead::whereNotNull('phone')->where('phone', '!=', '')->count(),
            'leads_without_website' => Lead::where(fn ($q) => $q->whereNull('website')->orWhere('website', ''))->count(),
            'total_campaigns' => $totalCampaigns,
            'total_outreach' => $totalOutreach,
            'delivered' => $delivered,
            'replied' => $replied,
            'failed' => $failed,
            'pending' => $pending,
            'queued' => $queued,
            // Sent rate: porsi pesan yang sudah terkirim dari semua pesan yang dibuat.
            'sent_rate' => $this->rate($delivered, $totalOutreach),
            // Reply rate: porsi pesan terkirim yang dibalas.
            'reply_rate' => $this->rate($replied, $delivered),
            // Failed rate: porsi percobaan kirim yang gagal.
            'failed_rate' => $this->rate($failed, $delivered + $failed),
        ];

        $channels = collect(['email', 'whatsapp'])->mapWithKeys(function ($type) {
            $delivered = OutreachMessage::where('type', $type)->whereIn('status', ['sent', 'replied'])->count();
            $replied = OutreachMessage::where('type', $type)->where('status', 'replied')->count();

            return [$type => ['delivered' => $delivered, 'replied' => $replied, 'reply_rate' => $this->rate($replied, $delivered)]];
        });

        $chart = $this->dailyChart(30);

        $recentOutreach = OutreachMessage::with(['lead', 'campaign'])->latest('updated_at')->take(6)->get();

        $upcoming = OutreachMessage::with(['lead', 'campaign'])
            ->where('status', 'queued')
            ->orderByRaw('scheduled_at IS NULL, scheduled_at')
            ->take(5)
            ->get();

        $campaignStats = Campaign::withCount([
            'outreachMessages as total_count',
            'outreachMessages as delivered_count' => fn ($q) => $q->whereIn('status', ['sent', 'replied']),
            'outreachMessages as replied_count' => fn ($q) => $q->where('status', 'replied'),
        ])->latest()->take(5)->get();

        $pipeline = Lead::selectRaw('pipeline_stage, COUNT(*) as total')->groupBy('pipeline_stage')->pluck('total', 'pipeline_stage');

        [$templateStats, $modeStats] = $this->abStats();

        return view('dashboard', compact(
            'stats', 'channels', 'chart', 'recentOutreach', 'upcoming', 'campaignStats', 'pipeline', 'templateStats', 'modeStats'
        ) + [
            // Variabel lama, tetap disediakan untuk kompatibilitas view
            'totalLeads' => $totalLeads,
            'totalCampaigns' => $totalCampaigns,
            'outreachSent' => $delivered,
            'replied' => $replied,
        ]);
    }

    /**
     * Data chart harian: lead baru, pesan terkirim, dan balasan.
     */
    protected function dailyChart(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $series = fn ($query, string $column) => $query
            ->where($column, '>=', $from)
            ->selectRaw("DATE({$column}) as d, COUNT(*) as c")
            ->groupBy('d')
            ->pluck('c', 'd');

        $leads = $series(Lead::query(), 'created_at');
        $sent = $series(OutreachMessage::whereIn('status', ['sent', 'replied']), 'sent_at');
        $replies = $series(OutreachMessage::where('status', 'replied'), 'replied_at');

        $labels = $leadData = $sentData = $replyData = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $key = $date->toDateString();
            $labels[] = $date->translatedFormat('d M');
            $leadData[] = (int) ($leads[$key] ?? 0);
            $sentData[] = (int) ($sent[$key] ?? 0);
            $replyData[] = (int) ($replies[$key] ?? 0);
        }

        return ['labels' => $labels, 'leads' => $leadData, 'sent' => $sentData, 'replies' => $replyData];
    }

    /**
     * A/B: bandingkan reply rate per template dan per mode (AI vs template vs hybrid).
     */
    protected function abStats(): array
    {
        $rows = OutreachMessage::whereIn('status', ['sent', 'replied'])
            ->select('template_id', 'mode', DB::raw('COUNT(*) as delivered'), DB::raw("SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied"))
            ->groupBy('template_id', 'mode')
            ->get();

        $templateNames = MessageTemplate::whereIn('id', $rows->pluck('template_id')->filter())->pluck('name', 'id');

        $templateStats = $rows->whereNotNull('template_id')
            ->groupBy('template_id')
            ->map(function ($group, $templateId) use ($templateNames) {
                $delivered = (int) $group->sum('delivered');
                $replied = (int) $group->sum('replied');

                return [
                    'name' => $templateNames[$templateId] ?? "Template #{$templateId} (dihapus)",
                    'delivered' => $delivered,
                    'replied' => $replied,
                    'reply_rate' => $this->rate($replied, $delivered),
                ];
            })
            ->sortByDesc('reply_rate')
            ->values();

        $modeStats = collect(['ai' => 'AI Generate', 'template' => 'Template', 'hybrid' => 'Hybrid (Template + AI)'])
            ->map(function ($label, $mode) use ($rows) {
                $group = $rows->where('mode', $mode);
                $delivered = (int) $group->sum('delivered');
                $replied = (int) $group->sum('replied');

                return ['label' => $label, 'delivered' => $delivered, 'replied' => $replied, 'reply_rate' => $this->rate($replied, $delivered)];
            });

        return [$templateStats, $modeStats];
    }

    protected function rate(int $part, int $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }
}
