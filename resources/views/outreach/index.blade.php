@extends('layouts.app')

@section('title', 'Outreach - Sandesa')
@section('header', 'Outreach Management')

@section('content')
{{-- ===== STUNNING OUTREACH COMPOSER ===== --}}
<div class="glass-card p-6 mb-8 border-t-2 border-t-indigo-500/50" style="animation: fadeInUp 0.4s ease backwards;">
    <div class="flex flex-col lg:flex-row gap-6">
       
        
        <div class="w-full">
            <form id="outreach-composer-form" class="bg-slate-50/50 dark:bg-slate-800/20 border border-slate-200/50 dark:border-slate-800/60 rounded-2xl p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="composer_campaign_id" class="form-label text-xs">Target Campaign</label>
                        <x-searchable-select id="composer-campaign-select" inputId="composer_campaign_id" name="campaign_id" :required="true" placeholder="Choose a campaign..." :options="$campaigns->mapWithKeys(fn($c) => [$c->id => $c->name . ' (' . $c->niche . ')'])->toArray()" triggerClass="form-select text-xs w-full" />
                    </div>

                    <div>
                        <label for="composer_type" class="form-label text-xs">Outreach Channel</label>
                        <x-searchable-select id="composer-type-select" inputId="composer_type" name="type" :required="true" :selected="'email'" :options="['email' => 'Email', 'whatsapp' => 'WhatsApp']" triggerClass="form-select text-xs w-full" />
                    </div>

                    <div>
                        <label for="composer_mode" class="form-label text-xs">Composition Mode</label>
                        <x-searchable-select id="composer-mode-select" inputId="composer_mode" name="mode" :required="true" :selected="'hybrid'" :options="['hybrid' => 'Template + AI Polish', 'template' => 'Master Template', 'ai' => 'AI Generate']" triggerClass="form-select text-xs w-full" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div id="composer_template_wrapper" class="md:col-span-1">
                        <label for="composer_template_id" class="form-label text-xs">Select Template</label>
                        <x-searchable-select id="composer-template-select" inputId="composer_template_id" name="template_id" placeholder="Choose template..." :options="[]" triggerClass="form-select text-xs w-full" />
                    </div>

                    <div id="composer_offer_wrapper" class="md:col-span-1">
                        <label for="composer_offer" class="form-label text-xs">Layanan / Penawaran</label>
                        <input type="text" name="offer" id="composer_offer" value="{{ $defaultOffer }}" placeholder="e.g. Pembuatan Website" class="form-input text-xs">
                    </div>

                    <div id="composer_sender_wrapper" class="md:col-span-1">
                        <label for="composer_sender" class="form-label text-xs">Identitas Pengirim</label>
                        <input type="text" name="sender_name" id="composer_sender" value="{{ $senderName }}" placeholder="e.g. Thoriq dari Lefateach" class="form-input text-xs">
                    </div>
                </div>

                {{-- Leads Selection --}}
                <div class="border-t border-slate-200/50 dark:border-slate-800/60 pt-4">
                    @php
                        $leadNiches = $leads->pluck('niche')->filter()->map(fn($n) => strtolower(trim($n)))->unique()->sort()->values();
                        $leadCities = $leads->pluck('city')->filter()->map(fn($c) => strtolower(trim($c)))->unique()->sort()->values();
                    @endphp
                    <div class="flex justify-between items-center mb-3">
                        <label class="form-label text-xs !mb-0 font-bold text-slate-700 dark:text-slate-350">Select Targets</label>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 text-2xs text-slate-600 dark:text-slate-400 cursor-pointer bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                <input type="checkbox" id="select-all-leads" class="form-checkbox h-3.5 w-3.5 rounded text-indigo-600 border-slate-300">
                                <span>Select All</span>
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
                            <input type="text" id="lead-search-input" placeholder="Search by business, city, niche..." class="w-full pl-8 pr-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-1.5">
                            <x-searchable-select
                                id="lead-niche-filter-select"
                                inputId="lead-niche-filter"
                                name="lead_niche_filter"
                                :selected="''"
                                placeholder="All Niches"
                                :options="$leadNiches->mapWithKeys(fn($n) => [$n => ucfirst($n)])->toArray()"
                                triggerClass="w-full px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300" />
                            <x-searchable-select
                                id="lead-city-filter-select"
                                inputId="lead-city-filter"
                                name="lead_city_filter"
                                :selected="''"
                                placeholder="All Locations"
                                :options="$leadCities->mapWithKeys(fn($c) => [$c => ucfirst($c)])->toArray()"
                                triggerClass="w-full px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300" />
                        </div>
                        <div class="grid grid-cols-3 gap-1.5">
                            <button type="button" id="filter-has-email" data-active="0" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300 hover:text-indigo-600">Has Email</button>
                            <button type="button" id="filter-has-phone" data-active="0" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300 hover:text-indigo-600">Has Phone</button>
                            <button type="button" id="filter-has-website" data-active="0" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 transition hover:border-indigo-300 hover:text-indigo-600">Has Website</button>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-inner">
                        <div class="max-h-[320px] overflow-y-auto p-2 space-y-1.5" id="leads-checkbox-list">
                            @forelse($leads as $lead)
                                <label class="lead-item flex items-center justify-between p-2.5 rounded-lg border border-slate-100 dark:border-slate-800/50 hover:border-indigo-500/20 hover:bg-indigo-500/5 cursor-pointer transition" data-id="{{ $lead->id }}" data-email="{{ !empty($lead->email) ? 'yes' : 'no' }}" data-phone="{{ !empty($lead->phone) ? 'yes' : 'no' }}" data-website="{{ !empty($lead->website) ? 'yes' : 'no' }}" data-niche="{{ strtolower($lead->niche) }}" data-city="{{ strtolower($lead->city) }}">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" class="lead-checkbox form-checkbox h-4.5 w-4.5 rounded text-indigo-600 border-slate-300">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $lead->business_name }}</div>
                                            <div class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2">
                                                <span>{{ $lead->city }}</span>
                                                <span class="px-1 bg-slate-100 dark:bg-slate-850 rounded border dark:border-slate-750 font-bold capitalize text-indigo-500">{{ $lead->niche }}</span>
                                                @if($lead->email) <span class="text-sky-600 bg-sky-500/10 px-1.5 py-0.5 rounded font-semibold">Email</span> @endif
                                                @if($lead->phone) <span class="text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded font-semibold">Phone</span> @endif
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <div class="text-center py-8 text-xs font-bold text-slate-400 dark:text-slate-500">
                                    No leads available in database.<br>Go to Leads page and scrape some!
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" onclick="prepareComposerPreview()" class="btn-success shadow-lg shadow-emerald-500/20 px-6 py-2.5 text-xs font-bold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Generate & Preview</span>
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
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Preview & Personalize Outreach Pipeline</h3>
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
                <button type="button" onclick="closeComposerPreviewModal()" class="btn-secondary px-5 py-2 text-xs">Cancel</button>
                <button type="button" id="btn-save-composer-pipeline" onclick="saveComposerPipeline()" class="btn-success shadow-lg shadow-emerald-500/20 px-6 py-2 text-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Save & Add to Pipeline</span>
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
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Outreach Pipeline</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Review, edit, and send your messages</p>
            </div>
        </div>
        
        <form action="{{ route('outreach.bulk') }}" method="POST" id="bulkOutreachForm" class="flex gap-2 items-center w-full sm:w-auto" onsubmit="return handleConfirm(event, this, 'Apply Bulk Action?', 'Are you sure you want to apply this action to all selected messages?', 'Yes, Apply')">
            @csrf
            <x-searchable-select name="action" 
                required="true" 
                placeholder="Aksi massal" 
                :options="[
                    'send_queue' => 'Kirim Email via Antrean',
                    'delete' => 'Delete Selected',
                    'status_pending' => 'Mark as Pending (batalkan antrean)',
                    'status_sent' => 'Mark as Sent',
                    'status_replied' => 'Mark as Replied',
                    'status_failed' => 'Mark as Failed'
                ]"
                triggerClass="form-select text-xs w-full sm:w-48 !py-1.5 !rounded-lg" />
            <button type="submit" class="btn-secondary py-1.5 px-4 text-xs font-bold">Apply</button>
            <a href="{{ route('outreach.export', request()->only(['status', 'campaign_id'])) }}" class="btn-secondary py-1.5 px-3 text-xs font-bold whitespace-nowrap" title="Export hasil outreach ke CSV"><x-icon name="download" class="w-3.5 h-3.5" /> CSV</a>
        </form>
    </div>

    {{-- Filter status --}}
    <div class="flex flex-wrap items-center gap-2 mb-5">
        @php $currentStatus = request('status'); @endphp
        <a href="{{ route('outreach.index', request()->except(['status', 'page'])) }}" class="px-3 py-1 rounded-full text-[11px] font-bold border transition {{ !$currentStatus ? 'bg-slate-900 text-white border-slate-900 dark:bg-indigo-600 dark:border-indigo-600' : 'border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-slate-400' }}">Semua</a>
        @foreach(['pending' => 'Pending', 'queued' => 'Antrean', 'sent' => 'Sent', 'replied' => 'Replied', 'failed' => 'Failed'] as $value => $label)
            <a href="{{ route('outreach.index', array_merge(request()->except('page'), ['status' => $value])) }}" class="px-3 py-1 rounded-full text-[11px] font-bold border transition {{ $currentStatus === $value ? 'bg-slate-900 text-white border-slate-900 dark:bg-indigo-600 dark:border-indigo-600' : 'border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-slate-400' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($fakeMailer)
        <div class="alert-error mb-5">
            <span><strong>MAIL_MAILER={{ config('mail.default') }}:</strong> email hanya ditulis ke <code>storage/logs/laravel.log</code>, tidak benar-benar terkirim. Isi konfigurasi SMTP Gmail di <code>.env</code> untuk mengirim sungguhan.</span>
        </div>
    @endif

    @if($queuedCount > 0)
        <div class="mb-5 p-3 rounded-xl border border-violet-500/20 bg-violet-500/5 text-xs text-violet-700 dark:text-violet-300 font-semibold">
            <x-icon name="clock" class="w-4 h-4 inline-block align-[-3px] mr-1" />{{ $queuedCount }} email di antrean kirim (maks. {{ config('leadhunter.sending.hourly_limit') }}/jam). Antrean diproses oleh scheduler; pastikan <code>composer run dev</code> (atau <code>php artisan schedule:work</code> + queue worker) berjalan.
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
                    <th style="width: 45%">Message Preview</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
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
                                            class="p-1.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-indigo-500/20 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition" title="Edit Message">
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
                                {{ ucfirst($msg->status) }}
                            </span>
                            @if($msg->sent_at)
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ $msg->sent_at->format('M d, H:i') }}</span>
                            @endif
                            @if($msg->status === 'queued')
                                <span class="text-[10px] text-violet-600 dark:text-violet-400 font-medium">{{ $msg->scheduled_at ? 'Jadwal: '.$msg->scheduled_at->format('d M H:i') : 'Sedang diproses...' }}</span>
                            @endif
                            @if($msg->followup_of_id)
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-500/10 text-sky-600 dark:text-sky-400 inline-flex items-center gap-1"><x-icon name="reply" class="w-3 h-3" /> Follow-up</span>
                            @endif
                            @if($msg->status === 'failed' && $msg->last_error)
                                <span class="text-[10px] text-rose-600 dark:text-rose-400 max-w-44 line-clamp-2" title="{{ $msg->last_error }}">{{ $msg->last_error }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="align-top py-4 text-right">
                        <div class="flex flex-col items-end gap-2.5">
                            @if($msg->status == 'pending')
                                <form action="{{ route('outreach.send', $msg->id) }}" method="POST" class="inline" @if(($msg->type ?? 'email') === 'whatsapp') target="_blank" @endif>
                                    @csrf
                                    @if(($msg->type ?? 'email') === 'whatsapp')
                                        <button type="submit" class="btn-success shadow-md shadow-emerald-500/10 text-xs py-1.5 px-4 rounded-lg">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <span>Send WA</span>
                                        </button>
                                    @else
                                        <button type="submit" class="btn-primary shadow-md shadow-indigo-500/10 text-xs py-1.5 px-4 rounded-lg">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9-2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                            <span>Send Email</span>
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
                                        <button type="submit" class="btn-success py-1 px-3 text-[11px] font-bold rounded-lg" title="Mark as Replied">
                                            <span>Replied</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('outreach.status', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="failed">
                                        <button type="submit" class="btn-secondary py-1 px-3 text-[11px] font-bold text-red-500 dark:text-red-400 border-red-500/20 hover:border-red-500/40 hover:bg-red-500/5 rounded-lg" title="Mark as Failed">
                                            <span>Failed</span>
                                        </button>
                                    </form>
                                </div>
                            @elseif($msg->status == 'replied')
                                <div class="flex flex-col items-end gap-1.5">
                                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/15 px-2 py-1 rounded"><x-icon name="trophy" class="w-3.5 h-3.5" /> Deal Closed</span>
                                    <form action="{{ route('outreach.status', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="text-slate-500 hover:text-slate-800 dark:hover:text-white text-[10px] font-semibold underline bg-transparent border-none cursor-pointer transition">
                                            Reset Status
                                        </button>
                                    </form>
                                </div>
                            @elseif($msg->status == 'failed')
                                <div class="flex flex-col items-end gap-2">
                                    <form action="{{ route('outreach.send', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-pink shadow-md shadow-pink-500/15 py-1 px-3 text-[11px] font-bold rounded-lg">
                                            <span>Retry</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('outreach.status', $msg->id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="text-slate-500 hover:text-slate-800 dark:hover:text-white text-[10px] font-semibold underline bg-transparent border-none cursor-pointer transition">
                                            Reset Status
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <form action="{{ route('outreach.destroy', $msg->id) }}" method="POST" class="inline mt-2 border-t border-slate-100 dark:border-slate-800 pt-2 w-full text-right" onsubmit="return handleConfirm(event, this, 'Delete Message?', 'Are you sure you want to delete this outreach message? This cannot be undone.', 'Yes, Delete')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-red-500 text-[10px] transition font-semibold" title="Delete Message">
                                    Delete
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
                            <p class="font-bold text-slate-500 dark:text-slate-400">Your Pipeline is Empty</p>
                            <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 max-w-sm mx-auto">Select a campaign and target leads in the generator above to let AI craft your perfect pitch.</p>
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
                <span>Edit AI Message</span>
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        {{-- Body --}}
        <div class="p-6">
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-3.5 mb-5 flex items-start gap-3">
                <span class="text-amber-500"><x-icon name="light-bulb" class="w-4 h-4" /></span>
                <p class="text-xs text-amber-700 dark:text-amber-200/80 leading-relaxed">You are editing the raw message. Be careful with formatting. Changes will be saved permanently for this specific outreach attempt.</p>
            </div>

            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-5">
                    <label class="form-label text-xs">Subject Line <span class="text-[10px] text-slate-500 ml-2 font-normal">(Leave empty for WhatsApp)</span></label>
                    <input type="text" name="subject" id="editSubject" class="form-input">
                </div>
                
                <div class="mb-5 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-xl">
                    <label class="form-label text-xs flex justify-between items-center mb-2">
                        <span class="text-indigo-700 dark:text-indigo-300 font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Regenerate with AI
                        </span>
                    </label>
                    <div class="flex flex-col gap-2">
                        <textarea id="customPrompt" rows="2" class="form-input text-xs w-full" placeholder="Optional: Enter a custom prompt (e.g. 'Make it more funny', 'Mention our discount promo')"></textarea>
                        <button type="button" id="btnRegenerate" onclick="regenerateMessage()" class="btn-primary py-2 text-xs self-end inline-flex items-center gap-1.5">
                            <x-icon name="sparkles" class="w-3.5 h-3.5" /> Regenerate Now
                        </button>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="form-label text-xs">Message Body</label>
                    <textarea name="message" id="editMessage" rows="10" required class="form-input font-mono text-xs leading-relaxed resize-y"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="closeEditModal()" class="btn-secondary px-5 py-2.5">Cancel</button>
                    <button type="submit" class="btn-primary shadow-lg shadow-indigo-500/20 px-6 py-2.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>Save Changes</span>
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
        
        btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating...';
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
                window.showToast('Message regenerated successfully! Review and click Save.', 'success');
            } else {
                window.showToast(data.message || 'Error regenerating message.', 'error');
            }
        } catch (e) {
            console.error(e);
            window.showToast('Network error while regenerating.', 'error');
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

    function getLeadCheckboxes() {
        return Array.from(document.querySelectorAll('#leads-checkbox-list .lead-checkbox'));
    }

    function getVisibleLeadItems() {
        return Array.from(document.querySelectorAll('#leads-checkbox-list .lead-item')).filter(item => !item.classList.contains('hidden'));
    }

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
        btn.classList.toggle('dark:text-indigo-400', active);
        btn.classList.toggle('shadow-sm', active);
        btn.classList.toggle('bg-white', !active);
        btn.classList.toggle('dark:bg-slate-900', !active);
        btn.classList.toggle('border-slate-200', !active);
        btn.classList.toggle('dark:border-slate-700', !active);
        btn.classList.toggle('text-slate-500', !active);
        btn.classList.toggle('dark:text-slate-400', !active);
        if (!active) {
            btn.classList.remove('dark:text-indigo-400', 'shadow-sm');
        }
    }

    function applyLeadFilters() {
        const query = (leadSearchInput?.value || '').trim().toLowerCase();
        const selectedNiche = (leadNicheFilterInput?.value || '').trim().toLowerCase();
        const selectedCity = (leadCityFilterInput?.value || '').trim().toLowerCase();
        const onlyEmail = filterHasEmailBtn?.dataset.active === '1';
        const onlyPhone = filterHasPhoneBtn?.dataset.active === '1';
        const onlyWebsite = filterHasWebsiteBtn?.dataset.active === '1';

        document.querySelectorAll('#leads-checkbox-list .lead-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            const hasEmail = item.dataset.email === 'yes';
            const hasPhone = item.dataset.phone === 'yes';
            const hasWebsite = item.dataset.website === 'yes';
            const itemNiche = (item.dataset.niche || '').toLowerCase();
            const itemCity = (item.dataset.city || '').toLowerCase();

            const matchSearch = !query || text.includes(query);
            const matchNiche = !selectedNiche || itemNiche === selectedNiche;
            const matchCity = !selectedCity || itemCity === selectedCity;
            const matchEmail = !onlyEmail || hasEmail;
            const matchPhone = !onlyPhone || hasPhone;
            const matchWebsite = !onlyWebsite || hasWebsite;

            item.classList.toggle('hidden', !(matchSearch && matchNiche && matchCity && matchEmail && matchPhone && matchWebsite));
        });
    }
    
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
        const campaignNiche = getSelectedCampaignNiche();
        
        // Dynamic visual filtering of leads list by campaign niche (optional detail!)
        if (campaignNiche) {
            document.querySelectorAll('#leads-checkbox-list .lead-item').forEach(item => {
                const niche = item.getAttribute('data-niche');
                if (niche.includes(campaignNiche) || campaignNiche.includes(niche)) {
                    item.classList.add('border-indigo-500/10');
                } else {
                    item.classList.remove('border-indigo-500/10');
                }
            });
        }
        
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
            <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" data-value="">Choose template...</div>
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
        if (cb.checked) {
            row.classList.add('bg-indigo-500/10', 'border-indigo-500/25', 'dark:bg-indigo-500/15');
            row.classList.remove('border-slate-100', 'dark:border-slate-800/50');
        } else {
            row.classList.remove('bg-indigo-500/10', 'border-indigo-500/25', 'dark:bg-indigo-500/15');
            row.classList.add('border-slate-100', 'dark:border-slate-800/50');
        }
    }

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            getVisibleLeadItems().forEach(item => {
                const cb = item.querySelector('.lead-checkbox');
                if (!cb) return;
                cb.checked = selectAllCb.checked;
                toggleLeadHighlight(cb);
            });
        });
    }

    getLeadCheckboxes().forEach(cb => {
        cb.addEventListener('change', () => toggleLeadHighlight(cb));
    });

    if (btnSmartSelect) {
        btnSmartSelect.addEventListener('click', function() {
            const channel = document.getElementById('composer_type').value;
            let matchedCount = 0;
            
            getLeadCheckboxes().forEach(cb => {
                const item = cb.closest('.lead-item');
                if (!item || item.classList.contains('hidden')) return;
                const hasEmail = item.dataset.email === 'yes';
                const hasPhone = item.dataset.phone === 'yes';
                
                if (channel === 'email' && hasEmail) {
                    cb.checked = true;
                    matchedCount++;
                } else if (channel === 'whatsapp' && hasPhone) {
                    cb.checked = true;
                    matchedCount++;
                } else {
                    cb.checked = false;
                }
                toggleLeadHighlight(cb);
            });
            
            if (matchedCount > 0) {
                window.showToast(`Auto-selected ${matchedCount} leads with active ${channel === 'whatsapp' ? 'Phones' : 'Emails'}.`, 'success');
            } else {
                window.showToast(`No leads found with active contacts.`, 'error');
            }
        });
    }

    if (leadSearchInput) {
        leadSearchInput.addEventListener('input', debounce(applyLeadFilters, 250));
    }

    if (leadNicheFilterInput) {
        leadNicheFilterInput.addEventListener('change', applyLeadFilters);
    }

    if (leadCityFilterInput) {
        leadCityFilterInput.addEventListener('change', applyLeadFilters);
    }

    if (filterHasEmailBtn) {
        filterHasEmailBtn.addEventListener('click', () => {
            const active = filterHasEmailBtn.dataset.active !== '1';
            setFilterButtonState(filterHasEmailBtn, active);
            applyLeadFilters();
        });
    }

    if (filterHasPhoneBtn) {
        filterHasPhoneBtn.addEventListener('click', () => {
            const active = filterHasPhoneBtn.dataset.active !== '1';
            setFilterButtonState(filterHasPhoneBtn, active);
            applyLeadFilters();
        });
    }

    if (filterHasWebsiteBtn) {
        filterHasWebsiteBtn.addEventListener('click', () => {
            const active = filterHasWebsiteBtn.dataset.active !== '1';
            setFilterButtonState(filterHasWebsiteBtn, active);
            applyLeadFilters();
        });
    }

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
        const checkedLeads = Array.from(document.querySelectorAll('#leads-checkbox-list .lead-checkbox:checked'));
        if (checkedLeads.length === 0) {
            window.showToast('Please select at least 1 target lead!', 'error');
            return;
        }

        if (!campaignId) {
            window.showToast('Please select a target campaign!', 'error');
            return;
        }

        if (mode !== 'ai' && !templateId) {
            window.showToast('Please select an outreach template!', 'error');
            return;
        }

        // Set mode label in footer
        let modeLabel = 'Hybrid';
        if (mode === 'template') modeLabel = 'Master Template';
        if (mode === 'ai') modeLabel = 'AI Generate';
        document.getElementById('composer-footer-mode-label').textContent = modeLabel;

        // Open modal and show skeleton loaders
        openComposerPreviewModal();
        previewBody.innerHTML = checkedLeads.map(cb => {
            const row = cb.closest('.lead-item');
            const name = row.querySelector('.text-xs').textContent;
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

        const leadIds = checkedLeads.map(cb => parseInt(cb.value));

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
                window.showToast(data.message || 'Error generating previews.', 'error');
            }
        } catch (e) {
            closeComposerPreviewModal();
            console.error(e);
            window.showToast('Network error while generating previews.', 'error');
        }
    }

    function renderComposerPreviews(previews, type) {
        previewBody.innerHTML = previews.map(item => {
            return `
                <div class="composer-lead-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 hover:border-indigo-500/25 transition-all space-y-4" data-lead-id="${item.lead_id}">
                    <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-800/80 pb-3">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">${escapeHtml(item.business_name)}</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5"><svg class="w-3 h-3 align-[-2px] inline-block shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg> ${escapeHtml(item.city)} &bull; <span class="capitalize">${escapeHtml(item.niche)}</span> &bull; ${escapeHtml(item.email || item.phone || 'No Contact listed')}</p>
                        </div>
                        <div class="flex gap-1.5 items-center">
                            ${item.is_fallback ? `<span class="px-2 py-0.5 text-[9px] font-bold bg-amber-500/10 text-amber-600 border border-amber-500/15 rounded inline-flex items-center gap-1" title="${escapeHtml(item.fallback_reason)}"><svg class="w-3 h-3 inline-block shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg> AI Fallback</span>` : ''}
                            <span class="px-2 py-0.5 text-[9px] font-bold rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 uppercase">${type}</span>
                        </div>
                    </div>
                    
                    ${type === 'email' ? `
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Subject Line</label>
                        <input type="text" class="composer-subject-input form-input text-xs" value="${escapeHtml(item.subject || '')}" placeholder="Subject Line">
                    </div>
                    ` : ''}
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Message Body</label>
                        <textarea class="composer-message-textarea form-input text-xs font-sans leading-relaxed resize-y" rows="7">${escapeHtml(item.message)}</textarea>
                    </div>

                    {{-- Dynamic on-the-fly Polish --}}
                    <div class="bg-indigo-50/30 dark:bg-indigo-950/10 border border-indigo-100/50 dark:border-indigo-850/50 rounded-xl p-3 flex flex-col gap-2">
                        <label class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span>Custom AI Polish for this Lead</span>
                        </label>
                        <div class="flex gap-2 items-center">
                            <input type="text" class="composer-polish-prompt form-input text-2xs py-1" placeholder="e.g. 'Add a 15% discount promo', 'Make the CTA much shorter'">
                            <button type="button" onclick="polishCardMessage(${item.lead_id}, this)" class="btn-primary py-1 px-3 text-[10px] shrink-0 font-bold">Polish</button>
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
            window.showToast('Please type a polish instruction first!', 'error');
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
                window.showToast('AI polished successfully!', 'success');
            } else {
                window.showToast(data.message || 'Failed to polish message.', 'error');
            }
        } catch(e) {
            console.error(e);
            window.showToast('Network error while polishing.', 'error');
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
                message: messageEl.value
            });
        });

        if (messages.length === 0) return;

        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = 'Saving...';
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
                window.showToast(data.message || 'Failed to save pipeline.', 'error');
            }
        } catch (e) {
            console.error(e);
            window.showToast('Network error while saving.', 'error');
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
</script>
@endsection
