@extends('layouts.app')

@section('title', $lead->business_name . ' — Leads')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('leads.index') }}" class="p-2.5 rounded-xl bg-white border border-slate-200/80 hover:border-slate-300 dark:bg-slate-900 dark:border-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $lead->business_name }}</h2>
                <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-bold px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 uppercase tracking-wider text-[10px]">{{ $lead->niche }}</span>
                    @if($lead->category)<span>{{ $lead->category }}</span><span>•</span>@endif
                    <span>{{ $lead->city }}</span>
                    @if($lead->rating)
                        <span>•</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400 inline-flex items-center gap-1"><x-icon name="star" class="w-3.5 h-3.5" /> {{ number_format($lead->rating, 1) }}</span>
                        @if($lead->reviews_count)<span>({{ number_format($lead->reviews_count) }} ulasan)</span>@endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Stage --}}
        <form action="{{ route('leads.stage', $lead) }}" method="POST" class="flex items-center gap-2">
            @csrf
            <label for="pipeline_stage" class="text-xs font-bold text-slate-500 dark:text-slate-400">Stage</label>
            <select name="pipeline_stage" id="pipeline_stage" onchange="this.form.submit()" class="form-input text-xs py-1.5 w-40">
                @foreach($stages as $value => $label)
                    <option value="{{ $value }}" @selected($lead->pipeline_stage === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if(session('success'))
        <div class="alert-success"><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
        <div class="alert-error"><span>{{ $errors->first() }}</span></div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Info kontak --}}
        <div class="glass-card p-5 space-y-3 lg:col-span-1">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kontak</h3>
            <dl class="space-y-2.5 text-xs">
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Email</dt>
                    <dd class="text-slate-700 dark:text-slate-200">
                        @if($lead->email)<a href="mailto:{{ $lead->email }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $lead->email }}</a>@else — @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Telepon / WA</dt>
                    <dd class="text-slate-700 dark:text-slate-200">
                        @if($lead->phone)
                            {{ $lead->phone }}
                            @if($wa = \App\Helpers\Phone::whatsAppUrl($lead->phone))
                                · <a href="{{ $wa }}" target="_blank" rel="noopener" class="text-emerald-600 dark:text-emerald-400 hover:underline">Buka WhatsApp</a>
                            @endif
                        @else — @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Website</dt>
                    <dd class="text-slate-700 dark:text-slate-200 break-all">
                        @if($lead->website)<a href="{{ \App\Helpers\Url::normalize($lead->website) ?? '#' }}" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $lead->website }}</a>@else <span class="text-rose-500 font-semibold">Belum punya website</span> @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Alamat</dt>
                    <dd class="text-slate-700 dark:text-slate-200">{{ $lead->address ?: '—' }}</dd>
                </div>
                @if($lead->google_maps_url)
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Google Maps</dt>
                        <dd><a href="{{ \App\Helpers\Url::normalize($lead->google_maps_url) ?? '#' }}" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">Lihat di Maps</a></dd>
                    </div>
                @endif
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sumber</dt>
                    <dd class="text-slate-700 dark:text-slate-200">{{ $lead->source }} · ditambahkan {{ $lead->created_at->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Catatan --}}
        <div class="glass-card p-5 lg:col-span-2 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Catatan</h3>

            <form action="{{ route('leads.notes.store', $lead) }}" method="POST" class="space-y-2">
                @csrf
                <textarea name="body" rows="3" required class="form-input text-xs w-full" placeholder="Mis. sudah telepon, minta dikirim portofolio; jadwal meeting Kamis 10:00..."></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary py-1.5 px-4 text-xs font-bold">Simpan Catatan</button>
                </div>
            </form>

            <div class="space-y-3">
                @forelse($lead->notes as $note)
                    <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/60 dark:bg-slate-900/40">
                        <div class="flex justify-between items-start gap-3">
                            <p class="text-xs text-slate-700 dark:text-slate-200 whitespace-pre-wrap">{{ $note->body }}</p>
                            <form action="{{ route('leads.notes.destroy', [$lead, $note]) }}" method="POST" onsubmit="return handleConfirm(event, this, 'Hapus catatan?', 'Catatan ini akan dihapus permanen.', 'Ya, hapus')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[10px] text-slate-400 hover:text-rose-500 font-semibold">Hapus</button>
                            </form>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1.5">{{ $note->user?->name ?? 'Sistem' }} · {{ $note->created_at->format('d M Y H:i') }}</div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Belum ada catatan.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Riwayat outreach --}}
    <div class="glass-card p-5">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Riwayat Outreach</h3>
        <div class="overflow-x-auto">
            <table class="fancy-table min-w-full">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Channel</th>
                        <th style="width: 45%">Pesan</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lead->outreachMessages as $msg)
                        <tr>
                            <td class="text-xs">
                                @if($msg->campaign)
                                    <a href="{{ route('campaigns.show', $msg->campaign) }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ $msg->campaign->name }}</a>
                                @endif
                                @if($msg->followup_of_id)<div class="text-[9px] font-bold text-sky-600 inline-flex items-center gap-1"><x-icon name="reply" class="w-3 h-3" /> Follow-up</div>@endif
                            </td>
                            <td class="text-xs">{{ $msg->type === 'whatsapp' ? 'WhatsApp' : 'Email' }}</td>
                            <td class="text-xs">
                                @if($msg->subject)<div class="font-bold text-slate-800 dark:text-slate-100 mb-1">{{ $msg->subject }}</div>@endif
                                <div class="text-slate-600 dark:text-slate-300 line-clamp-3 whitespace-pre-wrap">{{ $msg->message }}</div>
                            </td>
                            <td><span class="badge badge-{{ $msg->status }} px-2.5 py-1 text-[10px]">{{ ucfirst($msg->status) }}</span></td>
                            <td class="text-[10px] text-slate-500 whitespace-nowrap">
                                @if($msg->replied_at)Dibalas {{ $msg->replied_at->format('d M H:i') }}<br>@endif
                                @if($msg->sent_at)Terkirim {{ $msg->sent_at->format('d M H:i') }}@else Dibuat {{ $msg->created_at->format('d M H:i') }}@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-xs text-slate-400 py-6 text-center">Belum ada outreach untuk lead ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
