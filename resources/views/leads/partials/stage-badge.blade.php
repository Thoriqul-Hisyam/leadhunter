@php
    $stage = $stage ?? 'new';
    $classes = [
        'new' => 'bg-slate-500/10 text-slate-600 dark:text-slate-300 border-slate-500/15',
        'contacted' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/15',
        'replied' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/15',
        'meeting' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/15',
        'deal' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/15',
        'lost' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/15',
    ][$stage] ?? 'bg-slate-500/10 text-slate-600 border-slate-500/15';
@endphp
<span class="text-[10px] font-bold px-2 py-0.5 rounded-full border whitespace-nowrap {{ $classes }}">{{ \App\Models\Lead::STAGES[$stage] ?? ucfirst($stage) }}</span>
