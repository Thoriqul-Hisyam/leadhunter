{{-- Papan pipeline; dirender ulang lewat AJAX saat mencari (PipelineController, ?partial=1) --}}
<p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-3" id="pipeline-count">
    @if(array_filter($filters))
        {{ number_format($totalFound) }} lead cocok dengan pencarian.
    @else
        {{ number_format($totalFound) }} lead.
    @endif
</p>

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
                        <a href="{{ route('leads.show', $lead) }}" class="text-xs font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 block truncate" title="{{ $lead->business_name }}">{{ $lead->business_name }}</a>
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
                    <p class="text-[11px] text-slate-400 text-center py-6">{{ array_filter($filters) ? 'Tidak ada yang cocok' : 'Kosong' }}</p>
                @endforelse
                @if($column['total'] > $perStage)
                    <a href="{{ route('leads.index', array_filter(['stage' => $stage] + $filters)) }}" class="flex items-center justify-center gap-1 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline py-1">Lihat semua {{ number_format($column['total']) }} <x-icon name="arrow-right" class="w-3 h-3" /></a>
                @endif
            </div>
        </div>
    @endforeach
</div>
