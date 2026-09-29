@extends('layouts.app')

@section('title', 'Outreach - Sandesa')
@section('header', 'Kelola Outreach')

@section('content')
{{-- ===== STUNNING OUTREACH COMPOSER ===== --}}
<div class="glass-card p-6 mb-8 border-t-2 border-t-indigo-500/50" style="animation: fadeInUp 0.4s ease backwards;">
    <div class="flex flex-col lg:flex-row gap-6">
       
        
        <div class="w-full">
            <form id="outreach-composer-form" class="bg-slate-50/50 dark:bg-slate-800/20 border border-slate-200/50 dark:border-slate-800/60 rounded-2xl p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="composer_campaign_id" class="form-label text-xs">Campaign Tujuan</label>
                        <x-searchable-select id="composer-campaign-select" inputId="composer_campaign_id" name="campaign_id" :required="true" placeholder="Pilih campaign..." :options="$campaigns->mapWithKeys(fn($c) => [$c->id => $c->name . ' (' . $c->niche . ')'])->toArray()" triggerClass="form-select text-xs w-full" />
                    </div>

                    <div>
                        <label for="composer_type" class="form-label text-xs">Channel Outreach</label>
                        <x-searchable-select id="composer-type-select" inputId="composer_type" name="type" :required="true" :selected="'email'" :options="['email' => 'Email', 'whatsapp' => 'WhatsApp']" triggerClass="form-select text-xs w-full" />
                    </div>

                    <div>
                        <label for="composer_mode" class="form-label text-xs">Mode Penyusunan</label>
                        <x-searchable-select id="composer-mode-select" inputId="composer_mode" name="mode" :required="true" :selected="'hybrid'" :options="['hybrid' => 'Hybrid (Template + AI)', 'template' => 'Template', 'ai' => 'AI Generate']" triggerClass="form-select text-xs w-full" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div id="composer_template_wrapper" class="md:col-span-1">
                        <label for="composer_template_id" class="form-label text-xs">Pilih Template</label>
                        <x-searchable-select id="composer-template-select" inputId="composer_template_id" name="template_id" placeholder="Pilih template..." :options="[]" triggerClass="form-select text-xs w-full" />
                    </div>

                    <div id="composer_offer_wrapper" class="md:col-span-1">
                        <label for="composer_offer" class="form-label text-xs">Layanan / Penawaran</label>
                        <input type="text" name="offer" id="composer_offer" value="{{ $defaultOffer }}" placeholder="mis. Pembuatan Website" class="form-input text-xs">
                    </div>

                    <div id="composer_sender_wrapper" class="md:col-span-1">
                        <label for="composer_sender" class="form-label text-xs">Identitas Pengirim</label>
                        <input type="text" name="sender_name" id="composer_sender" value="{{ $senderName }}" placeholder="mis. Thoriq dari Lefateach" class="form-input text-xs">
                    </div>
                </div>

                {{-- Leads Selection --}}
                <div class="border-t border-slate-200/50 dark:border-slate-800/60 pt-4">
                    <div class="flex justify-between items-center mb-3">
                        <label class="form-label text-xs !mb-0 font-bold text-slate-700 dark:text-slate-350">Pilih Target</label>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 text-2xs text-slate-600 dark:text-slate-400 cursor-pointer bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                <input type="checkbox" id="select-all-leads" class="form-checkbox h-3.5 w-3.5 rounded text-indigo-600 border-slate-300">
                                <span>Pilih semua</span>
                            </label>
                            <button type="button" id="btn-smart-select" class="text-[10px] bg-gradient-to-r from-indigo-600 to-purple-600 px-2.5 py-1 rounded-lg text-white font-bold hover:shadow-lg hover:shadow-indigo-500/20 transition border border-indigo-500/30 inline-flex items-center gap-1">
                                <x-icon name="sparkles" class="w-3 h-3" /> Smart Select
                            </button>
                        </div>
                    </div>
                    <div class="mb-3 p-3 rounded-xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50 dark:bg-slate-800/30 space-y-2.5">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" id="lead-search-input" placeholder="Cari nama bisnis, kota, niche..." class="w-full pl-8 pr-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-1.5">
                            <x-searchable-select
                                id="lead-niche-filter-select"
                                inputId="lead-niche-filter"
                                name="lead_niche_filter"
                                :selected="''"
                                placeholder="Semua Niche"
                                :options="$leadNiches->mapWithKeys(fn($n) => [$n => $n])->toArray()"
                                triggerClass="w-full px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300" />
                            <x-searchable-select
                                id="lead-city-filter-select"
                                inputId="lead-city-filter"
                                name="lead_city_filter"
                                :selected="''"
                                placeholder="Semua Lokasi"
                                :options="$leadCities->mapWithKeys(fn($c) => [$c => $c])->toArray()"
                                triggerClass="w-full px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300" />
                        </div>
                        <div class="grid grid-cols-3 gap-1.5">
                            <button type="button" id="filter-has-email" data-active="0" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300 hover:text-indigo-600">Punya Email</button>
                            <button type="button" id="filter-has-phone" data-active="0" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300 hover:text-indigo-600">Punya Telepon</button>
                            <button type="button" id="filter-has-website" data-active="0" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300 hover:text-indigo-600">Punya Website</button>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-inner">
                        <div class="max-h-[320px] overflow-y-auto p-2 space-y-1.5" id="leads-checkbox-list">
                            <div id="leads-list-status" class="text-center py-8 text-xs font-bold text-slate-400 dark:text-slate-500">
                                {{ $totalLeads ? 'Memuat lead...' : 'Belum ada lead. Scrape dulu di halaman Leads.' }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 px-3 py-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                            <span class="text-slate-500 dark:text-slate-400"><strong id="leads-selected-count" class="text-slate-800 dark:text-slate-100">0</strong> dipilih · <span id="leads-shown-count">0</span> dari <span id="leads-total-count">0</span> ditampilkan</span>
                            <span class="flex items-center gap-3">
                                <button type="button" id="leads-clear-selection" class="font-semibold text-slate-500 hover:text-rose-500 hidden">Hapus pilihan</button>
                                <button type="button" id="leads-load-more" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline hidden">Muat lebih banyak</button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" onclick="prepareComposerPreview()" class="btn-success shadow-lg shadow-emerald-500/20 px-6 py-2.5 text-xs font-bold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Buat & Pratinjau</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== COMPOSER PREVIEW WIZARD MODAL ===== --}}
<div id="composerPreviewModal" class="hidden fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/70 backdrop-blur-md transition-opacity opacity-0 duration-300">
    <div class="glass-card w-full max-w-5xl h-[85vh] p-0 relative overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800" id="composerPreviewModalContent">
        {{-- Header --}}
        <div class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200/60 dark:border-slate-700/60 p-5 flex justify-between items-center shrink-0">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pratinjau & Personalisasi Pesan Outreach</h3>
            </div>
            <button type="button" onclick="closeComposerPreviewModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        {{-- Main Container --}}
        <div id="composer-preview-body" class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50/50 dark:bg-slate-900/30">
            {{-- Loaded dynamically --}}
        </div>

        {{-- Footer --}}
        <div class="bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200/60 dark:border-slate-700/60 p-4 flex justify-between items-center shrink-0">
            <span class="text-2xs text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">Mode: <span id="composer-footer-mode-label" class="text-indigo-500 font-bold">Hybrid</span></span>
            <div class="flex gap-3">
                <button type="button" onclick="closeComposerPreviewModal()" class="btn-secondary px-5 py-2 text-xs">Batal</button>
                <button type="button" id="btn-save-composer-pipeline" onclick="saveComposerPipeline()" class="btn-success shadow-lg shadow-emerald-500/20 px-6 py-2 text-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan ke Pipeline</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ===== OUTREACH HISTORY ===== --}}
<div class="glass-card p-6" style="animation: fadeInUp 0.4s ease backwards; animation-delay: 0.1s;">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-pink-500/10 text-pink-600 dark:text-pink-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Pipeline Outreach</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Tinjau, edit, dan kirim pesan Anda</p>
            </div>
        </div>
        
        <form action="{{ route('outreach.bulk') }}" method="POST" id="bulkOutreachForm" class="flex flex-wrap sm:flex-nowrap gap-2 items-center w-full sm:w-auto" onsubmit="return confirmOutreachBulk(event, this)">
            @csrf
            <x-searchable-select name="channel"
                required="true"
                :selected="in_array(request('type'), ['email', 'whatsapp'], true) ? request('type') : 'all'"
                :options="[
                    'all' => 'Semua kanal',
                    'email' => 'Email saja',
                    'whatsapp' => 'WhatsApp saja',
                ]"
                triggerClass="form-select text-xs w-full sm:w-36 !py-1.5 !rounded-lg" />
            <x-searchable-select name="action"
                required="true"
                placeholder="Aksi massal"
                :options="[
                    'send_queue' => 'Kirim via Antrean',
                    'delete' => 'Hapus yang Dipilih',
                    'status_pending' => 'Tandai sebagai Draft (batalkan antrean)',
                    'status_sent' => 'Tandai sebagai Terkirim',
                    'status_replied' => 'Tandai sebagai Dibalas',
                    'status_failed' => 'Tandai sebagai Gagal'
                ]"
                triggerClass="form-select text-xs w-full sm:w-48 !py-1.5 !rounded-lg" />
            <button type="submit" class="btn-secondary py-1.5 px-4 text-xs font-bold">Terapkan</button>
            <a href="{{ route('outreach.export', request()->only(['status', 'type', 'campaign_id'])) }}" class="btn-secondary py-1.5 px-3 text-xs font-bold whitespace-nowrap" title="Export hasil outreach ke CSV"><x-icon name="download" class="w-3.5 h-3.5" /> CSV</a>
        </form>
    </div>

    {{-- Filter status --}}
    <div class="flex flex-wrap items-center gap-2 mb-5">
        @php $currentStatus = request('status'); @endphp
        <a href="{{ route('outreach.index', request()->except(['status', 'page'])) }}" class="px-3 py-1 rounded-full text-[11px] font-bold border transition {{ !$currentStatus ? 'bg-slate-900 text-white border-slate-900 dark:bg-indigo-600 dark:border-indigo-600' : 'border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-slate-400' }}">Semua</a>
        @foreach(['pending' => 'Draft', 'queued' => 'Antre', 'sent' => 'Terkirim', 'replied' => 'Dibalas', 'failed' => 'Gagal'] as $value => $label)
            <a href="{{ route('outreach.index', array_merge(request()->except('page'), ['status' => $value])) }}" class="px-3 py-1 rounded-full text-[11px] font-bold border transition {{ $currentStatus === $value ? 'bg-slate-900 text-white border-slate-900 dark:bg-indigo-600 dark:border-indigo-600' : 'border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-slate-400' }}">{{ $label }}</a>
        @endforeach

        {{-- Filter kanal: tampilkan satu kanal lalu centang semua untuk aksi massal khusus email / WhatsApp --}}
        @php $currentType = in_array(request('type'), ['email', 'whatsapp'], true) ? request('type') : null; @endphp
        <span class="hidden sm:block w-px h-5 bg-slate-200 dark:bg-slate-700 mx-1"></span>
        @foreach([null => 'Semua kanal', 'email' => 'Email', 'whatsapp' => 'WhatsApp'] as $value => $label)
            <a href="{{ route('outreach.index', array_merge(request()->except(['type', 'page']), $value ? ['type' => $value] : [])) }}" class="px-3 py-1 rounded-full text-[11px] font-bold border transition inline-flex items-center gap-1 {{ $currentType === ($value ?: null) ? 'bg-slate-900 text-white border-slate-900 dark:bg-indigo-600 dark:border-indigo-600' : 'border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-slate-400' }}">
                @if($value === 'email')<x-icon name="envelope" class="w-3 h-3" />@elseif($value === 'whatsapp')<x-icon name="chat" class="w-3 h-3" />@endif
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if($fakeMailer)
        <div class="alert-error mb-5">
            <span><strong>Mode kirim email: log.</strong> Email hanya ditulis ke <code>storage/logs/laravel.log</code>, tidak benar-benar terkirim. Pilih SMTP di @can('manage_settings')<a href="{{ route('settings.edit') }}#koneksi" class="underline font-semibold">Pengaturan → Koneksi</a>@else Pengaturan → Koneksi @endcan untuk mengirim sungguhan.</span>
        </div>
    @endif

    @if($queuedCount > 0)
        <div class="mb-5 p-3 rounded-xl border border-violet-500/20 bg-violet-500/5 text-xs text-violet-700 dark:text-violet-300 font-semibold">
            <x-icon name="clock" class="w-4 h-4 inline-block align-[-3px] mr-1" />{{ collect([
                'email' => 'email (maks. '.config('leadhunter.sending.hourly_limit').'/jam)',
                'whatsapp' => 'WhatsApp (maks. '.config('leadhunter.whatsapp.hourly_limit').'/jam)',
            ])->filter(fn ($label, $type) => ($queuedByChannel[$type] ?? 0) > 0)->map(fn ($label, $type) => $queuedByChannel[$type].' '.$label)->implode(' dan ') }} di antrean kirim. Antrean diproses oleh scheduler; pastikan <code>composer run dev</code> (atau <code>php artisan schedule:work</code> + queue worker) berjalan.
        </div>
    @endif

    @if(session('success'))
        <div class="alert-success mb-6 shadow-lg shadow-emerald-500/10">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert-error mb-6 shadow-lg shadow-red-500/10">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="fancy-table min-w-full">
            <thead>
                <tr>
                    <th style="width: 45px"><input type="checkbox" id="selectAllOutreach" class="form-checkbox h-4.5 w-4.5 rounded text-indigo-600 border-slate-300"></th>
                    <th style="width: 25%">Target</th>
                    <th style="width: 45%">Pratinjau Pesan</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                <tr class="group">
                    <td class="align-top py-4">
                        <input type="checkbox" name="ids[]" value="{{ $msg->id }}" form="bulkOutreachForm" class="outreach-checkbox form-checkbox h-4.5 w-4.5 rounded text-indigo-600 border-slate-300 mt-1">
                    </td>
                    <td class="align-top py-4">
                        <div class="font-bold text-slate-900 dark:text-slate-100 mb-1">{{ $msg->lead->business_name }}</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mb-2.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>{{ $msg->campaign->name }}</span>
                        </div>
                        <div>
                            @if(($msg->type ?? 'email') === 'whatsapp')
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/15 flex items-center gap-1 w-max">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span>WhatsApp</span>
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 flex items-center gap-1 w-max">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span>Email</span>
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="align-top py-4 pr-6">
                        <div class="bg-slate-50 dark:bg-slate-900/40 rounded-lg p-4 border border-slate-200 dark:border-slate-800 relative group-hover:border-indigo-500/15 transition-all">
                            @if($msg->subject)
                                <div class="font-bold text-indigo-600 dark:text-indigo-400 text-xs mb-2 pb-2 border-b border-slate-150 dark:border-slate-800">{{ $msg->subject }}</div>
                            @endif
                            <div class="text-slate-700 dark:text-slate-300 text-xs leading-relaxed line-clamp-3 whitespace-pre-wrap">{{ $msg->message }}</div>
                            
                            @if($msg->status == 'pending')
                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button"
                                            data-id="{{ $msg->id }}"
                                            data-subject="{{ $msg->subject }}"
                                            data-message="{{ $msg->message }}"
                                            onclick="openEditModal(this.dataset.id, this.dataset.subject, this.dataset.message)"
                                            class="p-1.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-indigo-500/20 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition" title="Edit Pesan">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="align-top py-4">
                        <div class="flex flex-col gap-1.5 items-start">
                            <span class="badge px-3 py-1.5 text-[10px]
                                {{ $msg->status == 'sent' ? 'badge-sent' : '' }}
                                {{ $msg->status == 'replied' ? 'badge-replied' : '' }}
                                {{ $msg->status == 'pending' ? 'badge-pending' : '' }}
                                {{ $msg->status == 'failed' ? 'badge-failed' : '' }}
                                {{ $msg->status == 'queued' ? 'badge-queued' : '' }}
                            ">
                                @if($msg->status == 'sent')
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9-2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                @elseif($msg->status == 'replied')
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                @elseif($msg->status == 'pending')
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @elseif($msg->status == 'failed')
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                @endif
                                {{ $msg->statusLabel() }}
                            </span>
                            @if($msg->sent_at)
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ $msg->sent_at->translatedFormat('d M H:i') }}</span>
                            @endif
                            @if($msg->status === 'queued')
                                <span class="text-[10px] text-violet-600 dark:text-violet-400 font-medium">{{ $msg->scheduled_at ? 'Jadwal: '.$msg->scheduled_at->translatedFormat('d M H:i') : 'Sedang diproses...' }}</span>
                            @endif
                            @if($msg->isSequenceStep())
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-500/10 text-sky-600 dark:text-sky-400 inline-flex items-center gap-1"><x-icon name="reply" class="w-3 h-3" /> Follow-up · langkah {{ $msg->step }}</span>
                            @endif
                            @include('outreach.partials.reply-category', ['message' => $msg])
                            @include('outreach.partials.review-badge', ['message' => $msg])
                            @if($msg->status === 'failed' && $msg->last_error)
                                <span class="text-[10px] text-rose-600 dark:text-rose-400 max-w-44 line-clamp-2" title="{{ $msg->last_error }}">{{ $msg->last_error }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="align-top py-4 text-right">
                        <div class="flex flex-col items-end gap-2.5">
                            @if($msg->status == 'pending')
                                <form action="{{ route('outreach.send', $msg->id) }}" method="POST" class="inline" @if(($msg->type ?? 'email') === 'whatsapp' && ! $waGatewayActive) target="_blank" @endif>
                                    @csrf
                                    @if(($msg->type ?? 'email') === 'whatsapp')
                                        <button type="submit" class="btn-success shadow-md shadow-emerald-500/10 text-xs py-1.5 px-4 rounded-lg">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <span>{{ $waGatewayActive ? 'Kirim WA' : 'Buka WA' }}</span>
                                        </button>
                                    @else
                                        <button type="submit" class="btn-primary shadow-md shadow-indigo-500/10 text-xs py-1.5 px-4 rounded-lg">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9-2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                            <span>Kirim Email</span>
                                        </button>
                                    @endif
                                </form>
                            @elseif($msg->status == 'queued')
                                <form action="{{ route('outreach.status', $msg->id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="pending">
                                    <button type="submit" class="btn-secondary py-1 px-3 text-[11px] font-bold rounded-lg" title="Keluarkan dari antrean kirim">
                                        <span>Batalkan Antrean</span>
                                    </button>
                                </form>
                            @elseif($msg->status == 'sent')
                                <div class="flex gap-2 justify-end">
                                    <form action="{{ route('outreach.status', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="replied">
                                        <button type="submit" class="btn-success py-1 px-3 text-[11px] font-bold rounded-lg" title="Tandai sebagai Dibalas">
                                            <span>Dibalas</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('outreach.status', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="failed">
                                        <button type="submit" class="btn-secondary py-1 px-3 text-[11px] font-bold text-red-500 dark:text-red-400 border-red-500/20 hover:border-red-500/40 hover:bg-red-500/5 rounded-lg" title="Tandai sebagai Gagal">
                                            <span>Gagal</span>
                                        </button>
                                    </form>
                                </div>
                            @elseif($msg->status == 'replied')
                                <div class="flex flex-col items-end gap-1.5">
                                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/15 px-2 py-1 rounded"><x-icon name="trophy" class="w-3.5 h-3.5" /> Deal Tercapai</span>
                                    <form action="{{ route('outreach.status', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="text-slate-500 hover:text-slate-800 dark:hover:text-white text-[10px] font-semibold underline bg-transparent border-none cursor-pointer transition">
                                            Atur Ulang Status
                                        </button>
                                    </form>
                                </div>
                            @elseif($msg->status == 'failed')
                                <div class="flex flex-col items-end gap-2">
                                    <form action="{{ route('outreach.send', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-pink shadow-md shadow-pink-500/15 py-1 px-3 text-[11px] font-bold rounded-lg">
                                            <span>Coba Lagi</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('outreach.status', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="text-slate-500 hover:text-slate-800 dark:hover:text-white text-[10px] font-semibold underline bg-transparent border-none cursor-pointer transition">
                                            Atur Ulang Status
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <form action="{{ route('outreach.destroy', $msg->id) }}" method="POST" class="inline mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 w-full text-right" onsubmit="return handleConfirm(event, this, 'Hapus Pesan?', 'Yakin ingin menghapus pesan outreach ini? Tindakan ini tidak bisa dibatalkan.', 'Ya, Hapus')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-red-500 text-[10px] transition font-semibold" title="Hapus Pesan">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state py-12">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                            </div>
                            <p class="font-bold text-slate-500 dark:text-slate-400">Pipeline Masih Kosong</p>
                            <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 max-w-sm mx-auto">Pilih campaign dan lead target di composer di atas, lalu biarkan AI menyusun pesan yang pas.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $messages->links() }}
    </div>
</div>

{{-- ===== EDIT MODAL ===== --}}
<div id="editModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity opacity-0 duration-300">
    <div class="glass-card w-full max-w-2xl p-0 relative overflow-hidden transform scale-95 transition-transform duration-300" id="editModalContent">
        {{-- Header --}}
        <div class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/60 p-5 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span>Edit Pesan AI</span>
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        {{-- Body --}}
        <div class="p-6">
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-3.5 mb-5 flex items-start gap-3">
                <span class="text-amber-500"><x-icon name="light-bulb" class="w-4 h-4" /></span>
                <p class="text-xs text-amber-700 dark:text-amber-200/80 leading-relaxed">Anda sedang mengedit teks asli pesan. Perhatikan format penulisannya. Perubahan akan disimpan permanen untuk pesan outreach ini.</p>
            </div>

            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-5">
                    <label class="form-label text-xs">Subjek <span class="text-[10px] text-slate-500 ml-2 font-normal">(Kosongkan untuk WhatsApp)</span></label>
                    <input type="text" name="subject" id="editSubject" class="form-input">
                </div>
                
                <div class="mb-5 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-xl">
                    <label class="form-label text-xs flex justify-between items-center mb-2">
                        <span class="text-indigo-700 dark:text-indigo-300 font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Generate Ulang dengan AI
                        </span>
                    </label>
                    <div class="flex flex-col gap-2">
                        <textarea id="customPrompt" rows="2" class="form-input text-xs w-full" placeholder="Opsional: tulis instruksi khusus (mis. 'Buat lebih santai', 'Sebutkan promo diskon kami')"></textarea>
                        <button type="button" id="btnRegenerate" onclick="regenerateMessage()" class="btn-primary py-2 text-xs self-end inline-flex items-center gap-1.5">
                            <x-icon name="sparkles" class="w-3.5 h-3.5" /> Generate Ulang
                        </button>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="form-label text-xs">Isi Pesan</label>
                    <textarea name="message" id="editMessage" rows="10" required class="form-input font-mono text-xs leading-relaxed resize-y"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="closeEditModal()" class="btn-secondary px-5 py-2.5">Batal</button>
                    <button type="submit" class="btn-primary shadow-lg shadow-indigo-500/20 px-6 py-2.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');
    const modalContent = document.getElementById('editModalContent');
    const editForm = document.getElementById('editForm');
    const editSubject = document.getElementById('editSubject');
    const editMessage = document.getElementById('editMessage');
    let currentEditId = null;

    function openEditModal(id, subject, message) {
        currentEditId = id;
        editForm.action = `/outreach/${id}`;
        editSubject.value = subject || '';
        editMessage.value = message || '';

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeEditModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            editForm.reset();
            document.getElementById('customPrompt').value = '';
            currentEditId = null;
        }, 300);
    }

    async function regenerateMessage() {
        if (!currentEditId) return;
        
        const btn = document.getElementById('btnRegenerate');
        const customPrompt = document.getElementById('customPrompt').value.trim();
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Membuat ulang...';
        btn.disabled = true;
        
        try {
            const response = await fetch(`/outreach/${currentEditId}/regenerate`, {
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
                if (data.subject !== null) {
                    document.getElementById('editSubject').value = data.subject;
                }
                document.getElementById('editMessage').value = data.message;
                window.showToast('Pesan berhasil dibuat ulang! Periksa lalu klik Simpan Perubahan.', 'success');
            } else {
                window.showToast(data.message || 'Gagal membuat ulang pesan.', 'error');
            }
        } catch (e) {
            console.error(e);
            window.showToast('Kesalahan jaringan saat membuat ulang pesan.', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeEditModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeEditModal();
        }
    });

    // ==========================================
    // ===== NEW INTERACTIVE COMPOSER ENGINE =====
    // ==========================================
    let allTemplates = [];
    const previewModal = document.getElementById('composerPreviewModal');
    const previewModalContent = document.getElementById('composerPreviewModalContent');
    const previewBody = document.getElementById('composer-preview-body');
    const selectAllCb = document.getElementById('select-all-leads');
    const leadSearchInput = document.getElementById('lead-search-input');
    const leadNicheFilterInput = document.getElementById('lead-niche-filter');
    const leadCityFilterInput = document.getElementById('lead-city-filter');
    const filterHasEmailBtn = document.getElementById('filter-has-email');
    const filterHasPhoneBtn = document.getElementById('filter-has-phone');
    const filterHasWebsiteBtn = document.getElementById('filter-has-website');
    const btnSmartSelect = document.getElementById('btn-smart-select');

    // Daftar lead dimuat bertahap dari server (tidak lagi semua lead sekaligus).
    // Pilihan disimpan di selectedLeads sehingga tetap terpilih walau filter berubah.
    const selectedLeads = new Map(); // id → nama bisnis
    const leadsList = document.getElementById('leads-checkbox-list');
    const leadsLoadMoreBtn = document.getElementById('leads-load-more');
    const leadsClearBtn = document.getElementById('leads-clear-selection');
    const leadsFilterUrl = @js(route('campaigns.leads.filter'));
    let leadPage = 1;
    let leadController = null;

    function debounce(func, wait = 250) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }

    function setFilterButtonState(btn, active) {
        if (!btn) return;
        btn.dataset.active = active ? '1' : '0';
        btn.classList.toggle('bg-indigo-500/10', active);
        btn.classList.toggle('text-indigo-600', active);
        btn.classList.toggle('border-indigo-500/30', active);
        btn.classList.toggle('bg-white', !active);
        btn.classList.toggle('dark:bg-slate-900', !active);
        btn.classList.toggle('text-slate-500', !active);
    }

    function leadFilterParams(extra = {}) {
        const params = new URLSearchParams();
        const search = (leadSearchInput?.value || '').trim();
        if (search) params.set('search', search);
        if (leadNicheFilterInput?.value) params.set('niche', leadNicheFilterInput.value);
        if (leadCityFilterInput?.value) params.set('city', leadCityFilterInput.value);
        if (filterHasEmailBtn?.dataset.active === '1') params.set('has_email', 'yes');
        if (filterHasPhoneBtn?.dataset.active === '1') params.set('has_phone', 'yes');
        if (filterHasWebsiteBtn?.dataset.active === '1') params.set('has_website', 'yes');
        Object.entries(extra).forEach(([key, value]) => params.set(key, value));
        return params;
    }

    function updateLeadCounters(shown, total, hasMore) {
        document.getElementById('leads-selected-count').textContent = selectedLeads.size;
        if (shown !== undefined) {
            document.getElementById('leads-shown-count').textContent = shown;
            document.getElementById('leads-total-count').textContent = total;
            leadsLoadMoreBtn.classList.toggle('hidden', !hasMore);
        }
        leadsClearBtn.classList.toggle('hidden', selectedLeads.size === 0);
    }

    function renderLeadItem(lead) {
        const label = document.createElement('label');
        label.className = 'lead-item flex items-center justify-between p-2.5 rounded-lg border border-slate-100 dark:border-slate-800/50 hover:border-indigo-500/20 hover:bg-indigo-500/5 cursor-pointer transition';
        label.dataset.id = lead.id;
        const badges = [
            lead.email ? '<span class="text-sky-600 bg-sky-500/10 px-1.5 py-0.5 rounded font-semibold">Email</span>' : '',
            lead.phone ? '<span class="text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded font-semibold">' + (lead.phone_is_mobile ? 'WA' : 'Telp. kantor') + '</span>' : '',
            lead.score >= 70 ? '<span class="text-rose-600 bg-rose-500/10 px-1.5 py-0.5 rounded font-semibold">Hot ' + lead.score + '</span>' : '',
        ].join('');
        label.innerHTML = `
            <div class="flex items-center gap-3">
                <input type="checkbox" value="${lead.id}" class="lead-checkbox form-checkbox h-4.5 w-4.5 rounded text-indigo-600 border-slate-300">
                <div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">${escapeHtml(lead.business_name)}</div>
                    <div class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2">
                        <span>${escapeHtml(lead.city || '')}</span>
                        <span class="px-1 rounded border font-bold text-indigo-500">${escapeHtml(lead.niche || '')}</span>
                        ${badges}
                    </div>
                </div>
            </div>`;
        const cb = label.querySelector('.lead-checkbox');
        cb.checked = selectedLeads.has(lead.id);
        toggleLeadHighlight(cb);
        cb.addEventListener('change', () => {
            cb.checked ? selectedLeads.set(lead.id, lead.business_name) : selectedLeads.delete(lead.id);
            toggleLeadHighlight(cb);
            updateLeadCounters();
        });
        return label;
    }

    async function loadLeads(reset = true) {
        if (!leadsList) return;
        leadPage = reset ? 1 : leadPage + 1;
        leadController?.abort();
        leadController = new AbortController();

        try {
            const response = await fetch(`${leadsFilterUrl}?${leadFilterParams({ page: leadPage, per_page: 30 })}`, {
                headers: { 'Accept': 'application/json' },
                signal: leadController.signal,
            });
            const data = await response.json();
            if (reset) leadsList.innerHTML = '';

            if (reset && data.leads.length === 0) {
                leadsList.innerHTML = '<div class="text-center py-8 text-xs font-bold text-slate-400">Tidak ada lead yang cocok dengan filter.</div>';
            }
            data.leads.forEach(lead => leadsList.appendChild(renderLeadItem(lead)));

            const shown = leadsList.querySelectorAll('.lead-item').length;
            updateLeadCounters(shown, data.pagination.total, data.pagination.has_more);
            if (selectAllCb) selectAllCb.checked = false;
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        }
    }

    // Ambil semua ID yang cocok dengan filter (maks. 500) lalu tambahkan ke pilihan
    async function selectAllMatching(extra = {}) {
        const response = await fetch(`${leadsFilterUrl}?${leadFilterParams({ ids_only: 1, ...extra })}`, { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        data.leads.forEach(lead => selectedLeads.set(lead.id, lead.business_name));
        leadsList.querySelectorAll('.lead-checkbox').forEach(cb => {
            cb.checked = selectedLeads.has(parseInt(cb.value));
            toggleLeadHighlight(cb);
        });
        updateLeadCounters();
        return data;
    }

    const applyLeadFilters = () => loadLeads(true);

    // Initial templates fetch
    async function loadComposerTemplates() {
        try {
            const response = await fetch('{{ route("outreach.templates.filter") }}');
            const data = await response.json();
            if (data.status === 'success') {
                allTemplates = data.templates;
                fetchComposerTemplates(); // Filter and render matching templates
            }
        } catch (e) {
            console.error("Failed to preload templates", e);
        }
    }

    const campaignNicheMap = @json($campaigns->pluck('niche', 'id')->map(fn($n) => strtolower($n)));

    function getSelectedCampaignNiche() {
        const selectedCampaignId = document.getElementById('composer_campaign_id').value;
        return campaignNicheMap[selectedCampaignId] || '';
    }

    function updateSelectedNicheAndLocation() {
        fetchComposerTemplates();
    }

    function fetchComposerTemplates() {
        const channel = document.getElementById('composer_type').value;
        const campaignNiche = getSelectedCampaignNiche();
        const templateInput = document.getElementById('composer_template_id');
        const templateComboboxEl = document.getElementById('composer-template-select');
        const templateCombobox = templateComboboxEl ? templateComboboxEl.combobox : null;
        const templateOptionsContainer = templateComboboxEl ? templateComboboxEl.querySelector('.combobox-options') : null;
        if (!templateOptionsContainer) return;

        templateOptionsContainer.innerHTML = `
            <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" data-value="">Pilih template...</div>
        `;

        // Filter preloaded templates
        const filtered = allTemplates.filter(t => {
            const matchesChannel = t.channel === channel;
            const matchesNiche = !campaignNiche 
                || t.niche.includes(campaignNiche) 
                || campaignNiche.includes(t.niche) 
                || t.niche === 'general';
            return matchesChannel && matchesNiche;
        });

        // Fallback: if no niche match, show all active templates for that channel
        const toRender = filtered.length > 0 ? filtered : allTemplates.filter(t => t.channel === channel);

        toRender.forEach(t => {
            const optionEl = document.createElement('div');
            optionEl.className = 'combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium';
            optionEl.setAttribute('data-value', t.id);
            optionEl.textContent = `${t.name} (${t.niche} - ${t.tone})`;
            templateOptionsContainer.appendChild(optionEl);
        });

        if (templateCombobox) {
            templateCombobox.setupOptions();
        }

        // If we have templates, auto-select the first one
        if (toRender.length > 0 && templateCombobox) {
            templateCombobox.selectByValue(String(toRender[0].id));
        } else if (templateInput) {
            templateInput.value = '';
        }
    }

    function handleModeChange() {
        const mode = document.getElementById('composer_mode').value;
        const tplWrapper = document.getElementById('composer_template_wrapper');
        const offerWrapper = document.getElementById('composer_offer_wrapper');
        const senderWrapper = document.getElementById('composer_sender_wrapper');

        if (mode === 'ai') {
            tplWrapper.classList.add('hidden');
        } else {
            tplWrapper.classList.remove('hidden');
        }
    }

    function toggleLeadHighlight(cb) {
        const row = cb.closest('.lead-item');
        if (!row) return;
        row.classList.toggle('bg-indigo-500/10', cb.checked);
        row.classList.toggle('border-indigo-500/25', cb.checked);
        row.classList.toggle('border-slate-100', !cb.checked);
    }

    if (selectAllCb) {
        selectAllCb.addEventListener('change', async function() {
            if (selectAllCb.checked) {
                const data = await selectAllMatching();
                window.showToast(`${data.leads.length} lead hasil filter dipilih${data.truncated ? ' (dibatasi 500)' : ''}.`, 'success');
            } else {
                leadsList.querySelectorAll('.lead-checkbox').forEach(cb => {
                    cb.checked = false;
                    selectedLeads.delete(parseInt(cb.value));
                    toggleLeadHighlight(cb);
                });
                updateLeadCounters();
            }
        });
    }

    leadsLoadMoreBtn?.addEventListener('click', () => loadLeads(false));
    leadsClearBtn?.addEventListener('click', () => {
        selectedLeads.clear();
        leadsList.querySelectorAll('.lead-checkbox').forEach(cb => { cb.checked = false; toggleLeadHighlight(cb); });
        if (selectAllCb) selectAllCb.checked = false;
        updateLeadCounters();
    });

    // Smart Select: pilih semua lead hasil filter yang punya kontak untuk channel terpilih
    if (btnSmartSelect) {
        btnSmartSelect.addEventListener('click', async function() {
            const channel = document.getElementById('composer_type').value;
            const data = await selectAllMatching(channel === 'whatsapp' ? { has_phone: 'yes' } : { has_email: 'yes' });
            const contact = channel === 'whatsapp' ? 'nomor telepon' : 'email';

            if (data.leads.length > 0) {
                window.showToast(`${data.leads.length} lead dengan ${contact} dipilih.`, 'success');
            } else {
                window.showToast(`Tidak ada lead dengan ${contact} di filter ini.`, 'error');
            }
        });
    }

    leadSearchInput?.addEventListener('input', debounce(applyLeadFilters, 300));
    leadNicheFilterInput?.addEventListener('change', applyLeadFilters);
    leadCityFilterInput?.addEventListener('change', applyLeadFilters);

    [filterHasEmailBtn, filterHasPhoneBtn, filterHasWebsiteBtn].forEach(btn => {
        btn?.addEventListener('click', () => {
            setFilterButtonState(btn, btn.dataset.active !== '1');
            applyLeadFilters();
        });
    });

    loadLeads(true);

    // Modal actions
    function openComposerPreviewModal() {
        previewModal.classList.remove('hidden');
        setTimeout(() => {
            previewModal.classList.remove('opacity-0');
            previewModalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeComposerPreviewModal() {
        previewModal.classList.add('opacity-0');
        previewModalContent.classList.add('scale-95');
        setTimeout(() => {
            previewModal.classList.add('hidden');
            previewBody.innerHTML = '';
        }, 300);
    }

    // Dynamic compose and preview trigger
    async function prepareComposerPreview() {
        const campaignId = document.getElementById('composer_campaign_id').value;
        const type = document.getElementById('composer_type').value;
        const mode = document.getElementById('composer_mode').value;
        const templateId = document.getElementById('composer_template_id').value;
        const offer = document.getElementById('composer_offer').value.trim();
        const senderName = document.getElementById('composer_sender').value.trim();

        // Validate leads selection
        const checkedLeads = [...selectedLeads.entries()].map(([id, name]) => ({ id, name }));
        if (checkedLeads.length === 0) {
            window.showToast('Pilih minimal 1 lead target!', 'error');
            return;
        }

        if (!campaignId) {
            window.showToast('Pilih campaign tujuan terlebih dahulu!', 'error');
            return;
        }

        if (mode !== 'ai' && !templateId) {
            window.showToast('Pilih template outreach terlebih dahulu!', 'error');
            return;
        }

        // Set mode label in footer
        let modeLabel = 'Hybrid';
        if (mode === 'template') modeLabel = 'Template';
        if (mode === 'ai') modeLabel = 'AI Generate';
        document.getElementById('composer-footer-mode-label').textContent = modeLabel;

        // Open modal and show skeleton loaders
        openComposerPreviewModal();
        previewBody.innerHTML = checkedLeads.map(({ name }) => {
            return `
                <div class="glass-card p-5 border border-slate-200 dark:border-slate-800 space-y-4">
                    <div class="flex justify-between items-center pb-2 border-b dark:border-slate-800">
                        <span class="text-xs font-bold text-slate-800 dark:text-white">${escapeHtml(name)}</span>
                        <div class="h-4 bg-slate-200 dark:bg-slate-800 w-16 rounded animate-pulse"></div>
                    </div>
                    <div class="space-y-2 animate-pulse">
                        <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-1/3"></div>
                        <div class="h-16 bg-slate-200 dark:bg-slate-800 rounded w-full"></div>
                    </div>
                </div>
            `;
        }).join('');

        const leadIds = checkedLeads.map(lead => lead.id);

        try {
            const response = await fetch('{{ route("outreach.compose.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    campaign_id: campaignId,
                    lead_ids: leadIds,
                    mode: mode,
                    template_id: templateId || null,
                    type: type,
                    offer: offer,
                    sender_name: senderName
                })
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                renderComposerPreviews(data.previews, type);
            } else {
                closeComposerPreviewModal();
                window.showToast(data.message || 'Gagal membuat pratinjau.', 'error');
            }
        } catch (e) {
            closeComposerPreviewModal();
            console.error(e);
            window.showToast('Kesalahan jaringan saat membuat pratinjau.', 'error');
        }
    }

    function renderComposerPreviews(previews, type) {
        previewBody.innerHTML = previews.map(item => {
            return `
                <div class="composer-lead-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 hover:border-indigo-500/25 transition-all space-y-4" data-lead-id="${item.lead_id}" data-variant="${escapeHtml(item.variant || '')}">
                    <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-800/80 pb-3">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">${escapeHtml(item.business_name)}</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5"><svg class="w-3 h-3 align-[-2px] inline-block shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg> ${escapeHtml(item.city)} &bull; <span class="capitalize">${escapeHtml(item.niche)}</span> &bull; ${escapeHtml(item.email || item.phone || 'Tidak ada kontak')}</p>
                        </div>
                        <div class="flex gap-1.5 items-center">
                            ${item.is_fallback ? `<span class="px-2 py-0.5 text-[9px] font-bold bg-amber-500/10 text-amber-600 border border-amber-500/15 rounded inline-flex items-center gap-1" title="${escapeHtml(item.fallback_reason)}"><svg class="w-3 h-3 inline-block shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg> Cadangan (AI gagal)</span>` : ''}
                            <span class="px-2 py-0.5 text-[9px] font-bold rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 uppercase">${type}</span>
                        </div>
                    </div>
                    
                    ${(item.problems || []).length ? `
                    <div class="text-[11px] rounded-xl border border-amber-500/20 bg-amber-500/5 text-amber-700 dark:text-amber-400 px-3 py-2">
                        <strong>Cek dulu sebelum disimpan:</strong> ${item.problems.map(escapeHtml).join(' · ')}
                    </div>
                    ` : ''}

                    ${type === 'email' ? `
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Subjek</label>
                        <input type="text" class="composer-subject-input form-input text-xs" value="${escapeHtml(item.subject || '')}" placeholder="Subjek email">
                    </div>
                    ` : ''}
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Isi Pesan</label>
                        <textarea class="composer-message-textarea form-input text-xs font-sans leading-relaxed resize-y" rows="7">${escapeHtml(item.message)}</textarea>
                    </div>

                    {{-- Dynamic on-the-fly Polish --}}
                    <div class="bg-indigo-50/30 dark:bg-indigo-950/10 border border-indigo-100/50 dark:border-indigo-850/50 rounded-xl p-3 flex flex-col gap-2">
                        <label class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span>Poles dengan AI khusus untuk lead ini</span>
                        </label>
                        <div class="flex gap-2 items-center">
                            <input type="text" class="composer-polish-prompt form-input text-2xs py-1" placeholder="mis. 'Tambahkan promo diskon 15%', 'Buat ajakan penutupnya lebih singkat'">
                            <button type="button" onclick="polishCardMessage(${item.lead_id}, this)" class="btn-primary py-1 px-3 text-[10px] shrink-0 font-bold">Poles</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function polishCardMessage(leadId, button) {
        const card = button.closest('.composer-lead-card');
        const messageTextarea = card.querySelector('.composer-message-textarea');
        const subjectInput = card.querySelector('.composer-subject-input');
        const promptInput = card.querySelector('.composer-polish-prompt');
        const channel = document.getElementById('composer_type').value;

        const customPrompt = promptInput.value.trim();
        if (!customPrompt) {
            window.showToast('Tulis instruksi poles terlebih dahulu!', 'error');
            return;
        }

        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="animate-spin h-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        button.disabled = true;

        try {
            const response = await fetch('{{ route("outreach.compose.polish") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    lead_id: leadId,
                    type: channel,
                    message: messageTextarea.value,
                    subject: subjectInput ? subjectInput.value : null,
                    custom_prompt: customPrompt
                })
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                messageTextarea.value = data.message;
                promptInput.value = '';
                window.showToast('Pesan berhasil dipoles AI!', 'success');
            } else {
                window.showToast(data.message || 'Gagal memoles pesan.', 'error');
            }
        } catch(e) {
            console.error(e);
            window.showToast('Kesalahan jaringan saat memoles pesan.', 'error');
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    async function saveComposerPipeline() {
        const campaignId = document.getElementById('composer_campaign_id').value;
        const type = document.getElementById('composer_type').value;
        const mode = document.getElementById('composer_mode').value;
        const templateId = document.getElementById('composer_template_id').value;
        const saveBtn = document.getElementById('btn-save-composer-pipeline');

        const cards = document.querySelectorAll('.composer-lead-card');
        const messages = [];

        cards.forEach(card => {
            const leadId = parseInt(card.getAttribute('data-lead-id'));
            const subjectEl = card.querySelector('.composer-subject-input');
            const messageEl = card.querySelector('.composer-message-textarea');

            messages.push({
                lead_id: leadId,
                subject: subjectEl ? subjectEl.value : null,
                message: messageEl.value,
                variant: card.dataset.variant || null
            });
        });

        if (messages.length === 0) return;

        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = 'Menyimpan...';
        saveBtn.disabled = true;

        try {
            const response = await fetch('{{ route("outreach.compose.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    campaign_id: campaignId,
                    type: type,
                    mode: mode,
                    template_id: templateId || null,
                    messages: messages
                })
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                closeComposerPreviewModal();
                window.showToast(data.message, 'success');
                // Reload page data to reflect the newly saved outreach pipeline messages!
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.showToast(data.message || 'Gagal menyimpan ke pipeline.', 'error');
            }
        } catch (e) {
            console.error(e);
            window.showToast('Kesalahan jaringan saat menyimpan.', 'error');
        } finally {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const campaignInput = document.getElementById('composer_campaign_id');
        const typeInput = document.getElementById('composer_type');
        const modeInput = document.getElementById('composer_mode');

        if (campaignInput) {
            campaignInput.addEventListener('change', updateSelectedNicheAndLocation);
        }
        if (typeInput) {
            typeInput.addEventListener('change', fetchComposerTemplates);
        }
        if (modeInput) {
            modeInput.addEventListener('change', handleModeChange);
        }

        loadComposerTemplates();
        handleModeChange();
        applyLeadFilters();
    });

    // Close preview modal on backdrop click
    previewModal.addEventListener('click', function(e) {
        if (e.target === previewModal) closeComposerPreviewModal();
    });

    // Close preview modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !previewModal.classList.contains('hidden')) {
            closeComposerPreviewModal();
        }
    });

    // ==========================================
    // ===== OUTREACH TABLE pipeline LOGIC =====
    // ==========================================
    const selectAllOutreach = document.getElementById('selectAllOutreach');
    const outreachCbs = document.querySelectorAll('.outreach-checkbox');
    
    if (selectAllOutreach) {
        selectAllOutreach.addEventListener('change', function() {
            outreachCbs.forEach(cb => {
                cb.checked = selectAllOutreach.checked;
            });
        });
    }

    // Konfirmasi aksi massal, menyebut kanal jika aksi dibatasi ke email / WhatsApp saja
    window.confirmOutreachBulk = function (event, form) {
        const scope = { email: ' email', whatsapp: ' WhatsApp' }[form.elements.channel?.value] || '';
        const note = scope ? ` Pesan kanal lain yang ikut dipilih tidak diubah.` : '';

        return handleConfirm(event, form, 'Jalankan Aksi Massal?', `Yakin ingin menjalankan aksi ini untuk pesan${scope} yang dipilih?${note}`, 'Ya, Jalankan');
    };
</script>
@endsection
