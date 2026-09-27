@extends('layouts.app')

@section('title', 'Dashboard — Sandesa')

@section('content')
<style>
    /* Warna chart (palet kategori tervalidasi, slot 1–3) dan tinta teks, terang & gelap */
    .viz-root {
        --viz-series-1: #2a78d6;
        --viz-series-2: #eb6834;
        --viz-series-3: #1baf7a;
        --viz-text: #52514e;
        --viz-grid: #eef0f3;
        --viz-tooltip: #0f172a;
    }
    :root[data-theme="dark"] .viz-root {
        --viz-series-1: #3987e5;
        --viz-series-2: #d95926;
        --viz-series-3: #199e70;
        --viz-text: #c3c2b7;
        --viz-grid: #262833;
        --viz-tooltip: #000000;
    }
    .kpi-label { font-size: 0.75rem; font-weight: 700; color: #64748b; }
    .kpi-value { font-size: 1.875rem; font-weight: 800; letter-spacing: -0.025em; color: #0f172a; line-height: 1.1; }
    [data-theme="dark"] .kpi-value { color: #f1f5f9; }
    .legend-dot { width: 10px; height: 10px; border-radius: 9999px; display: inline-block; }
</style>

<div class="viz-root grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12">

    {{-- ===== KPI ROW ===== --}}
    <div class="lg:col-span-12 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="dash-card py-5">
            <div class="kpi-label">Total Leads</div>
            <div class="kpi-value mt-2">{{ number_format($stats['total_leads']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                {{ number_format($stats['leads_with_email']) }} punya email · {{ number_format($stats['leads_without_website']) }} tanpa website
            </div>
        </div>
        <div class="dash-card py-5">
            <div class="kpi-label">Outreach Terkirim</div>
            <div class="kpi-value mt-2">{{ number_format($stats['delivered']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                Sent rate <strong class="text-slate-700 dark:text-slate-200">{{ $stats['sent_rate'] }}%</strong> dari {{ number_format($stats['total_outreach']) }} pesan
            </div>
        </div>
        <div class="dash-card py-5">
            <div class="kpi-label">Reply Rate</div>
            <div class="kpi-value mt-2">{{ $stats['reply_rate'] }}%</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                {{ number_format($stats['replied']) }} balasan dari {{ number_format($stats['delivered']) }} terkirim
            </div>
        </div>
        <div class="dash-card py-5">
            <div class="kpi-label">Gagal Kirim</div>
            <div class="kpi-value mt-2">{{ $stats['failed_rate'] }}%</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                {{ number_format($stats['failed']) }} gagal · {{ number_format($stats['pending']) }} draft · {{ number_format($stats['queued']) }} antrean
            </div>
        </div>
    </div>

    {{-- ===== LEFT COLUMN ===== --}}
    <div class="lg:col-span-4 flex flex-col gap-6">

        {{-- Quick Actions --}}
        <div class="dash-card">
            <h3 class="font-bold text-slate-900 dark:text-white text-base mb-4">Aksi Cepat</h3>
            <div class="grid grid-cols-2 gap-3">
                @can('manage_campaigns')
                <a href="{{ route('campaigns.create') }}" class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-indigo-100 hover:bg-indigo-50/50 dark:hover:bg-indigo-500/10 transition">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-tight">Buat<br>Campaign</span>
                </a>
                @endcan
                @can('manage_leads')
                <a href="{{ route('leads.index') }}" class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-sky-100 hover:bg-sky-50/50 dark:hover:bg-sky-500/10 transition">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-500/10 flex items-center justify-center text-sky-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-tight">Cari<br>Leads</span>
                </a>
                @endcan
                @can('manage_templates')
                <a href="{{ route('templates.create') }}" class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-emerald-100 hover:bg-emerald-50/50 dark:hover:bg-emerald-500/10 transition">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-tight">Buat<br>Template</span>
                </a>
                @endcan
                @can('manage_leads')
                <a href="{{ route('pipeline.index') }}" class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-rose-100 hover:bg-rose-50/50 dark:hover:bg-rose-500/10 transition">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center text-rose-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-tight">Lihat<br>Pipeline</span>
                </a>
                @endcan
            </div>
        </div>

        {{-- Channels --}}
        <div class="dash-card">
            <h3 class="font-bold text-slate-900 dark:text-white text-base">Per Channel</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-5">Pesan terkirim & reply rate.</p>
            @foreach(['email' => 'Email', 'whatsapp' => 'WhatsApp'] as $type => $label)
                @php $c = $channels[$type]; @endphp
                <div class="mb-4 last:mb-0">
                    <div class="flex justify-between items-baseline mb-1.5">
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $label }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><strong class="text-slate-800 dark:text-slate-100">{{ $c['reply_rate'] }}%</strong> reply · {{ number_format($c['delivered']) }} terkirim</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden" role="img" aria-label="Reply rate {{ $label }} {{ $c['reply_rate'] }}%">
                        <div class="h-full rounded-full" style="width: {{ min(100, $c['reply_rate']) }}%; background: var(--viz-series-1);"></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pipeline summary --}}
        <div class="dash-card flex-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">Pipeline</h3>
                @can('manage_leads')<a href="{{ route('pipeline.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Buka <x-icon name="arrow-right" class="w-3 h-3" /></a>@endcan
            </div>
            @php $pipelineMax = max(1, $pipeline->max() ?? 1); @endphp
            <div class="space-y-3">
                @foreach(\App\Models\Lead::STAGES as $stage => $label)
                    @php $n = (int) ($pipeline[$stage] ?? 0); @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $label }}</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($n) }}</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ round($n / $pipelineMax * 100) }}%; background: var(--viz-series-1);"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== RIGHT COLUMN ===== --}}
    <div class="lg:col-span-8 flex flex-col gap-6">

        {{-- Outreach chart --}}
        <div class="dash-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">Outreach 30 Hari Terakhir</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pesan terkirim dan balasan per hari.</p>
                </div>
                <div class="flex gap-4 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <span class="flex items-center gap-1.5"><span class="legend-dot" style="background: var(--viz-series-1)"></span> Terkirim ({{ array_sum($chart['sent']) }})</span>
                    <span class="flex items-center gap-1.5"><span class="legend-dot" style="background: var(--viz-series-2)"></span> Dibalas ({{ array_sum($chart['replies']) }})</span>
                </div>
            </div>
            <div class="relative h-64">
                <canvas id="outreachChart" role="img" aria-label="Grafik pesan terkirim dan dibalas per hari selama 30 hari"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Leads chart --}}
            <div class="dash-card">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">Lead Baru per Hari</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-4">{{ number_format(array_sum($chart['leads'])) }} lead dalam 30 hari.</p>
                <div class="relative h-40">
                    <canvas id="leadsChart" role="img" aria-label="Grafik lead baru per hari selama 30 hari"></canvas>
                </div>
            </div>

            {{-- Upcoming queue --}}
            <div class="dash-card">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">Antrean Kirim</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-4">Email berikutnya (maks. {{ config('leadhunter.sending.hourly_limit') }}/jam).</p>
                <div class="space-y-2.5">
                    @forelse($upcoming as $msg)
                        <div class="flex items-center justify-between gap-3 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">{{ $msg->lead?->business_name }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ $msg->campaign?->name }}</div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $msg->scheduled_at ? $msg->scheduled_at->format('d M H:i') : 'diproses' }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Antrean kosong. Pilih pesan di halaman Outreach, lalu jalankan aksi "Kirim Email via Antrean".</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Campaign performance --}}
        <div class="dash-card">
            <h3 class="font-bold text-slate-900 dark:text-white text-base mb-4">Performa Campaign Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-[10px] uppercase tracking-wider text-slate-400">
                            <th class="pb-2 font-bold">Campaign</th>
                            <th class="pb-2 font-bold text-right">Pesan</th>
                            <th class="pb-2 font-bold text-right">Terkirim</th>
                            <th class="pb-2 font-bold text-right">Dibalas</th>
                            <th class="pb-2 font-bold text-right">Reply rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($campaignStats as $c)
                            <tr>
                                <td class="py-2.5">
                                    @can('manage_campaigns')
                                        <a href="{{ route('campaigns.show', $c) }}" class="font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600">{{ $c->name }}</a>
                                    @else
                                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ $c->name }}</span>
                                    @endcan
                                    <div class="text-[10px] text-slate-400">{{ $c->niche }} · {{ $c->location }}</div>
                                </td>
                                <td class="py-2.5 text-right text-slate-600 dark:text-slate-300">{{ $c->total_count }}</td>
                                <td class="py-2.5 text-right text-slate-600 dark:text-slate-300">{{ $c->delivered_count }}</td>
                                <td class="py-2.5 text-right text-slate-600 dark:text-slate-300">{{ $c->replied_count }}</td>
                                <td class="py-2.5 text-right font-bold text-slate-800 dark:text-slate-100">{{ $c->delivered_count ? round($c->replied_count / $c->delivered_count * 100, 1) : 0 }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada campaign.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- A/B insight --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="dash-card">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">Reply Rate per Mode</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-4">AI vs template vs hybrid (dari pesan terkirim).</p>
                <div class="space-y-3">
                    @foreach($modeStats as $m)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $m['label'] }}</span>
                                <span class="text-slate-500 dark:text-slate-400"><strong class="text-slate-800 dark:text-slate-100">{{ $m['reply_rate'] }}%</strong> · {{ $m['replied'] }}/{{ $m['delivered'] }}</span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ min(100, $m['reply_rate']) }}%; background: var(--viz-series-1);"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="dash-card">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">Reply Rate per Template</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-4">Template dengan balasan terbanyak di atas.</p>
                <div class="space-y-2.5">
                    @forelse($templateStats->take(6) as $t)
                        <div class="flex justify-between items-center gap-3 text-xs">
                            <span class="font-semibold text-slate-700 dark:text-slate-200 truncate" title="{{ $t['name'] }}">{{ $t['name'] }}</span>
                            <span class="whitespace-nowrap text-slate-500 dark:text-slate-400"><strong class="text-slate-800 dark:text-slate-100">{{ $t['reply_rate'] }}%</strong> · {{ $t['replied'] }}/{{ $t['delivered'] }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada pesan terkirim dari template.</p>
                    @endforelse
                </div>
                @if($templateStats->isNotEmpty() && $templateStats->max('delivered') < 30)
                    <p class="text-[10px] text-slate-400 mt-3">Sampel masih kecil (&lt; 30 pesan per template); perbedaan persentase belum bisa dijadikan patokan.</p>
                @endif
            </div>
        </div>

        {{-- Recent activity --}}
        <div class="dash-card">
            <h3 class="font-bold text-slate-900 dark:text-white text-base mb-4">Aktivitas Outreach Terbaru</h3>
            <div class="space-y-2.5">
                @forelse($recentOutreach as $msg)
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">{{ $msg->lead?->business_name }}</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ $msg->type === 'whatsapp' ? 'WhatsApp' : 'Email' }} · {{ $msg->campaign?->name }}</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="badge badge-{{ $msg->status }} px-2.5 py-1 text-[10px]">{{ ucfirst($msg->status) }}</span>
                            <span class="text-[10px] text-slate-400 w-20 text-right">{{ $msg->updated_at->diffForHumans(short: true) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Belum ada aktivitas.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const chartData = @js($chart);
    const root = document.querySelector('.viz-root');
    const css = (name) => getComputedStyle(root).getPropertyValue(name).trim();

    const axis = () => ({
        grid: { color: css('--viz-grid'), drawTicks: false },
        border: { display: false },
        ticks: { color: css('--viz-text'), font: { family: 'Inter', size: 11 }, padding: 8, precision: 0 },
    });

    const tooltip = () => ({
        backgroundColor: css('--viz-tooltip'),
        padding: 10,
        cornerRadius: 8,
        titleFont: { family: 'Inter', size: 12 },
        bodyFont: { family: 'Inter', size: 12 },
        boxPadding: 4,
    });

    const outreach = new Chart(document.getElementById('outreachChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Terkirim', data: chartData.sent, borderColor: css('--viz-series-1'), backgroundColor: css('--viz-series-1') },
                { label: 'Dibalas', data: chartData.replies, borderColor: css('--viz-series-2'), backgroundColor: css('--viz-series-2') },
            ].map(ds => ({ ...ds, borderWidth: 2, tension: 0.3, pointRadius: 0, pointHoverRadius: 5, pointHitRadius: 12 })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: tooltip() },
            scales: { x: { ...axis(), ticks: { ...axis().ticks, maxTicksLimit: 8 } }, y: { ...axis(), beginAtZero: true } },
        },
    });

    const leads = new Chart(document.getElementById('leadsChart'), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{ label: 'Lead baru', data: chartData.leads, backgroundColor: css('--viz-series-3'), borderRadius: 4, borderSkipped: 'bottom', barPercentage: 0.8, categoryPercentage: 0.9 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: tooltip() },
            scales: { x: { ...axis(), grid: { display: false }, ticks: { ...axis().ticks, maxTicksLimit: 6 } }, y: { ...axis(), beginAtZero: true } },
        },
    });

    // Warna mengikuti toggle tema terang/gelap
    new MutationObserver(() => {
        [outreach, leads].forEach(chart => {
            ['x', 'y'].forEach(k => {
                chart.options.scales[k].ticks.color = css('--viz-text');
                if (chart.options.scales[k].grid.display !== false) chart.options.scales[k].grid.color = css('--viz-grid');
            });
            chart.options.plugins.tooltip.backgroundColor = css('--viz-tooltip');
        });
        outreach.data.datasets[0].borderColor = outreach.data.datasets[0].backgroundColor = css('--viz-series-1');
        outreach.data.datasets[1].borderColor = outreach.data.datasets[1].backgroundColor = css('--viz-series-2');
        leads.data.datasets[0].backgroundColor = css('--viz-series-3');
        outreach.update();
        leads.update();
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
});
</script>
@endsection
