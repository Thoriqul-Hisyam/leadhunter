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
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Hunt New Leads</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Scrape businesses from Google Maps</p>
        </div>
    </div>

    <form action="{{ route('leads.scrape') }}" method="POST" class="flex flex-col md:flex-row gap-4 items-end">
        @csrf
        <div class="flex-1 w-full">
            <label for="niche" class="form-label">Niche / Keyword</label>
            <input type="text" name="niche" id="niche" placeholder="e.g. klinik gigi, cafe, agency" required class="form-input">
        </div>
        <div class="flex-1 w-full">
            <label for="location" class="form-label">Location</label>
            <input type="text" name="location" id="location" placeholder="e.g. Surabaya, Jakarta, Bali" required class="form-input">
        </div>
        <div class="w-full md:w-auto">
            <button type="submit" class="btn-pink w-full justify-center">
                <span>Scrape Leads</span>
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
                    <div class="absolute text-xs">📡</div>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white">Scraping in progress...</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Fetching real-time leads from Google Maps in the background. You will receive a notification at the top-right bell icon when complete.</p>
                </div>
            </div>
            <span class="text-[11px] font-bold px-3 py-1 rounded-full bg-indigo-500/15 text-indigo-600 dark:text-indigo-400">
                {{ $pendingScrapes }} active job(s)
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
                <h3 class="text-base font-bold text-slate-900 dark:text-white">All Leads</h3>
                <button type="button" 
                        onclick="openCreateModal()" 
                        class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] transition shadow-xs flex items-center gap-1 cursor-pointer border-0 outline-none">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Lead</span>
                </button>
            </div>
        </div>
        
        <form action="{{ route('leads.index') }}" method="GET" class="w-full lg:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <label class="flex items-center justify-center gap-2 text-xs text-slate-700 dark:text-slate-300 font-semibold cursor-pointer whitespace-nowrap bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700/50 transition">
                <input type="checkbox" name="no_website" value="1" class="form-checkbox rounded border-slate-300 dark:border-slate-600 text-indigo-600 bg-slate-100 dark:bg-slate-700" {{ request('no_website') ? 'checked' : '' }} onchange="this.form.submit()">
                <span>🚫 No Website</span>
            </label>
            <div class="flex gap-2 flex-1">
                <input type="text" name="search" placeholder="Search leads..." value="{{ request('search') }}" class="form-input text-xs" style="width: auto;">
                <button type="submit" class="btn-secondary py-1.5 px-4 text-xs">Search</button>
            </div>
        </form>
    </div>

    {{-- Bulk AI Actions Toolbar --}}
    <div id="bulk-actions-toolbar" class="hidden mb-6 p-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white border border-indigo-500/25 shadow-lg shadow-indigo-500/10 transition-all duration-300 transform scale-95 opacity-0 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white shadow-inner">
                <svg class="w-5 h-5 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-sm text-white"><span id="selected-count">0</span> Leads Selected</h4>
                <p class="text-[10px] text-indigo-100 mt-0.5">Select campaign and channel to generate bulk outreach</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
            <x-searchable-select name="campaign_id" 
                form="bulk-outreach-form" 
                required="true" 
                placeholder="🚀 Choose Campaign..." 
                :options="$campaigns->mapWithKeys(fn($c) => [$c->id => $c->name . ' (' . $c->niche . ')'])"
                triggerClass="!bg-white/10 !text-white !border-white/20 focus:!bg-indigo-900/50 focus:!text-white !rounded-xl text-xs font-semibold h-[38px] min-w-[170px] [&_span]:truncate [&_svg]:!text-white/80" />

            <x-searchable-select name="type" 
                form="bulk-outreach-form" 
                required="true" 
                selected="email"
                :options="['email' => '📧 Email', 'whatsapp' => '💬 WhatsApp']"
                triggerClass="!bg-white/10 !text-white !border-white/20 focus:!bg-indigo-900/50 focus:!text-white !rounded-xl text-xs font-semibold h-[38px] min-w-[120px] [&_span]:truncate [&_svg]:!text-white/80" />
            <button type="submit" form="bulk-outreach-form" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-white text-indigo-700 font-bold text-xs hover:bg-slate-50 transition shadow-md flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.683 5.682a8.205 8.205 0 11-11.64 0c.37-.362.84-.62 1.348-.753l1.16-.3a.75.75 0 01.916.518l.3 1.16a2.205 2.205 0 003.536 0l.3-1.16a.75.75 0 01.916-.518l1.16.3c.508.133.978.391 1.348.753z" />
                </svg>
                <span>Generate</span>
            </button>
        </div>
    </div>

    <form action="{{ route('outreach.generate') }}" method="POST" id="bulk-outreach-form">
        @csrf
    </form>
    <div class="overflow-x-auto">
            <table class="fancy-table">
                <thead>
                    <tr>
                        <th style="width: 45px; padding-left: 16px;">
                            <input type="checkbox" id="select-all-leads" class="form-checkbox h-4.5 w-4.5 rounded text-indigo-600 border-slate-300 cursor-pointer">
                        </th>
                        <th>Business Name</th>
                        <th>Niche / City</th>
                        <th>Contact</th>
                        <th>Source</th>
                        <th style="width: 120px; text-align: right; padding-right: 20px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    <tr class="lead-row" id="lead-row-{{ $lead->id }}">
                        <td style="padding-left: 16px;">
                            <input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" form="bulk-outreach-form" class="lead-checkbox form-checkbox h-4.5 w-4.5 rounded text-indigo-600 border-slate-300 cursor-pointer">
                        </td>
                        <td>
                            <div class="font-bold text-slate-900 dark:text-slate-100">{{ $lead->business_name }}</div>
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
                                    <a href="{{ $lead->website }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold flex items-center gap-1 website-item">
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        <span>Website</span>
                                    </a>
                                @endif
                                @if(!$lead->email && !$lead->phone && !$lead->website)
                                    <span class="text-slate-400 dark:text-slate-600 text-xs no-contact-label">No contact info</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">{{ $lead->source }}</span>
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
                                        data-created-at="{{ $lead->created_at->format('M d, Y \a\t H:i') }}"
                                        class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-800 dark:hover:text-white transition shadow-2xs cursor-pointer border-0 outline-none flex items-center justify-center shrink-0"
                                        title="View Details">
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
                                            title="Extract missing info from website">
                                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    </button>
                                @endif
                                
                                {{-- Delete Button --}}
                                <form action="{{ route('leads.destroy', $lead) }}" method="POST" onsubmit="return handleConfirm(event, this, 'Delete Lead?', 'Are you sure you want to delete this lead? All associated outreach messages will also be permanently deleted.', 'Yes, Delete')" class="inline shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-600 hover:text-white transition shadow-2xs cursor-pointer border-0 outline-none flex items-center justify-center"
                                            title="Delete Lead">
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
                        <td colspan="6">
                            <div class="empty-state py-12">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <p class="font-semibold text-slate-500 dark:text-slate-400">No leads yet</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Use the form above to start scraping!</p>
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
                <span>Lead Profile Details</span>
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
                    <h4 class="text-base font-extrabold text-slate-900 dark:text-white truncate" id="view-business-name">Business Name</h4>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span class="badge bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 text-[10px]" id="view-niche">Niche</span>
                        <span class="badge bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/15 text-[10px]" id="view-city">City</span>
                        <span class="badge bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/15 text-[10px] uppercase" id="view-source">Source</span>
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
                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Email Address</span>
                        <a href="" id="view-email-link" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block">Not Available</a>
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
                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Phone Number</span>
                        <a href="" id="view-phone-link" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block">Not Available</a>
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
                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Website URL</span>
                        <a href="" target="_blank" id="view-website-link" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block">Not Available</a>
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
                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Discovery Date</span>
                        <span id="view-created-at" class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate block">Date</span>
                    </div>
                </div>
            </div>

            {{-- Address Field --}}
            <div class="mb-6 p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block mb-1">Full Address</span>
                <p id="view-address" class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-semibold">Full Address Here...</p>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeViewModal()" class="btn-secondary px-6 py-2.5 text-xs font-bold">Close</button>
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
                <span>Add New Lead</span>
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
                        <label for="create-business-name" class="form-label text-[10px]">Business Name</label>
                        <input type="text" name="business_name" id="create-business-name" placeholder="e.g. Acme Corporation" required class="form-input text-xs">
                    </div>
                    <div>
                        <label for="create-niche" class="form-label text-[10px]">Niche</label>
                        <input type="text" name="niche" id="create-niche" placeholder="e.g. Digital Agency, Klinik Gigi" required class="form-input text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="create-city" class="form-label text-[10px]">City</label>
                        <input type="text" name="city" id="create-city" placeholder="e.g. Surabaya" required class="form-input text-xs">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">Source Channel</label>
                        <x-searchable-select name="source" 
                            id="create-source" 
                            required="true" 
                            selected="custom"
                            :options="['custom' => 'Custom Import', 'google-maps' => 'Google Maps', 'website' => 'Website']"
                            triggerClass="text-xs !py-2 !rounded-lg" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="sm:col-span-1">
                        <label for="create-phone" class="form-label text-[10px]">Phone Number</label>
                        <input type="text" name="phone" id="create-phone" placeholder="e.g. +62812345678" class="form-input text-xs">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="create-email" class="form-label text-[10px]">Email Address</label>
                        <input type="email" name="email" id="create-email" placeholder="e.g. hello@acme.com" class="form-input text-xs">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="create-website" class="form-label text-[10px]">Website URL</label>
                        <input type="text" name="website" id="create-website" placeholder="e.g. https://acme.com" class="form-input text-xs">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="create-address" class="form-label text-[10px]">Full Address</label>
                    <textarea name="address" id="create-address" rows="3" placeholder="e.g. Jl. Basuki Rahmat No. 12" class="form-input resize-none text-xs"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="closeCreateModal()" class="btn-secondary px-5 py-2.5 text-xs font-bold">Cancel</button>
                    <button type="submit" class="btn-primary shadow-lg shadow-indigo-500/20 px-6 py-2.5 text-xs">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>Add Lead</span>
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
                <span>Edit Lead Profile</span>
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
                        <label for="edit-business-name" class="form-label text-[10px]">Business Name</label>
                        <input type="text" name="business_name" id="edit-business-name" required class="form-input text-xs">
                    </div>
                    <div>
                        <label for="edit-niche" class="form-label text-[10px]">Niche</label>
                        <input type="text" name="niche" id="edit-niche" required class="form-input text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="edit-city" class="form-label text-[10px]">City</label>
                        <input type="text" name="city" id="edit-city" required class="form-input text-xs">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">Source Channel</label>
                        <x-searchable-select name="source" 
                            id="edit-source" 
                            required="true" 
                            :options="['google-maps' => 'Google Maps', 'website' => 'Website', 'custom' => 'Custom Import']"
                            triggerClass="text-xs !py-2 !rounded-lg" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="sm:col-span-1">
                        <label for="edit-phone" class="form-label text-[10px]">Phone Number</label>
                        <input type="text" name="phone" id="edit-phone" class="form-input text-xs">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="edit-email" class="form-label text-[10px]">Email Address</label>
                        <input type="email" name="email" id="edit-email" class="form-input text-xs">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="edit-website" class="form-label text-[10px]">Website URL</label>
                        <input type="text" name="website" id="edit-website" class="form-input text-xs">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="edit-address" class="form-label text-[10px]">Full Address</label>
                    <textarea name="address" id="edit-address" rows="3" class="form-input resize-none text-xs"></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="closeEditModal()" class="btn-secondary px-5 py-2.5 text-xs font-bold">Cancel</button>
                    <button type="submit" class="btn-primary shadow-lg shadow-indigo-500/20 px-6 py-2.5 text-xs">
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
        if (checkedCount > 0) {
            bulkToolbar.classList.remove('hidden');
            setTimeout(() => {
                bulkToolbar.style.opacity = '1';
                bulkToolbar.style.transform = 'scale(1)';
            }, 10);
            selectedCountSpan.textContent = checkedCount;
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

    document.getElementById('view-business-name').textContent = name || 'N/A';
    document.getElementById('view-niche').textContent = niche || 'N/A';
    document.getElementById('view-city').textContent = city || 'N/A';
    document.getElementById('view-source').textContent = source || 'N/A';
    document.getElementById('view-created-at').textContent = createdAt || 'N/A';
    document.getElementById('view-address').textContent = address || 'No address registered';

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
            link.textContent = 'Not Available';
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
        webLink.textContent = 'Not Available';
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
        <span class="text-xs font-semibold leading-relaxed">${message}</span>
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
        const response = await fetch(`/leads/${id}/crawl-website`, {
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
                            <span class="email-value">${email}</span>
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
                            <span class="phone-value">${phone}</span>
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
            showToast(result.message || 'Failed to crawl website details.', 'error');
            btn.innerHTML = originalContent;
            btn.disabled = false;
            btn.classList.remove('cursor-wait');
        }
    } catch (e) {
        showToast('An error occurred during crawling: ' + e.message, 'error');
        btn.innerHTML = originalContent;
        btn.disabled = false;
        btn.classList.remove('cursor-wait');
    }
};
</script>
@endsection
