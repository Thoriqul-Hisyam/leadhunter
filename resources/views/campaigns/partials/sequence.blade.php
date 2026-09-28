@php
    $stepRows = old('steps', $campaign->steps->map(fn ($s) => ['channel' => $s->channel, 'delay_days' => $s->delay_days, 'auto_queue' => $s->auto_queue])->all());
    $maxFollowups = \App\Models\CampaignStep::MAX_STEP - 1;
    $statLine = function (int $step) use ($stepStats) {
        $s = $stepStats[$step] ?? null;
        if (! $s) {
            return 'Belum ada pesan';
        }
        $rate = $s['sent'] ? round($s['replied'] / $s['sent'] * 100) : 0;

        return "{$s['total']} dibuat · {$s['sent']} terkirim · {$s['replied']} dibalas ({$rate}%)";
    };
@endphp

<div class="glass-card p-5 space-y-4" id="sequence">
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-icon name="arrow-path" class="w-4 h-4 text-indigo-500" /> Sequence follow-up
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">
                Langkah lanjutan dibuat otomatis untuk lead yang belum membalas, dihitung dari waktu langkah sebelumnya terkirim.
                Sequence berhenti sendiri begitu lead membalas di kanal mana pun, minta berhenti, atau stage-nya dipindah ke Membalas/Meeting/Deal/Batal.
            </p>
        </div>
        <button type="button" id="sequence-preset" class="btn-secondary py-1.5 px-3 text-[11px] font-bold shrink-0">Contoh: Email → WA → penutup</button>
    </div>

    <form action="{{ route('campaigns.sequence', $campaign) }}" method="POST" class="space-y-2" id="sequence-form">
        @csrf
        @method('PUT')

        <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/60 dark:bg-slate-900/40">
            <span class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-black flex items-center justify-center shrink-0">1</span>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-slate-800 dark:text-slate-100">Pesan pembuka</div>
                <div class="text-[10px] text-slate-500">{{ $statLine(1) }}</div>
            </div>
        </div>

        <div id="sequence-steps" class="space-y-2">
            @foreach($stepRows as $i => $row)
                <div class="sequence-step flex flex-wrap items-center gap-3 p-3 rounded-xl border border-slate-200/70 dark:border-slate-800/70">
                    <span class="step-number w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-black flex items-center justify-center shrink-0">{{ $i + 2 }}</span>
                    <label class="text-[11px] text-slate-500">Setelah</label>
                    <input type="number" min="1" max="60" required name="steps[{{ $i }}][delay_days]" value="{{ $row['delay_days'] }}" class="form-input text-xs w-16 py-1.5">
                    <span class="text-[11px] text-slate-500">hari, kirim lewat</span>
                    <select name="steps[{{ $i }}][channel]" class="form-input text-xs w-40 py-1.5">
                        @foreach(\App\Models\CampaignStep::CHANNELS as $value => $label)
                            <option value="{{ $value }}" @selected(($row['channel'] ?? 'same') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300 cursor-pointer" title="Tanpa review: pesan langsung masuk antrean kirim (tetap mengikuti jam & batas kirim)">
                        <input type="checkbox" name="steps[{{ $i }}][auto_queue]" value="1" class="form-checkbox rounded" @checked(! empty($row['auto_queue']))> Langsung antrekan
                    </label>
                    <span class="step-stats text-[10px] text-slate-400 sm:ml-auto">{{ $statLine($i + 2) }}</span>
                    <button type="button" class="remove-step p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-500/10 transition" title="Hapus langkah">
                        <x-icon name="x-mark" class="w-4 h-4" />
                    </button>
                </div>
            @endforeach
        </div>

        <p id="sequence-empty" class="{{ count($stepRows) ? 'hidden' : '' }} text-[11px] text-slate-500 dark:text-slate-400 px-1">
            Belum ada langkah lanjutan. Campaign ini memakai follow-up default dari Pengaturan:
            @if(\App\Models\Setting::followupEnabled())
                <strong>1 follow-up setelah {{ \App\Models\Setting::followupDays() }} hari</strong> (kanal sama, menunggu review).
            @else
                <strong>nonaktif</strong>.
            @endif
        </p>

        <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
            <button type="button" id="add-step" class="btn-secondary py-1.5 px-3 text-[11px] font-bold"><x-icon name="plus" class="w-3.5 h-3.5" /> Tambah langkah</button>
            <div class="flex items-center gap-3">
                <span class="text-[10px] text-slate-400">Maks. {{ $maxFollowups }} langkah lanjutan. Tanpa "Langsung antrekan", langkah dibuat sebagai Draft untuk direview dulu.</span>
                <button type="submit" class="btn-primary py-2 px-4 text-xs font-bold">Simpan sequence</button>
            </div>
        </div>
    </form>
</div>

<template id="sequence-step-template">
    <div class="sequence-step flex flex-wrap items-center gap-3 p-3 rounded-xl border border-slate-200/70 dark:border-slate-800/70">
        <span class="step-number w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-black flex items-center justify-center shrink-0"></span>
        <label class="text-[11px] text-slate-500">Setelah</label>
        <input type="number" min="1" max="60" required data-field="delay_days" value="3" class="form-input text-xs w-16 py-1.5">
        <span class="text-[11px] text-slate-500">hari, kirim lewat</span>
        <select data-field="channel" class="form-input text-xs w-40 py-1.5">
            @foreach(\App\Models\CampaignStep::CHANNELS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300 cursor-pointer">
            <input type="checkbox" data-field="auto_queue" value="1" class="form-checkbox rounded"> Langsung antrekan
        </label>
        <span class="step-stats text-[10px] text-slate-400 sm:ml-auto">Belum ada pesan</span>
        <button type="button" class="remove-step p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-500/10 transition" title="Hapus langkah">
            <x-icon name="x-mark" class="w-4 h-4" />
        </button>
    </div>
</template>

<script>
(function () {
    const list = document.getElementById('sequence-steps');
    const template = document.getElementById('sequence-step-template');
    const empty = document.getElementById('sequence-empty');
    const addBtn = document.getElementById('add-step');
    const max = {{ $maxFollowups }};

    // Nama field mengikuti urutan baris, supaya langkah yang dihapus tidak meninggalkan celah.
    function renumber() {
        list.querySelectorAll('.sequence-step').forEach((row, i) => {
            row.querySelector('.step-number').textContent = i + 2;
            row.querySelectorAll('input, select').forEach(el => {
                const field = el.dataset.field || el.name.replace(/^steps\[\d+\]\[(\w+)\]$/, '$1');
                el.dataset.field = field;
                el.name = `steps[${i}][${field}]`;
            });
        });
        const count = list.querySelectorAll('.sequence-step').length;
        empty.classList.toggle('hidden', count > 0);
        addBtn.disabled = count >= max;
        addBtn.classList.toggle('opacity-50', count >= max);
    }

    function addStep(values = {}) {
        if (list.querySelectorAll('.sequence-step').length >= max) return;
        const row = template.content.firstElementChild.cloneNode(true);
        if (values.delay_days) row.querySelector('[data-field=delay_days]').value = values.delay_days;
        if (values.channel) row.querySelector('[data-field=channel]').value = values.channel;
        row.querySelector('[data-field=auto_queue]').checked = !!values.auto_queue;
        list.appendChild(row);
        renumber();
    }

    addBtn.addEventListener('click', () => addStep());
    list.addEventListener('click', e => {
        const btn = e.target.closest('.remove-step');
        if (!btn) return;
        btn.closest('.sequence-step').remove();
        renumber();
    });

    document.getElementById('sequence-preset').addEventListener('click', () => {
        list.innerHTML = '';
        addStep({ delay_days: 3, channel: 'whatsapp' });
        addStep({ delay_days: 4, channel: 'email' });
    });

    renumber();
})();
</script>
