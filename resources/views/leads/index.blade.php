@extends('layouts.app')

@section('title', 'Leads — Sandesa')
@section('header', 'Leads')

@section('content')
{{-- ===== SCRAPER FORM ===== --}}
<div class="glass-card p-6 mb-6" style="animation: fadeInUp 0.4s ease backwards;">
    <div class="flex items-center gap-3 mb-5">
        <div class="p-2 rounded-lg bg-pink-500/10 text-pink-600 dark:text-pink-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Cari Lead Baru</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Scrape bisnis dari Google Maps</p>
        </div>
    </div>

    <form action="{{ route('leads.scrape') }}" method="POST" class="flex flex-col md:flex-row gap-4 items-end">
        @csrf
        <div class="flex-1 w-full">
            <label for="niche" class="form-label">Niche / Kata Kunci</label>
            <input type="text" name="niche" id="niche" placeholder="mis. klinik gigi, cafe, agency" required class="form-input">
        </div>
        <div class="flex-1 w-full">
            <label for="location" class="form-label">Lokasi</label>
            <input type="text" name="location" id="location" placeholder="mis. Surabaya, Jakarta, Bali" required class="form-input">
        </div>
        <div class="w-full md:w-auto">
            <button type="submit" class="btn-pink w-full justify-center">
                <span>Scrape Lead</span>
            </button>
        </div>
    </form>
</div>

{{-- ===== LEADS TABLE ===== --}}
<div class="glass-card p-6" style="animation: fadeInUp 0.4s ease backwards; animation-delay: 0.1s;">
    @if(session('success'))
        <div class="alert-success mb-6">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert-error mb-6">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($pendingScrapes > 0)
        <div class="mb-6 p-4 rounded-xl border border-indigo-500/20 bg-indigo-500/5 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="relative flex items-center justify-center flex-shrink-0">
                    <div class="w-8 h-8 rounded-full border-2 border-indigo-500 border-t-transparent" style="animation: spin 1s linear infinite;"></div>
                    <div class="absolute text-indigo-500"><x-icon name="signal" class="w-3.5 h-3.5" /></div>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white">Scraping sedang berjalan...</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Mengambil lead dari Google Maps di latar belakang. Notifikasi akan muncul di ikon lonceng kanan atas setelah selesai.</p>
                </div>
            </div>
            <span class="text-[11px] font-bold px-3 py-1 rounded-full bg-indigo-500/15 text-indigo-600 dark:text-indigo-400">
                {{ $pendingScrapes }} job aktif
            </span>
        </div>
    @endif

    {{-- Search --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </div>
            <div class="flex items-center gap-2.5">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Semua Lead</h3>
                <button type="button" 
                        onclick="openCreateModal()" 
                        class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] transition shadow-xs flex items-center gap-1 cursor-pointer border-0 outline-none">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Lead</span>
                </button>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <a href="{{ route('leads.export', request()->only(['search', 'has_email', 'has_phone', 'has_website', 'no_website', 'stage'])) }}" class="btn-secondary py-1.5 px-3 text-xs font-bold" title="Export leads (sesuai filter) ke CSV"><x-icon name="download" class="w-3.5 h-3.5" /> Export CSV</a>
            <button type="button" onclick="document.getElementById('import-panel').classList.toggle('hidden')" class="btn-secondary py-1.5 px-3 text-xs font-bold"><x-icon name="upload" class="w-3.5 h-3.5" /> Import CSV</button>
        </div>
    </div>

    {{-- Import CSV --}}
    <div id="import-panel" class="hidden mb-6 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/30">
        <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-3 items-end">
            @csrf
            <div class="flex-1 w-full">
                <label class="form-label text-xs">File CSV</label>
                <input type="file" name="file" accept=".csv,text/csv" required class="form-input text-xs">
                <p class="text-[10px] text-slate-500 mt-1">Header yang dikenali: business_name/nama, city/kota, niche, email, phone/telepon, website, address/alamat, category, rating. Pemisah koma atau titik koma.</p>
            </div>
            <div class="w-full md:w-48">
                <label class="form-label text-xs">Niche default</label>
                <input type="text" name="default_niche" placeholder="mis. klinik gigi" class="form-input text-xs">
            </div>
            <div class="w-full md:w-48">
                <label class="form-label text-xs">Kota default</label>
                <input type="text" name="default_city" placeholder="jika kolom kota kosong" class="form-input text-xs">
            </div>
            <button type="submit" class="btn-primary py-2 px-4 text-xs font-bold">Import</button>
        </form>
    </div>

    {{-- Filter --}}
    <form action="{{ route('leads.index') }}" method="GET" class="mb-6 flex flex-col lg:flex-row items-stretch lg:items-center gap-2">
        <input type="text" name="search" placeholder="Cari nama, niche, kota, email..." value="{{ request('search') }}" class="form-input text-xs lg:w-64">
        @foreach(['has_email' => 'Email', 'has_phone' => 'WA / Telepon', 'has_website' => 'Website'] as $field => $label)
            <select name="{{ $field }}" onchange="this.form.submit()" class="form-input text-xs lg:w-auto">
                <option value="">{{ $label }}: semua</option>
                <option value="yes" @selected(request($field) === 'yes')>{{ $label }}: ada</option>
                <option value="no" @selected(request($field) === 'no' || ($field === 'has_website' && request('no_website')))>{{ $label }}: tidak ada</option>
            </select>
        @endforeach
        <select name="stage" onchange="this.form.submit()" class="form-input text-xs lg:w-auto">
            <option value="">Stage: semua</option>
            @foreach($stages as $value => $label)
                <option value="{{ $value }}" @selected(request('stage') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="min_score" onchange="this.form.submit()" class="form-input text-xs lg:w-auto" aria-label="Filter skor">
            <option value="">Skor: semua</option>
            <option value="70" @selected(request('min_score') == 70)>Hot (skor ≥ 70)</option>
            <option value="50" @selected(request('min_score') == 50)>Skor ≥ 50</option>
        </select>
        <input type="hidden" name="sort" value="{{ $sort === 'score' ? 'score' : '' }}">
        <button type="submit" class="btn-secondary py-1.5 px-4 text-xs">Filter</button>
        @if(array_filter($filters))
            <a href="{{ route('leads.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-white px-2">Reset</a>
        @endif
        <span class="lg:ml-auto text-[11px] text-slate-500 dark:text-slate-400 font-semibold whitespace-nowrap">{{ number_format($leads->total()) }} lead</span>
    </form>

    {{-- Bulk AI Actions Toolbar --}}
    <div id="bulk-actions-toolbar" class="hidden mb-6 p-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white border border-indigo-500/25 shadow-lg shadow-indigo-500/10 transition-all duration-300 transform scale-95 opacity-0 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white shadow-inner">
                <svg class="w-5 h-5 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-sm text-white"><span id="selected-count">0</span> Lead Dipilih</h4>
                <p class="text-[10px] text-indigo-100 mt-0.5">Pilih campaign dan channel untuk generate outreach massal</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
            <x-searchable-select name="campaign_id" 
                form="bulk-outreach-form" 
                required="true" 
                placeholder="Pilih campaign..." 
                :options="$campaigns->mapWithKeys(fn($c) => [$c->id => $c->name . ' (' . $c->niche . ')'])"
                triggerClass="!bg-white/10 !text-white !border-white/20 focus:!bg-indigo-900/50 focus:!text-white !rounded-xl text-xs font-semibold h-[38px] min-w-[170px] [&_span]:truncate [&_svg]:!text-white/80" />

            <x-searchable-select name="type" 
                form="bulk-outreach-form" 
                required="true" 
                selected="email"
                :options="['email' => 'Email', 'whatsapp' => 'WhatsApp']"
                triggerClass="!bg-white/10 !text-white !border-white/20 focus:!bg-indigo-900/50 focus:!text-white !rounded-xl text-xs font-semibold h-[38px] min-w-[120px] [&_span]:truncate [&_svg]:!text-white/80" />
            <button type="submit" form="bulk-outreach-form" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-white text-indigo-700 font-bold text-xs hover:bg-slate-50 transition shadow-md flex items-center justify-center gap-1.5">
                <x-icon name="sparkles" class="w-3.5 h-3.5" />
                <span>Generate</span>
            </button>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto md:border-l md:border-white/20 md:pl-4">
            <button type="button" onclick="submitLeadBulk('crawl')" class="px-3 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-white font-bold text-xs flex items-center gap-1.5 transition" title="Buka website setiap lead untuk mencari email & telepon yang belum ada">
                <x-icon name="envelope" class="w-3.5 h-3.5" /> Cari email
            </button>
            <button type="button" onclick="submitLeadBulk('audit')" class="px-3 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-white font-bold text-xs flex items-center gap-1.5 transition" title="Cek kecepatan mobile & HTTPS website lead (Google PageSpeed)">
                <x-icon name="signal" class="w-3.5 h-3.5" /> Audit website
            </button>
            <select id="bulk-stage-select" onchange="if (this.value) submitLeadBulk('stage', this.value)" class="px-2 py-2 rounded-lg bg-white/15 text-white font-bold text-xs border border-white/20 [&>option]:text-slate-800" aria-label="Pindahkan stage lead terpilih">
                <option value="">Pindah stage...</option>
                @foreach($stages as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Pilih semua hasil filter (lintas halaman) --}}
    <div id="select-all-banner" class="hidden mb-4 p-3 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-700 dark:text-indigo-300 flex flex-wrap items-center gap-2">
        <span id="select-all-text"></span>
        <button type="button" id="select-all-filter-btn" class="font-bold underline">Pilih semua {{ number_format($leads->total()) }} lead hasil filter</button>
    </div>

    <form action="{{ route('outreach.generate') }}" method="POST" id="bulk-outreach-form">
        @csrf
        <div class="select-all-fields"></div>
    </form>
    <form action="{{ route('leads.bulk') }}" method="POST" id="leads-bulk-form" class="hidden">
        @csrf
        <div class="select-all-fields"></div>
    </form>
    <div class="overflow-x-auto">
            <table class="fancy-table">
                <thead>
                    <tr>
                        <th style="width: 45px; padding-left: 16px;">
                            <input type="checkbox" id="select-all-leads" class="form-checkbox h-4.5 w-4.5 rounded text-indigo-600 border-slate-300 cursor-pointer">
                        </th>
                        <th>Nama Bisnis</th>
                        <th>Niche / Kota</th>
                        <th>Kontak</th>
                        <th>Rating</th>
                        <th>Stage</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'score' ? null : 'score', 'page' => null]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600" title="Urutkan berdasarkan skor prioritas">
                                Skor <x-icon name="chevron-down" class="w-3 h-3 {{ $sort === 'score' ? 'text-indigo-600' : 'opacity-40' }}" />
                            </a>
                        </th>
                        <th style="width: 120px; text-align: right; padding-right: 20px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    <tr class="lead-row" id="lead-row-{{ $lead->id }}">
                        <td style="padding-left: 16px;">
                            <input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" form="bulk-outreach-form" class="lead-checkbox form-checkbox h-4.5 w-4.5 rounded text-indigo-600 border-slate-300 cursor-pointer">
                        </td>
                        <td>
                            <a href="{{ route('leads.show', $lead) }}" class="font-bold text-slate-900 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition">{{ $lead->business_name }}</a>
                            @if($lead->category)
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $lead->category }}</div>
                            @endif
                            @if($lead->address)
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate max-w-[200px]" title="{{ $lead->address }}">{{ $lead->address }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-sent bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">{{ $lead->niche }}</span>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ $lead->city }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="space-y-1 contact-list" data-lead-id="{{ $lead->id }}">
                                @if($lead->email)
                                    <a href="mailto:{{ $lead->email }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold flex items-center gap-1 email-item">
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span class="email-value">{{ $lead->email }}</span>
                                    </a>
                                @endif
                                @if($lead->phone)
                                    <a href="tel:{{ $lead->phone }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold flex items-center gap-1 phone-item">
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <span class="phone-value">{{ $lead->phone }}</span>
                                    </a>
                                @endif
                                @if($lead->website)
                                    <a href="{{ \App\Helpers\Url::normalize($lead->website) ?? '#' }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold flex items-center gap-1 website-item">
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        <span>Website</span>
                                    </a>
                                @endif
                                @if(!$lead->email && !$lead->phone && !$lead->website)
                                    <span class="text-slate-400 dark:text-slate-600 text-xs no-contact-label">Belum ada kontak</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($lead->rating)
                                <div class="text-xs font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap inline-flex items-center gap-1"><x-icon name="star" class="w-3.5 h-3.5" /> {{ number_format($lead->rating, 1) }}</div>
                                @if($lead->reviews_count)
                                    <div class="text-[10px] text-slate-400">{{ number_format($lead->reviews_count) }} ulasan</div>
                                @endif
                            @else
                                <span class="text-[10px] text-slate-400">—</span>
                            @endif
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-500/10 text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1 inline-block">{{ $lead->source }}</span>
                        </td>
                        <td>
                            @include('leads.partials.stage-badge', ['stage' => $lead->pipeline_stage])
                        </td>
                        <td>
                            @include('leads.partials.score-badge', ['lead' => $lead])
                        </td>
                        <td style="text-align: right; padding-right: 20px;">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- Details (Read) Button --}}
                                <button type="button" 
                                        onclick="openViewModal(this)"
                                        data-id="{{ $lead->id }}"
                                        data-business-name="{{ $lead->business_name }}"
                                        data-niche="{{ $lead->niche }}"
                                        data-website="{{ $lead->website }}"
                                        data-email="{{ $lead->email }}"
                                        data-phone="{{ $lead->phone }}"
                                        data-address="{{ $lead->address }}"
                                        data-city="{{ $lead->city }}"
                                        data-source="{{ $lead->source }}"
                                        data-created-at="{{ $lead->created_at->translatedFormat('d M Y, H:i') }}"
                                        class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-800 dark:hover:text-white transition shadow-2xs cursor-pointer border-0 outline-none flex items-center justify-center shrink-0"
                                        title="Lihat Detail">
                                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                
                                {{-- Edit Button --}}
                                <button type="button" 
                                        onclick="openEditModal(this)"
                                        data-id="{{ $lead->id }}"
                                        data-business-name="{{ $lead->business_name }}"
                                        data-niche="{{ $lead->niche }}"
                                        data-website="{{ $lead->website }}"
                                        data-email="{{ $lead->email }}"
                                        data-phone="{{ $lead->phone }}"
                                        data-address="{{ $lead->address }}"
                                        data-city="{{ $lead->city }}"
                                        data-source="{{ $lead->source }}"
                                        class="p-1.5 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition shadow-2xs cursor-pointer border-0 outline-none flex items-center justify-center shrink-0"
                                        title="Edit Lead">
                                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-2.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>

                                {{-- Crawl Website Button --}}
                                @if($lead->website && (!$lead->email || !$lead->phone))
                                    <button type="button" 
                                            onclick="crawlLeadWebsite(this, '{{ $lead->id }}')"
                                            class="p-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/25 text-emerald-600 dark:text-emerald-400 hover:text-white transition shadow-2xs cursor-pointer border-0 outline-none flex items-center justify-center shrink-0 crawl-btn"
                                            title="Cari kontak yang belum ada dari website">
                                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    </button>
                                @endif
                                
                                {{-- Delete Button --}}
                                <form action="{{ route('leads.destroy', $lead) }}" method="POST" onsubmit="return handleConfirm(event, this, 'Hapus Lead?', 'Yakin ingin menghapus lead ini? Semua pesan outreach terkait juga akan dihapus permanen.', 'Ya, Hapus')" class="inline shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-600 hover:text-white transition shadow-2xs cursor-pointer border-0 outline-none flex items-center justify-center"
                                            title="Hapus Lead">
                                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state py-12">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <p class="font-semibold text-slate-500 dark:text-slate-400">Belum ada lead</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Gunakan form di atas untuk mulai scraping.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    <div class="mt-6">
        {{ $leads->links() }}
    </div>
</div>

{{-- ===== VIEW DETAILS MODAL ===== --}}
<div id="view-lead-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity opacity-0 duration-300">
    <div class="glass-card w-full max-w-lg p-0 relative overflow-hidden transform scale-95 transition-transform duration-300 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800" id="view-lead-modal-content">
        {{-- Header --}}
        <div class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/60 p-5 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>Detail Profil Lead</span>
            </h3>
            <button type="button" onclick="closeViewModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        {{-- Body --}}
        <div class="p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg font-black shrink-0" id="view-initials">
                    LH
                </div>
                <div class="overflow-hidden">
                    <h4 class="text-base font-extrabold text-slate-900 dark:text-white truncate" id="view-business-name">Nama Bisnis</h4>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span class="badge bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 text-[10px]" id="view-niche">Niche</span>
                        <span class="badge bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/15 text-[10px]" id="view-city">Kota</span>
                        <span class="badge bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/15 text-[10px] uppercase" id="view-source">Sumber</span>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                {{-- Email Card --}}
                <div class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="overflow-hidden min-w-0">
                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Alamat Email</span>
                        <a href="" id="view-email-link" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block">Tidak tersedia</a>
                    </div>
                </div>

                {{-- Phone Card --}}
                <div class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div class="overflow-hidden min-w-0">
                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Nomor Telepon</span>
                        <a href="" id="view-phone-link" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block">Tidak tersedia</a>
                    </div>
                </div>

                {{-- Website Card --}}
                <div class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </div>
                    <div class="overflow-hidden min-w-0">
                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">URL Website</span>
                        <a href="" target="_blank" id="view-website-link" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block">Tidak tersedia</a>
                    </div>
                </div>

                {{-- Scraped Date Card --}}
                <div class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="overflow-hidden min-w-0">
                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Tanggal Ditemukan</span>
                        <span id="view-created-at" class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate block">Tanggal</span>
                    </div>
                </div>
            </div>

            {{-- Address Field --}}
            <div class="mb-6 p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block mb-1">Alamat Lengkap</span>
                <p id="view-address" class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-semibold">Alamat lengkap...</p>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeViewModal()" class="btn-secondary px-6 py-2.5 text-xs font-bold">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- ===== CREATE LEAD MODAL ===== --}}
<div id="create-lead-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity opacity-0 duration-300">
    <div class="glass-card w-full max-w-xl p-0 relative overflow-hidden transform scale-95 transition-transform duration-300 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800" id="create-lead-modal-content">
        {{-- Header --}}
        <div class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/60 p-5 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Lead Baru</span>
            </h3>
            <button type="button" onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        {{-- Body --}}
        <div class="p-6">
            <form id="create-lead-form" action="{{ route('leads.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="create-business-name" class="form-label text-[10px]">Nama Bisnis</label>
                        <input type="text" name="business_name" id="create-business-name" placeholder="mis. Acme Corporation" required class="form-input text-xs">
                    </div>
                    <div>
                        <label for="create-niche" class="form-label text-[10px]">Niche</label>
                        <input type="text" name="niche" id="create-niche" placeholder="mis. Digital Agency, Klinik Gigi" required class="form-input text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="create-city" class="form-label text-[10px]">Kota</label>
                        <input type="text" name="city" id="create-city" placeholder="mis. Surabaya" required class="form-input text-xs">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">Sumber</label>
                        <x-searchable-select name="source" 
                            id="create-source" 
                            required="true" 
                            selected="custom"
                            :options="['custom' => 'Input Manual', 'google-maps' => 'Google Maps', 'website' => 'Website']"
                            triggerClass="text-xs !py-2 !rounded-lg" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="sm:col-span-1">
                        <label for="create-phone" class="form-label text-[10px]">Nomor Telepon</label>
                        <input type="text" name="phone" id="create-phone" placeholder="mis. +62812345678" class="form-input text-xs">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="create-email" class="form-label text-[10px]">Alamat Email</label>
                        <input type="email" name="email" id="create-email" placeholder="mis. hello@acme.com" class="form-input text-xs">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="create-website" class="form-label text-[10px]">URL Website</label>
                        <input type="text" name="website" id="create-website" placeholder="mis. https://acme.com" class="form-input text-xs">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="create-address" class="form-label text-[10px]">Alamat Lengkap</label>
                    <textarea name="address" id="create-address" rows="3" placeholder="mis. Jl. Basuki Rahmat No. 12" class="form-input resize-none text-xs"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="closeCreateModal()" class="btn-secondary px-5 py-2.5 text-xs font-bold">Batal</button>
                    <button type="submit" class="btn-primary shadow-lg shadow-indigo-500/20 px-6 py-2.5 text-xs">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>Tambah Lead</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== EDIT LEAD MODAL ===== --}}
<div id="edit-lead-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity opacity-0 duration-300">
    <div class="glass-card w-full max-w-xl p-0 relative overflow-hidden transform scale-95 transition-transform duration-300 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800" id="edit-lead-modal-content">
        {{-- Header --}}
        <div class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/60 p-5 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span>Edit Profil Lead</span>
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        {{-- Body --}}
        <div class="p-6">
            <form id="edit-lead-form" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="edit-business-name" class="form-label text-[10px]">Nama Bisnis</label>
                        <input type="text" name="business_name" id="edit-business-name" required class="form-input text-xs">
                    </div>
                    <div>
                        <label for="edit-niche" class="form-label text-[10px]">Niche</label>
                        <input type="text" name="niche" id="edit-niche" required class="form-input text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="edit-city" class="form-label text-[10px]">Kota</label>
                        <input type="text" name="city" id="edit-city" required class="form-input text-xs">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">Sumber</label>
                        <x-searchable-select name="source" 
                            id="edit-source" 
                            required="true" 
                            :options="['google-maps' => 'Google Maps', 'website' => 'Website', 'custom' => 'Input Manual']"
                            triggerClass="text-xs !py-2 !rounded-lg" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="sm:col-span-1">
                        <label for="edit-phone" class="form-label text-[10px]">Nomor Telepon</label>
                        <input type="text" name="phone" id="edit-phone" class="form-input text-xs">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="edit-email" class="form-label text-[10px]">Alamat Email</label>
                        <input type="email" name="email" id="edit-email" class="form-input text-xs">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="edit-website" class="form-label text-[10px]">URL Website</label>
                        <input type="text" name="website" id="edit-website" class="form-input text-xs">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="edit-address" class="form-label text-[10px]">Alamat Lengkap</label>
                    <textarea name="address" id="edit-address" rows="3" class="form-input resize-none text-xs"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="closeEditModal()" class="btn-secondary px-5 py-2.5 text-xs font-bold">Batal</button>
                    <button type="submit" class="btn-primary shadow-lg shadow-indigo-500/20 px-6 py-2.5 text-xs">
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
document.addEventListener('DOMContentLoaded', function() {
    initBulkActions();
});

// Use event delegation / custom lifecycle init to support dynamically replaced DOM from polling
function initBulkActions() {
    const selectAllCheckbox = document.getElementById('select-all-leads');
    const bulkToolbar = document.getElementById('bulk-actions-toolbar');
    const selectedCountSpan = document.getElementById('selected-count');
    
    if (!selectAllCheckbox) return;

    function getLeadCheckboxes() {
        return document.querySelectorAll('.lead-checkbox');
    }

    function updateBulkToolbar() {
        const checkedCount = document.querySelectorAll('.lead-checkbox:checked').length;
        const allOnPage = checkedCount > 0 && checkedCount === getLeadCheckboxes().length;

        if (!allOnPage) {
            window.leadSelectAllFilter = false;
        }
        updateSelectAllBanner(checkedCount, allOnPage);

        if (checkedCount > 0) {
            bulkToolbar.classList.remove('hidden');
            setTimeout(() => {
                bulkToolbar.style.opacity = '1';
                bulkToolbar.style.transform = 'scale(1)';
            }, 10);
            selectedCountSpan.textContent = window.leadSelectAllFilter ? LEAD_TOTAL.toLocaleString('id-ID') : checkedCount;
        } else {
            bulkToolbar.style.opacity = '0';
            bulkToolbar.style.transform = 'scale(0.95)';
            setTimeout(() => {
                bulkToolbar.classList.add('hidden');
            }, 300);
        }
    }

    selectAllCheckbox.addEventListener('change', function() {
        getLeadCheckboxes().forEach(cb => {
            cb.checked = selectAllCheckbox.checked;
        });
        updateBulkToolbar();
    });

    // "Pilih semua hasil filter": aksi massal berlaku untuk semua lead yang cocok, bukan hanya halaman ini
    const banner = document.getElementById('select-all-banner');
    const bannerText = document.getElementById('select-all-text');
    const bannerBtn = document.getElementById('select-all-filter-btn');

    function updateSelectAllBanner(checkedCount, allOnPage) {
        const pageCount = getLeadCheckboxes().length;
        if (!allOnPage || LEAD_TOTAL <= pageCount) {
            banner.classList.add('hidden');
            return;
        }
        banner.classList.remove('hidden');
        if (window.leadSelectAllFilter) {
            bannerText.textContent = `Semua ${LEAD_TOTAL.toLocaleString('id-ID')} lead hasil filter dipilih.`;
            bannerBtn.textContent = 'Batalkan';
        } else {
            bannerText.textContent = `${pageCount} lead di halaman ini dipilih.`;
            bannerBtn.textContent = `Pilih semua ${LEAD_TOTAL.toLocaleString('id-ID')} lead hasil filter`;
        }
    }

    bannerBtn.addEventListener('click', () => {
        window.leadSelectAllFilter = !window.leadSelectAllFilter;
        updateBulkToolbar();
    });

    document.getElementById('bulk-outreach-form').addEventListener('submit', function () {
        fillSelectionFields(this);
    });

    // Delegate checkbox changes to the document body to survive HTML replacement from scraping refresh
    document.body.addEventListener('change', function(e) {
        if (e.target.classList.contains('lead-checkbox')) {
            updateBulkToolbar();
            const leadCheckboxes = getLeadCheckboxes();
            const checkedCount = document.querySelectorAll('.lead-checkbox:checked').length;
            
            if (!e.target.checked) {
                selectAllCheckbox.checked = false;
            } else if (checkedCount === leadCheckboxes.length) {
                selectAllCheckbox.checked = true;
            }
        }
    });
}

const LEAD_TOTAL = {{ (int) $leads->total() }};
const LEAD_FILTERS = @js(array_filter($filters));
window.leadSelectAllFilter = false;

// Isi form aksi massal dengan pilihan saat ini: daftar ID, atau "semua hasil filter" + parameter filternya
function fillSelectionFields(form) {
    const box = form.querySelector('.select-all-fields');
    box.innerHTML = '';
    const add = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        box.appendChild(input);
    };

    if (window.leadSelectAllFilter) {
        add('select_all', '1');
        Object.entries(LEAD_FILTERS).forEach(([key, value]) => add(key, value));
    } else if (form.id !== 'bulk-outreach-form') {
        // bulk-outreach-form sudah menerima checkbox lewat atribut form=""
        document.querySelectorAll('.lead-checkbox:checked').forEach(cb => add('lead_ids[]', cb.value));
    }
}

window.submitLeadBulk = function (action, stage = null) {
    const form = document.getElementById('leads-bulk-form');
    fillSelectionFields(form);
    const box = form.querySelector('.select-all-fields');
    [['action', action], ['pipeline_stage', stage]].forEach(([name, value]) => {
        if (value === null) return;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        box.appendChild(input);
    });
    form.submit();
};


// Global Modal handlers for View Details
window.openViewModal = function(btn) {
    const name = btn.getAttribute('data-business-name');
    const niche = btn.getAttribute('data-niche');
    const website = btn.getAttribute('data-website');
    const email = btn.getAttribute('data-email');
    const phone = btn.getAttribute('data-phone');
    const address = btn.getAttribute('data-address');
    const city = btn.getAttribute('data-city');
    const source = btn.getAttribute('data-source');
    const createdAt = btn.getAttribute('data-created-at');

    document.getElementById('view-business-name').textContent = name || '—';
    document.getElementById('view-niche').textContent = niche || '—';
    document.getElementById('view-city').textContent = city || '—';
    document.getElementById('view-source').textContent = source || '—';
    document.getElementById('view-created-at').textContent = createdAt || '—';
    document.getElementById('view-address').textContent = address || 'Alamat belum tercatat';

    // Initials
    const initials = name ? name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() : 'LH';
    document.getElementById('view-initials').textContent = initials;

    // Contact Links helper
    function setupLink(elId, val, prefix = '') {
        const link = document.getElementById(elId);
        if (val) {
            link.href = prefix + val;
            link.textContent = val;
            link.className = "text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block cursor-pointer";
        } else {
            link.removeAttribute('href');
            link.textContent = 'Tidak tersedia';
            link.className = "text-xs font-semibold text-slate-400 dark:text-slate-600 truncate block pointer-events-none";
        }
    }

    setupLink('view-email-link', email, 'mailto:');
    setupLink('view-phone-link', phone, 'tel:');
    
    // Website link needs proper prefixing if missing http
    const webLink = document.getElementById('view-website-link');
    if (website) {
        webLink.href = website.startsWith('http') ? website : `https://${website}`;
        webLink.textContent = website;
        webLink.className = "text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block cursor-pointer";
    } else {
        webLink.removeAttribute('href');
        webLink.textContent = 'Tidak tersedia';
        webLink.className = "text-xs font-semibold text-slate-400 dark:text-slate-600 truncate block pointer-events-none";
    }

    const viewModal = document.getElementById('view-lead-modal');
    const viewModalContent = document.getElementById('view-lead-modal-content');
    
    viewModal.classList.remove('hidden');
    setTimeout(() => {
        viewModal.classList.remove('opacity-0');
        viewModalContent.classList.remove('scale-95');
    }, 10);
};

window.closeViewModal = function() {
    const viewModal = document.getElementById('view-lead-modal');
    const viewModalContent = document.getElementById('view-lead-modal-content');
    viewModal.classList.add('opacity-0');
    viewModalContent.classList.add('scale-95');
    setTimeout(() => {
        viewModal.classList.add('hidden');
    }, 300);
};

// Global Modal handlers for Edit
window.openEditModal = function(btn) {
    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-business-name');
    const niche = btn.getAttribute('data-niche');
    const website = btn.getAttribute('data-website');
    const email = btn.getAttribute('data-email');
    const phone = btn.getAttribute('data-phone');
    const address = btn.getAttribute('data-address');
    const city = btn.getAttribute('data-city');
    const source = btn.getAttribute('data-source');

    const form = document.getElementById('edit-lead-form');
    form.action = `/leads/${id}`;
    
    document.getElementById('edit-business-name').value = name || '';
    document.getElementById('edit-niche').value = niche || '';
    document.getElementById('edit-city').value = city || '';
    if (document.getElementById('edit-source') && document.getElementById('edit-source').combobox) {
        document.getElementById('edit-source').combobox.selectByValue(source || 'google-maps');
    } else if (document.getElementById('edit-source')) {
        document.getElementById('edit-source').value = source || 'google-maps';
    }
    document.getElementById('edit-phone').value = phone || '';
    document.getElementById('edit-email').value = email || '';
    document.getElementById('edit-website').value = website || '';
    document.getElementById('edit-address').value = address || '';

    const editModal = document.getElementById('edit-lead-modal');
    const editModalContent = document.getElementById('edit-lead-modal-content');
    
    editModal.classList.remove('hidden');
    setTimeout(() => {
        editModal.classList.remove('opacity-0');
        editModalContent.classList.remove('scale-95');
    }, 10);
};

window.closeEditModal = function() {
    const editModal = document.getElementById('edit-lead-modal');
    const editModalContent = document.getElementById('edit-lead-modal-content');
    editModal.classList.add('opacity-0');
    editModalContent.classList.add('scale-95');
    setTimeout(() => {
        editModal.classList.add('hidden');
        document.getElementById('edit-lead-form').reset();
    }, 300);
};

// Global Modal handlers for Create
window.openCreateModal = function() {
    const createModal = document.getElementById('create-lead-modal');
    const createModalContent = document.getElementById('create-lead-modal-content');
    
    // Reset form
    document.getElementById('create-lead-form').reset();
    if (document.getElementById('create-source') && document.getElementById('create-source').combobox) {
        document.getElementById('create-source').combobox.selectByValue('custom');
    }
    
    createModal.classList.remove('hidden');
    setTimeout(() => {
        createModal.classList.remove('opacity-0');
        createModalContent.classList.remove('scale-95');
    }, 10);
};

window.closeCreateModal = function() {
    const createModal = document.getElementById('create-lead-modal');
    const createModalContent = document.getElementById('create-lead-modal-content');
    createModal.classList.add('opacity-0');
    createModalContent.classList.add('scale-95');
    setTimeout(() => {
        createModal.classList.add('hidden');
        document.getElementById('create-lead-form').reset();
    }, 300);
};

// Setup click-outside backdrop close
document.addEventListener('DOMContentLoaded', function() {
    const createModal = document.getElementById('create-lead-modal');
    const viewModal = document.getElementById('view-lead-modal');
    const editModal = document.getElementById('edit-lead-modal');
    
    if (createModal) {
        createModal.addEventListener('click', function(e) {
            if (e.target === createModal) {
                closeCreateModal();
            }
        });
    }
    
    if (viewModal) {
        viewModal.addEventListener('click', function(e) {
            if (e.target === viewModal) {
                closeViewModal();
            }
        });
    }
    
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) {
                closeEditModal();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (createModal && !createModal.classList.contains('hidden')) {
                closeCreateModal();
            }
            if (viewModal && !viewModal.classList.contains('hidden')) {
                closeViewModal();
            }
            if (editModal && !editModal.classList.contains('hidden')) {
                closeEditModal();
            }
        }
    });
});

// Dynamic Floating Glassmorphic Toast Notification
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-5 right-5 z-[200] flex flex-col gap-3 max-w-sm w-full';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `p-4 rounded-xl shadow-lg border transition-all duration-300 transform translate-y-5 opacity-0 flex items-center gap-3 backdrop-blur-md ` +
        (type === 'success' 
            ? 'bg-emerald-500/10 dark:bg-emerald-500/15 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' 
            : 'bg-rose-500/10 dark:bg-rose-500/15 border-rose-500/20 text-rose-600 dark:text-rose-400');
            
    const icon = type === 'success' 
        ? `<svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
        : `<svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`;
        
    toast.innerHTML = `
        ${icon}
        <span class="text-xs font-semibold leading-relaxed">${window.escapeHtml(message)}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer border-0 bg-transparent flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-y-5', 'opacity-0');
    }, 10);
    
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// AJAX handler for crawling missing lead contact info from website
window.crawlLeadWebsite = async function(btn, id) {
    const row = document.getElementById(`lead-row-${id}`);
    if (!row) return;

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.classList.add('cursor-wait');
    btn.innerHTML = `
        <svg class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    `;

    try {
        const response = await fetch(@js(url('leads')) + `/${id}/crawl-website`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            const data = result.data || {};
            const email = data.email;
            const phone = data.phone;
            
            const contactList = row.querySelector('.contact-list');
            if (contactList) {
                const noContact = contactList.querySelector('.no-contact-label');
                if (noContact) noContact.remove();

                const webItem = contactList.querySelector('.website-item');

                // Update/Insert Email Item
                if (email) {
                    let emailItem = contactList.querySelector('.email-item');
                    if (emailItem) {
                        emailItem.href = `mailto:${email}`;
                        const emailVal = emailItem.querySelector('.email-value');
                        if (emailVal) emailVal.textContent = email;
                    } else {
                        const newEmail = document.createElement('a');
                        newEmail.href = `mailto:${email}`;
                        newEmail.className = 'text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold flex items-center gap-1 email-item';
                        newEmail.innerHTML = `
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="email-value">${window.escapeHtml(email)}</span>
                        `;
                        if (webItem) {
                            contactList.insertBefore(newEmail, webItem);
                        } else {
                            contactList.appendChild(newEmail);
                        }
                    }
                }

                // Update/Insert Phone Item
                if (phone) {
                    let phoneItem = contactList.querySelector('.phone-item');
                    if (phoneItem) {
                        phoneItem.href = `tel:${phone}`;
                        const phoneVal = phoneItem.querySelector('.phone-value');
                        if (phoneVal) phoneVal.textContent = phone;
                    } else {
                        const newPhone = document.createElement('a');
                        newPhone.href = `tel:${phone}`;
                        newPhone.className = 'text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold flex items-center gap-1 phone-item';
                        newPhone.innerHTML = `
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="phone-value">${window.escapeHtml(phone)}</span>
                        `;
                        if (webItem) {
                            contactList.insertBefore(newPhone, webItem);
                        } else {
                            contactList.appendChild(newPhone);
                        }
                    }
                }
            }

            // Sync datasets on Details and Edit buttons
            const detailsBtn = row.querySelector('button[onclick="openViewModal(this)"]');
            if (detailsBtn) {
                if (email) detailsBtn.setAttribute('data-email', email);
                if (phone) detailsBtn.setAttribute('data-phone', phone);
            }

            const editBtn = row.querySelector('button[onclick="openEditModal(this)"]');
            if (editBtn) {
                if (email) editBtn.setAttribute('data-email', email);
                if (phone) editBtn.setAttribute('data-phone', phone);
            }

            showToast(result.message, 'success');

            const updatedEmail = email || (detailsBtn ? detailsBtn.getAttribute('data-email') : null);
            const updatedPhone = phone || (detailsBtn ? detailsBtn.getAttribute('data-phone') : null);
            
            if (updatedEmail && updatedPhone) {
                btn.remove();
            } else {
                btn.innerHTML = originalContent;
                btn.disabled = false;
                btn.classList.remove('cursor-wait');
            }
        } else {
            showToast(result.message || 'Gagal mengambil detail dari website.', 'error');
            btn.innerHTML = originalContent;
            btn.disabled = false;
            btn.classList.remove('cursor-wait');
        }
    } catch (e) {
        showToast('Terjadi kesalahan saat crawling: ' + e.message, 'error');
        btn.innerHTML = originalContent;
        btn.disabled = false;
        btn.classList.remove('cursor-wait');
    }
};
</script>
@endsection
