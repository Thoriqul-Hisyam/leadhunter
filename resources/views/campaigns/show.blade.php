@extends('layouts.app')

@section('title', $campaign->name . ' — Campaign')
@section('header', 'Detail Campaign')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    
    {{-- Back & Action Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('campaigns.index') }}" class="p-2.5 rounded-xl bg-white border border-slate-200/80 hover:border-slate-300 dark:bg-slate-900 dark:border-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $campaign->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 uppercase tracking-wider">{{ $campaign->niche }}</span>
                    <span class="text-xs text-slate-400 dark:text-slate-500">•</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        {{ $campaign->location }}
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500">•</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Dibuat {{ $campaign->created_at->translatedFormat('d M Y') }}</span>
                </div>
            </div>
        </div>
        
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('campaigns.edit', $campaign) }}" class="btn-secondary py-2.5 px-4 text-xs font-bold rounded-xl justify-center flex-1 sm:flex-initial">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-2.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span>Edit Campaign</span>
            </a>
            <div class="flex flex-col sm:flex-row gap-3">
                <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" onsubmit="return handleConfirm(event, this, 'Hapus Campaign?', 'Yakin ingin menghapus campaign ini? Semua pesan outreach di dalamnya juga akan terhapus permanen.', 'Ya, Hapus')" class="flex-1 sm:flex-initial">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary w-full justify-center text-rose-600 hover:bg-rose-50 hover:border-rose-200 py-2.5 px-4 text-xs font-bold rounded-xl flex items-center gap-1.5 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Hapus Campaign</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Notification Area --}}
    @if(session('success'))
        <div class="alert-success">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="alert-error">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Progres generate pesan AI (berjalan di queue) --}}
    <div id="generation-progress" class="{{ $campaign->isGenerating() ? '' : 'hidden' }} dash-card border border-indigo-500/20">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-full border-2 border-indigo-500 border-t-transparent" style="animation: spin 1s linear infinite;"></div>
                <span class="text-xs font-bold text-slate-800 dark:text-white">AI sedang menulis pesan di background...</span>
            </div>
            <span id="generation-progress-label" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">
                {{ $campaign->generation_done + $campaign->generation_failed }} / {{ $campaign->generation_total }}
            </span>
        </div>
        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
            <div id="generation-progress-bar" class="h-full bg-linear-to-r from-indigo-500 to-purple-500 transition-all duration-500"
                 style="width: {{ $campaign->generation_total ? round(($campaign->generation_done + $campaign->generation_failed) / $campaign->generation_total * 100) : 0 }}%"></div>
        </div>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-2">Halaman akan dimuat ulang otomatis saat selesai. Pastikan queue worker berjalan (<code>composer run dev</code>).</p>
    </div>

    {{-- Dashboard Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" style="animation: fadeInUp 0.4s ease backwards;">
        {{-- Total Leads --}}
        <div class="dash-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Lead</span>
                <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $groupedMessages->count() }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Calon klien yang dituju</div>
        </div>

        {{-- Total Messages --}}
        <div class="dash-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Pesan Dibuat</span>
                <div class="p-2 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $totalMessages }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Draft pesan di campaign ini</div>
        </div>

        {{-- Sent Out --}}
        <div class="dash-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Pesan Terkirim</span>
                <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $sentCount }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                @if($totalMessages > 0)
                    {{ round(($sentCount / $totalMessages) * 100) }}% dari semua pesan
                @else
                    0% dari semua pesan
                @endif
            </div>
        </div>

        {{-- Conversions (Replied) --}}
        <div class="dash-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Balasan</span>
                <div class="p-2 rounded-lg bg-pink-500/10 text-pink-600 dark:text-pink-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 013 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $repliedCount }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                @if($sentCount > 0)
                    {{ round(($repliedCount / $sentCount) * 100) }}% reply rate
                @else
                    0% reply rate
                @endif
            </div>
        </div>
    </div>

    @include('campaigns.partials.sequence')

    {{-- Main Grid: Leads List & Message Reviewer --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" style="animation: fadeInUp 0.4s ease backwards; animation-delay: 0.05s;">
        
        {{-- Left: Campaign Leads Index --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="glass-card p-5 flex flex-col max-h-[700px]">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Lead di Campaign Ini</h3>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Klik lead untuk meninjau pesan personalnya</p>
                </div>

                <div class="overflow-y-auto space-y-2 flex-1 pr-1" id="campaign-leads-list">
                    @forelse($groupedMessages as $leadId => $leadMessages)
                        @php $lead = $leadMessages->first()->lead; @endphp
                        <div class="lead-item p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80 hover:border-indigo-500/25 hover:bg-indigo-500/5 transition cursor-pointer flex flex-col gap-2 relative overflow-hidden group" data-lead-id="{{ $leadId }}">
                            <div class="flex justify-between items-start gap-2">
                                <h4 class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">{{ $lead->business_name }}</h4>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0 border border-indigo-500/15">{{ $lead->niche }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                                <span class="flex items-center gap-0.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    {{ $lead->city }}
                                </span>
                                
                                <div class="flex gap-1">
                                    @foreach($leadMessages as $msg)
                                        <span class="p-1 rounded bg-slate-100 dark:bg-slate-800 text-[10px]" title="{{ ucfirst($msg->type) }}: {{ $msg->statusLabel() }}">
                                            @if($msg->type === 'email')
                                                <x-icon name="envelope" class="w-3 h-3 inline-block" />
                                            @else
                                                <x-icon name="chat" class="w-3 h-3 inline-block" />
                                            @endif
                                            <span class="font-bold text-[8px] uppercase @if($msg->status === 'sent') text-emerald-600 dark:text-emerald-400 @elseif($msg->status === 'replied') text-indigo-600 dark:text-indigo-400 @elseif($msg->status === 'failed') text-rose-600 @else text-slate-400 @endif">{{ mb_substr($msg->statusLabel(), 0, 1) }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-xs font-bold text-slate-500">Belum ada lead di campaign ini.</p>
                            <a href="{{ route('campaigns.edit', $campaign) }}" class="text-[10px] text-indigo-500 font-bold hover:underline mt-1.5 inline-flex items-center gap-1">Tambah lead sekarang <x-icon name="arrow-right" class="w-3 h-3" /></a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: AI Outreach message reviewer --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="glass-card p-6 flex flex-col min-h-[500px]" id="message-review-panel">
                {{-- Empty state (no lead selected) --}}
                <div id="review-empty-state" class="flex-1 flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-16 h-16 rounded-full bg-indigo-500/5 text-indigo-500 dark:text-indigo-400 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-sm text-slate-800 dark:text-white">Tinjau Pesan Outreach</h4>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-xs">Pilih lead di daftar sebelah kiri untuk melihat, mengedit, atau mengirim pesannya.</p>
                </div>

                {{-- Message templates (hidden by default, loaded via JS) --}}
                @foreach($groupedMessages as $leadId => $leadMessages)
                    <div class="lead-messages-container hidden space-y-6 animate-fadeIn" id="lead-msg-{{ $leadId }}">
                        @php $lead = $leadMessages->first()->lead; @endphp
                        
                        {{-- Lead Quick Header Info (Premium Rebuilt Design) --}}
                        <div class="bg-gradient-to-r from-slate-50 to-indigo-50/20 dark:from-slate-900/40 dark:to-indigo-950/10 p-5 rounded-2xl border border-slate-200/60 dark:border-slate-800/80 shadow-xs relative overflow-hidden">
                            <div class="absolute -right-16 -bottom-16 w-36 h-36 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
                            
                            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="space-y-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </span>
                                        <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight leading-tight">{{ $lead->business_name }}</h3>
                                    </div>
                                    
                                    {{-- Interactive & Visual Contact Pills --}}
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        @if($lead->website)
                                            <a href="{{ \App\Helpers\Url::normalize($lead->website) ?? '#' }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-50 dark:bg-sky-950/20 border border-sky-200/50 dark:border-sky-900/40 text-sky-700 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-950/40 transition font-medium shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                                </svg>
                                                <span>{{ parse_url($lead->website, PHP_URL_HOST) ?? $lead->website }}</span>
                                            </a>
                                        @endif
                                        
                                        @if($lead->email)
                                            <a href="mailto:{{ $lead->email }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-50 dark:bg-violet-950/20 border border-violet-200/50 dark:border-violet-900/40 text-violet-700 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-950/40 transition font-medium shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <span>{{ $lead->email }}</span>
                                            </a>
                                        @endif
                                        
                                        @if($lead->phone)
                                            <a href="tel:{{ $lead->phone }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200/50 dark:border-emerald-900/40 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 transition font-medium shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                                <span>{{ $lead->phone }}</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-start md:items-end flex-col shrink-0 md:text-right gap-1 md:border-l md:border-slate-200/60 dark:border-slate-800/80 md:pl-5">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 uppercase tracking-wider">{{ $lead->niche }}</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium flex items-center gap-1 mt-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        {{ $lead->city }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Outreach Cards --}}
                        <div class="space-y-4">
                            @foreach($leadMessages as $message)
                                @php $isEmail = $message->type === 'email'; @endphp
                                <div class="p-6 rounded-2xl border transition duration-300 relative group/card shadow-xs hover:shadow-sm
                                    @if($isEmail) 
                                        bg-gradient-to-br from-white to-indigo-50/10 dark:from-slate-900/60 dark:to-indigo-950/5 border-slate-200/80 dark:border-slate-800/80 focus-within:border-indigo-400/50 dark:focus-within:border-indigo-500/30
                                    @else 
                                        bg-gradient-to-br from-white to-emerald-50/10 dark:from-slate-900/60 dark:to-emerald-950/5 border-slate-200/80 dark:border-slate-800/80 focus-within:border-emerald-400/50 dark:focus-within:border-emerald-500/30
                                    @endif">
                                    
                                    {{-- Card Header --}}
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 pb-3.5 border-b border-slate-100 dark:border-slate-800/80">
                                        <div class="flex items-center gap-3">
                                            <span class="flex items-center justify-center w-9 h-9 rounded-xl shadow-2xs transition duration-200 shrink-0
                                                @if($isEmail) bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 @else bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 @endif">
                                                @if($isEmail)
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-2.25-1.5a2 2 0 00-2.22 0l-2.25 1.5" />
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                @endif
                                            </span>
                                            <div>
                                                <h4 class="font-bold text-xs text-slate-800 dark:text-white uppercase tracking-wider">
                                                    @if($isEmail) Email @else WhatsApp{{ $waGatewayActive ? '' : ' (click-to-chat)' }} @endif
                                                </h4>
                                                <p class="text-[10px] text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                                                    <span>Langkah {{ max(1, (int) $message->step) }} · {{ $message->isSequenceStep() ? 'follow-up' : 'pesan pembuka' }}</span>
                                                    @include('outreach.partials.reply-category')
                                                    @include('outreach.partials.review-badge')
                                                </p>
                                            </div>
                                        </div>
                                        
                                        {{-- Status Toggle Buttons --}}
                                        <div class="relative shrink-0 w-full sm:w-auto">
                                            <form action="{{ route('outreach.status', $message) }}" method="POST" class="flex items-center gap-1.5 flex-wrap justify-end">
                                                @csrf
                                                <button type="submit" name="status" value="pending" class="px-2.5 py-1 rounded-lg border text-[10px] font-bold transition cursor-pointer {{ $message->status === 'pending' ? 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-400' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-amber-400/40' }}">Draft</button>
                                                <button type="submit" name="status" value="sent" class="px-2.5 py-1 rounded-lg border text-[10px] font-bold transition cursor-pointer {{ $message->status === 'sent' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-400' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-emerald-400/40' }}">Terkirim</button>
                                                <button type="submit" name="status" value="replied" class="px-2.5 py-1 rounded-lg border text-[10px] font-bold transition cursor-pointer {{ $message->status === 'replied' ? 'bg-indigo-500/10 border-indigo-500/30 text-indigo-700 dark:text-indigo-400' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-indigo-400/40' }}">Dibalas</button>
                                                <button type="submit" name="status" value="failed" class="px-2.5 py-1 rounded-lg border text-[10px] font-bold transition cursor-pointer {{ $message->status === 'failed' ? 'bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-400' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-rose-400/40' }}">Gagal</button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    @if($message->reply_excerpt)
                                        <div class="mb-4 p-3 rounded-xl border-l-4 {{ $message->reply_category === 'auto_reply' ? 'border-slate-300 bg-slate-50 dark:bg-slate-900/40' : 'border-emerald-400 bg-emerald-500/5' }}">
                                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1 flex items-center gap-1"><x-icon name="reply" class="w-3 h-3" /> {{ $message->reply_category === 'auto_reply' ? 'Balasan otomatis (diabaikan)' : 'Balasan lead' }}</div>
                                            <p class="text-xs text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($message->reply_excerpt, 400) }}</p>
                                        </div>
                                    @endif

                                    {{-- Message Body Review & Save Form --}}
                                    <form action="{{ route('outreach.update', $message) }}" method="POST" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        
                                        @if($isEmail)
                                            {{-- Interactive Premium Subject Input --}}
                                            <div class="space-y-1.5">
                                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Subjek Email</label>
                                                <div class="relative flex items-center group/input">
                                                    <span class="absolute left-3.5 text-slate-400 dark:text-slate-500 font-bold text-xs select-none">Subjek:</span>
                                                    <input type="text" name="subject" id="campaignSubject-{{ $message->id }}" value="{{ $message->subject }}" oninput="markAsUnsaved({{ $message->id }})" class="w-full pl-18 pr-4 py-2.5 text-xs font-semibold bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none text-slate-850 dark:text-slate-100 transition shadow-2xs" placeholder="Tulis subjek email...">
                                                </div>
                                            </div>
                                        @endif

                                        {{-- AI Regeneration Section --}}
                                        <div class="space-y-1.5 mt-2 mb-4">
                                            <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-xl">
                                                <label class="block text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300 mb-2 flex items-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                    Generate Ulang dengan AI
                                                </label>
                                                <div class="flex flex-col gap-2">
                                                    <textarea id="customPrompt-{{ $message->id }}" rows="2" class="w-full text-xs font-medium bg-white dark:bg-slate-950 border border-indigo-200 dark:border-indigo-800/50 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500/20 outline-none resize-none" placeholder="Opsional: instruksi tambahan (mis. 'Buat lebih singkat')"></textarea>
                                                    <button type="button" onclick="regenerateCampaignMessage({{ $message->id }}, this)" class="btn-primary py-2 px-4 text-[10px] self-end shadow-md shadow-indigo-500/20 cursor-pointer inline-flex items-center gap-1.5">
                                                        <x-icon name="sparkles" class="w-3.5 h-3.5" /> Generate Ulang
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Message Textarea --}}
                                        <div class="space-y-1.5">
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Isi Pesan</label>
                                            <textarea name="message" id="campaignMessage-{{ $message->id }}" rows="6" oninput="markAsUnsaved({{ $message->id }})" class="w-full text-xs font-medium bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-4 focus:ring-4 outline-none leading-relaxed text-slate-700 dark:text-slate-200 resize-y shadow-2xs transition-all duration-200
                                                @if($isEmail) focus:ring-indigo-500/10 focus:border-indigo-500 @else focus:ring-emerald-500/10 focus:border-emerald-500 @endif" placeholder="Tulis isi pesan...">{{ $message->message }}</textarea>
                                        </div>
                                        
                                        {{-- Form Actions (Save draft changes and send outreach trigger) --}}
                                        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 dark:border-slate-800/80 pt-4 mt-2">
                                            <div class="flex items-center gap-3">
                                                <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition cursor-pointer bg-transparent border-0 outline-none">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                                    </svg>
                                                    <span>Simpan Perubahan</span>
                                                </button>
                                                <span id="unsavedWarning-{{ $message->id }}" class="hidden text-[10px] font-bold text-amber-500 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20 items-center gap-1">
                                                    <x-icon name="warning" class="w-3 h-3 inline-block" /> Belum disimpan
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center gap-2" id="sendActionContainer-{{ $message->id }}">
                                                @if($message->status === 'failed' && $message->last_error)
                                                    <span class="text-[10px] font-semibold text-rose-600 dark:text-rose-400 max-w-55 truncate" title="{{ $message->last_error }}"><x-icon name="warning" class="w-3 h-3 inline-block align-[-2px]" /> {{ $message->last_error }}</span>
                                                @endif
                                                @if($message->status === 'queued')
                                                    <span class="badge badge-queued px-3 py-1.5 text-[10px] gap-1"><x-icon name="clock" class="w-3 h-3" /> Antrean{{ $message->scheduled_at ? ' · '.$message->scheduled_at->translatedFormat('d M H:i') : ' · diproses' }}</span>
                                                @elseif(($isEmail || $waGatewayActive) && $message->isDelivered())
                                                    <span class="badge badge-sent px-3 py-1.5 text-[10px] gap-1"><x-icon name="check-circle" class="w-3 h-3" /> Terkirim{{ $message->sent_at ? ' · '.$message->sent_at->translatedFormat('d M H:i') : '' }}</span>
                                                @elseif(! $isEmail && $waGatewayActive)
                                                    @if(! $lead->phone_is_mobile)
                                                        <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400" title="Nomor kantor belum tentu terdaftar di WhatsApp">Nomor kantor</span>
                                                    @endif
                                                    {{-- Kirim otomatis lewat gateway --}}
                                                    <button type="button" onclick="sendDirectEmail(this, '{{ route('outreach.send', $message) }}', 'WhatsApp')" class="relative overflow-hidden px-4 py-2 bg-linear-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold rounded-xl text-xs transition duration-200 flex items-center gap-1.5 cursor-pointer">
                                                        <x-icon name="send" class="w-3.5 h-3.5" />
                                                        <span>Kirim WhatsApp</span>
                                                    </button>
                                                @elseif($isEmail)
                                                    {{-- SMTP Direct Send Button --}}
                                                    <button type="button" onclick="sendDirectEmail(this, '{{ route('outreach.send', $message) }}')" class="relative overflow-hidden px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold rounded-xl text-xs hover:shadow-md hover:shadow-indigo-500/10 hover:-translate-y-0.5 active:translate-y-0 transition duration-200 flex items-center gap-1.5 cursor-pointer">
                                                        <svg class="w-3.5 h-3.5 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                        </svg>
                                                        <span>Kirim Email</span>
                                                    </button>
                                                @else
                                                    @if($lead->phone && ! $lead->phone_is_mobile)
                                                        <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400" title="Nomor kantor belum tentu terdaftar di WhatsApp">Nomor kantor</span>
                                                    @endif
                                                    {{-- WhatsApp Click-to-Chat Trigger --}}
                                                    <button type="button"
                                                        data-wa-url="{{ \App\Helpers\Phone::whatsAppUrl($lead->phone, $message->message) }}"
                                                        data-status-url="{{ route('outreach.status', $message) }}"
                                                        onclick="triggerWhatsAppChat(this)" class="relative overflow-hidden px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold rounded-xl text-xs hover:shadow-md hover:shadow-emerald-500/10 hover:-translate-y-0.5 active:translate-y-0 transition duration-200 flex items-center gap-1.5 cursor-pointer">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                        </svg>
                                                        <span>Buka WhatsApp</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const leadItems = document.querySelectorAll('.lead-item');
    const reviewEmptyState = document.getElementById('review-empty-state');
    const containers = document.querySelectorAll('.lead-messages-container');

    // Click Lead to show their templates
    leadItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active style from all lead items
            leadItems.forEach(li => {
                li.classList.remove('border-indigo-500/40', 'bg-indigo-500/5', 'dark:bg-indigo-500/10');
                li.classList.add('border-slate-200', 'dark:border-slate-800/80');
            });
            
            // Add active style to selected item
            item.classList.remove('border-slate-200', 'dark:border-slate-800/80');
            item.classList.add('border-indigo-500/40', 'bg-indigo-500/5', 'dark:bg-indigo-500/10');

            // Hide empty state & other containers
            reviewEmptyState.classList.add('hidden');
            containers.forEach(c => c.classList.add('hidden'));

            // Show selected lead's message review panel
            const leadId = item.getAttribute('data-lead-id');
            const targetContainer = document.getElementById(`lead-msg-${leadId}`);
            if (targetContainer) {
                targetContainer.classList.remove('hidden');
            }
        });
    });

});

// SMTP Direct Email Sending Utility
// Polling progres generate AI di background
(function pollGenerationProgress() {
    const box = document.getElementById('generation-progress');
    if (!box || box.classList.contains('hidden')) return;

    const url = @js(route('campaigns.progress', $campaign));
    const timer = setInterval(async () => {
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            const processed = data.done + data.failed;
            document.getElementById('generation-progress-label').textContent = `${processed} / ${data.total}`;
            document.getElementById('generation-progress-bar').style.width = (data.total ? Math.round(processed / data.total * 100) : 0) + '%';

            if (!data.generating) {
                clearInterval(timer);
                window.showToast(`Generate selesai: ${data.done} berhasil${data.failed ? ', ' + data.failed + ' gagal' : ''}.`, data.failed && !data.done ? 'error' : 'success');
                setTimeout(() => location.reload(), 1200);
            }
        } catch (e) {
            console.error(e);
        }
    }, 3000);
})();

async function sendDirectEmail(btn, url, channel = 'Email') {
    if (!btn.dataset.confirmed) {
        handleConfirmAction('Kirim ' + channel + '?', 'Pesan ' + channel + ' ini akan dikirim sekarang juga.', 'Ya, kirim', () => {
            btn.dataset.confirmed = 'true';
            sendDirectEmail(btn, url, channel);
        });
        return;
    }
    delete btn.dataset.confirmed;
    
    const originalContent = btn.innerHTML;
    btn.innerHTML = 'Mengirim...';
    btn.disabled = true;
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.status === 'success') {
            window.showToast(result.message || 'Email terkirim!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            window.showToast('Gagal mengirim: ' + (result.message || 'server email error.'), 'error');
        }
    } catch (e) {
        console.error(e);
        window.showToast('Gagal terhubung ke server saat mengirim.', 'error');
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

// WhatsApp click-to-chat utility
function triggerWhatsAppChat(btn) {
    // URL wa.me dibuat di server (App\Helpers\Phone) dan dibaca dari data-attribute,
    // supaya isi pesan/nomor tidak pernah dieksekusi sebagai JavaScript.
    const waUrl = btn.dataset.waUrl;
    const statusUrl = btn.dataset.statusUrl;

    if (!waUrl) {
        window.showToast('Lead ini tidak punya nomor WhatsApp yang valid!', 'error');
        return;
    }

    // Open WhatsApp Web / App
    window.open(waUrl, '_blank');
    
    // 3. Prompt user if they want to update status to "Sent" automatically!
    handleConfirmAction('WhatsApp sudah dikirim?', 'Apakah pesan WhatsApp tadi sudah Anda kirim? Ubah status pesan menjadi Terkirim?', 'Ya, ubah', () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = statusUrl;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = 'sent';
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    });
}

// Regenerate AI Message inline
async function regenerateCampaignMessage(messageId, btn) {
    const customPrompt = document.getElementById('customPrompt-' + messageId).value.trim();
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Membuat...';
    btn.disabled = true;
    
    try {
        const response = await fetch(`/outreach/${messageId}/regenerate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ custom_prompt: customPrompt })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            const subjectEl = document.getElementById('campaignSubject-' + messageId);
            if (subjectEl && data.subject !== null) {
                subjectEl.value = data.subject;
            }
            document.getElementById('campaignMessage-' + messageId).value = data.message;
            markAsUnsaved(messageId);
            window.showToast('Pesan baru dibuat. Tekan Simpan Perubahan sebelum mengirim.', 'success');
        } else {
            window.showToast(data.message || 'Gagal membuat ulang pesan.', 'error');
        }
    } catch (e) {
        console.error(e);
        window.showToast('Gagal terhubung ke server saat membuat ulang pesan.', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Hide send buttons when there are unsaved edits
function markAsUnsaved(messageId) {
    const sendContainer = document.getElementById('sendActionContainer-' + messageId);
    const warningBadge = document.getElementById('unsavedWarning-' + messageId);
    
    if (sendContainer) {
        sendContainer.classList.add('hidden');
    }
    if (warningBadge) {
        warningBadge.classList.remove('hidden');
    }
}
</script>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
@endsection
