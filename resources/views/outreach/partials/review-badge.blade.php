@if($message->needs_review && ! $message->isDelivered())
    @php $problems = app(\App\Services\MessageQualityGate::class)->problems((string) $message->message, $message->subject, $message->lead, $message->type); @endphp
    <span class="inline-flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded border whitespace-nowrap bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20"
          title="Tidak ikut antrean kirim massal sampai pesan diedit & disimpan.{{ $problems ? ' '.implode('; ', $problems) : '' }}">
        <x-icon name="warning" class="w-3 h-3" /> Perlu review
    </span>
@endif
