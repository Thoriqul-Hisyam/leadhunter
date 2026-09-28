@if($message->reply_category)
    @php
        $tone = match ($message->reply_category) {
            'interested' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            'pricing' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
            'not_interested' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            default => 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/15',
        };
    @endphp
    <span class="inline-flex items-center text-[9px] font-bold px-1.5 py-0.5 rounded border whitespace-nowrap {{ $tone }}"
          title="{{ $message->reply_category === 'auto_reply' ? 'Balasan otomatis tidak dihitung sebagai reply' : 'Kategori balasan' }}{{ $message->reply_excerpt ? ': '.\Illuminate\Support\Str::limit($message->reply_excerpt, 200) : '' }}">
        {{ $message->replyCategoryLabel() }}
    </span>
@endif
