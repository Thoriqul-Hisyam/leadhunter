@php
    $score = (int) $lead->score;
    $tone = $score >= \App\Models\Lead::HOT_SCORE ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
        : ($score >= 50 ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
        : 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/15');
    $hint = collect([
        ! $lead->website ? 'belum punya website' : (\App\Helpers\Url::socialPlatform($lead->website) ? 'hanya '.\App\Helpers\Url::socialPlatform($lead->website) : null),
        $lead->website_score !== null ? 'skor web '.$lead->website_score.'/100' : null,
        $lead->reviews_count ? number_format($lead->reviews_count).' ulasan' : null,
        $lead->phone_is_mobile ? 'ada nomor seluler' : null,
        $lead->email ? 'ada email' : null,
    ])->filter()->implode(', ');
@endphp
<span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border whitespace-nowrap {{ $tone }}" title="Skor prioritas: {{ $hint }}">
    {{ $score }}
    @if($lead->isHot())
        <span class="uppercase tracking-wider">Hot</span>
    @endif
</span>
@if($lead->website_score !== null)
    <div class="text-[9px] text-slate-400 mt-1 whitespace-nowrap" title="Skor performa mobile Google PageSpeed">Web {{ $lead->website_score }}/100{{ $lead->website_https === false ? ' · tanpa HTTPS' : '' }}</div>
@endif
