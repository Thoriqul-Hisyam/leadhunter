@extends('layouts.app')

@section('title', 'Pipeline — Sandesa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Pipeline Lead</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Stage "Dihubungi" dan "Membalas" terisi otomatis saat pesan terkirim atau dibalas; stage lain diatur manual.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari lead..." class="form-input text-xs w-56">
            <button type="submit" class="btn-secondary py-1.5 px-4 text-xs">Cari</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert-success"><span>{{ session('success') }}</span></div>
    @endif

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($columns as $stage => $column)
            <div class="w-72 shrink-0 flex flex-col rounded-2xl bg-white/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-800/70 max-h-[75vh]">
                <div class="px-4 py-3 border-b border-slate-200/70 dark:border-slate-800/70 flex items-center justify-between">
                    @include('leads.partials.stage-badge', ['stage' => $stage])
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ number_format($column['total']) }}</span>
                </div>
                <div class="p-3 space-y-2.5 overflow-y-auto flex-1">
                    @forelse($column['leads'] as $lead)
                        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs">
                            <a href="{{ route('leads.show', $lead) }}" class="text-xs font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 block truncate">{{ $lead->business_name }}</a>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ $lead->niche }} · {{ $lead->city }}</div>
                            <div class="flex items-center justify-between mt-2 gap-2">
                                <div class="flex items-center gap-1.5 text-[9px] font-bold text-slate-400">
                                    @if($lead->email)<span title="Punya email"><x-icon name="envelope" class="w-3.5 h-3.5" /></span>@endif
                                    @if($lead->phone)<span title="Punya telepon"><x-icon name="phone" class="w-3.5 h-3.5" /></span>@endif
                                    @if($lead->outreach_messages_count)<span title="Jumlah pesan outreach" class="inline-flex items-center gap-0.5"><x-icon name="send" class="w-3.5 h-3.5" />{{ $lead->outreach_messages_count }}</span>@endif
                                    @if($lead->notes_count)<span title="Jumlah catatan" class="inline-flex items-center gap-0.5"><x-icon name="note" class="w-3.5 h-3.5" />{{ $lead->notes_count }}</span>@endif
                                </div>
                                <form action="{{ route('leads.stage', $lead) }}" method="POST">
                                    @csrf
                                    <select name="pipeline_stage" onchange="this.form.submit()" class="text-[10px] font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md px-1.5 py-0.5 text-slate-600 dark:text-slate-300" aria-label="Pindahkan stage">
                                        @foreach($stages as $value => $label)
                                            <option value="{{ $value }}" @selected($value === $stage)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 text-center py-6">Kosong</p>
                    @endforelse
                    @if($column['total'] > $perStage)
                        <a href="{{ route('leads.index', ['stage' => $stage]) }}" class="flex items-center justify-center gap-1 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline py-1">Lihat semua {{ number_format($column['total']) }} <x-icon name="arrow-right" class="w-3 h-3" /></a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
