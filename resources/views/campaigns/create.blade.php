@extends('layouts.app')

@section('title', 'New Campaign - Sandesa')
@section('header', 'Create Campaign')

@section('content')
<div class="max-w-6xl mx-auto">
    <form action="{{ route('campaigns.store') }}" method="POST" id="campaign-create-form" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        
        {{-- Left Side: Campaign Configuration & Options --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card p-6" style="animation: fadeInUp 0.4s ease backwards;">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">New Campaign</h3>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Configure target details</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="form-label text-xs">Campaign Name</label>
                        <input type="text" name="name" id="name" placeholder="e.g. Surabaya Dental Web Sales" required class="form-input text-sm">
                    </div>

                    <div>
                        <label for="niche" class="form-label text-xs">Target Niche</label>
                        <input type="text" name="niche" id="niche" placeholder="e.g. cafe, dentist, salon" required class="form-input text-sm">
                    </div>

                    <div>
                        <label for="location" class="form-label text-xs">Target Location</label>
                        <input type="text" name="location" id="location" placeholder="e.g. Surabaya, Jakarta, Bali" required class="form-input text-sm">
                    </div>
                </div>
            </div>

            {{-- Auto Outreach Generation Box --}}
            <div class="glass-card p-5 !border-indigo-500/10" style="animation: fadeInUp 0.4s ease backwards; animation-delay: 0.05s;">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="auto_generate" id="auto_generate" value="1" checked class="form-checkbox mt-1 h-4.5 w-4.5 rounded cursor-pointer">
                    <div>
                        <label for="auto_generate" class="font-bold text-sm text-slate-900 dark:text-white cursor-pointer flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Auto-Generate AI Outreach</span>
                        </label>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">AI will generate personalized website creation outreach for selected leads.</p>
                    </div>
                </div>

                <div id="channel-selection-group" class="pl-7 space-y-3 mt-4">
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Outreach Channels:</p>
                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer hover:text-slate-900 dark:hover:text-white transition">
                        <input type="checkbox" name="channels[]" value="email" checked class="form-checkbox rounded channel-checkbox">
                        <span>Email Penawaran Website</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer hover:text-slate-900 dark:hover:text-white transition">
                        <input type="checkbox" name="channels[]" value="whatsapp" checked class="form-checkbox rounded channel-checkbox">
                        <span>WhatsApp Click-to-Chat</span>
                    </label>

                    {{-- Generation Mode Selector --}}
                    <div class="pt-3 mt-3 border-t border-slate-200/60 dark:border-slate-700/50">
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2.5">Generation Mode:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="mode-card relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 cursor-pointer transition-all duration-200 border-indigo-500 bg-indigo-500/5 dark:bg-indigo-500/10 shadow-sm shadow-indigo-500/10" data-mode="ai_generate">
                                <input type="radio" name="outreach_mode" value="ai_generate" checked class="sr-only">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 113.536 0V21h-3.536v-5.064z" />
                                </svg>
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-200 text-center leading-tight">AI Generate</span>
                            </label>
                            <label class="mode-card relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 cursor-pointer transition-all duration-200 border-slate-200 dark:border-slate-700 hover:border-emerald-400/50 hover:bg-emerald-500/5" data-mode="template">
                                <input type="radio" name="outreach_mode" value="template" class="sr-only">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-200 text-center leading-tight">Master Template</span>
                            </label>
                        </div>
                    </div>

                    {{-- Searchable Template Combobox --}}
                    <div id="template-selector-group" class="hidden pt-2 space-y-2 transition-all duration-300 relative custom-combobox" data-custom-init="true">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400">Select Template:</label>
                        <input type="hidden" name="template_id" id="template_id" class="combobox-hidden-input">
                        <button type="button" class="combobox-trigger w-full px-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200 flex justify-between items-center cursor-pointer">
                            <span class="combobox-label">- Choose a template -</span>
                            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="combobox-dropdown hidden absolute z-50 left-0 right-0 mt-1 p-2 bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800/80 rounded-xl shadow-xl backdrop-blur-xl">
                            <div class="relative mb-2">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none text-slate-400">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input type="text" class="combobox-search w-full pl-7 pr-2.5 py-1 text-2xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200" placeholder="Search template...">
                            </div>
                            <div class="combobox-options max-h-40 overflow-y-auto space-y-1">
                                <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" data-value="">- Choose a template -</div>
                                @foreach($templates as $tmpl)
                                    <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" 
                                         data-value="{{ $tmpl->id }}" 
                                         data-channel="{{ $tmpl->channel }}" 
                                         data-niche="{{ strtolower($tmpl->niche) }}">
                                        {{ $tmpl->name }} ({{ ucfirst($tmpl->channel) }} · {{ ucfirst($tmpl->tone) }})
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-[9px] text-slate-400 dark:text-slate-500 leading-relaxed">
                            Template will be rendered with lead data instantly - no AI cost.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3" style="animation: fadeInUp 0.4s ease backwards; animation-delay: 0.1s;">
                <button type="submit" class="btn-primary flex-1 justify-center py-3 font-bold rounded-xl shadow-lg transition duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Launch & Generate</span>
                </button>
                <a href="{{ route('campaigns.index') }}" class="btn-secondary justify-center rounded-xl py-3 px-5">
                    Cancel
                </a>
            </div>
        </div>

        {{-- Right Side: Leads Selection Panel --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card p-6 flex flex-col h-full" style="max-height: 600px; animation: fadeInUp 0.4s ease backwards; animation-delay: 0.08s;">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="6"></circle>
                                <circle cx="12" cy="12" r="2"></circle>
                            </svg>
                            <span>Target Leads Selection</span>
                        </h3>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Choose leads manually or let AI auto-select.</p>
                    </div>
                    <button type="button" id="btn-ai-select" class="w-full sm:w-auto px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs font-bold rounded-lg hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer border border-indigo-500/30">
                        <svg class="w-3.5 h-3.5 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 113.536 0V21h-3.536v-5.064z" />
                        </svg>
                        <span>AI Auto-Select</span>
                    </button>
                </div>

                {{-- Search & Filter Controls --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-2.5 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800/60 z-30">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" id="lead-search-input" placeholder="Search leads by name, email, niche..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200">
                    </div>
                    
                    {{-- Searchable Niche Dropdown --}}
                    <div class="relative custom-combobox" id="combobox-niche">
                        <button type="button" class="combobox-trigger w-full px-3 py-1.5 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200 flex justify-between items-center cursor-pointer">
                            <span class="combobox-label">All Niches</span>
                            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="combobox-dropdown hidden absolute z-50 left-0 right-0 mt-1 p-2 bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800/80 rounded-xl shadow-xl backdrop-blur-xl">
                            <div class="relative mb-2">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none text-slate-400">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input type="text" class="combobox-search w-full pl-7 pr-2.5 py-1 text-2xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200" placeholder="Search niche...">
                            </div>
                            <div class="combobox-options max-h-40 overflow-y-auto space-y-1">
                                <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" data-value="">All Niches</div>
                                @foreach($niches as $niche)
                                    <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" data-value="{{ strtolower($niche) }}">{{ ucfirst($niche) }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Searchable City Dropdown --}}
                    <div class="relative custom-combobox" id="combobox-city">
                        <button type="button" class="combobox-trigger w-full px-3 py-1.5 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200 flex justify-between items-center cursor-pointer">
                            <span class="combobox-label">All Cities</span>
                            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="combobox-dropdown hidden absolute z-50 left-0 right-0 mt-1 p-2 bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800/80 rounded-xl shadow-xl backdrop-blur-xl">
                            <div class="relative mb-2">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none text-slate-400">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input type="text" class="combobox-search w-full pl-7 pr-2.5 py-1 text-2xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200" placeholder="Search city...">
                            </div>
                            <div class="combobox-options max-h-40 overflow-y-auto space-y-1">
                                <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" data-value="">All Cities</div>
                                @foreach($cities as $city)
                                    <div class="combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium" data-value="{{ strtolower($city) }}">{{ ucfirst($city) }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact Info Presence Filters --}}
                <div class="grid grid-cols-3 gap-2.5 mb-4 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800/60 z-20">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-1">Phone Status</label>
                        <input type="hidden" id="filter-phone" value="">
                        <div class="grid grid-cols-3 gap-1">
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 bg-indigo-500/10 text-indigo-600" data-target="filter-phone" data-value="">All</button>
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400" data-target="filter-phone" data-value="yes">Has</button>
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400" data-target="filter-phone" data-value="no">None</button>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-1">Email Status</label>
                        <input type="hidden" id="filter-email" value="">
                        <div class="grid grid-cols-3 gap-1">
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 bg-indigo-500/10 text-indigo-600" data-target="filter-email" data-value="">All</button>
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400" data-target="filter-email" data-value="yes">Has</button>
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400" data-target="filter-email" data-value="no">None</button>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-1">Website Status</label>
                        <input type="hidden" id="filter-website" value="">
                        <div class="grid grid-cols-3 gap-1">
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 bg-indigo-500/10 text-indigo-600" data-target="filter-website" data-value="">All</button>
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400" data-target="filter-website" data-value="yes">Has</button>
                            <button type="button" class="filter-toggle-btn px-2 py-1 text-[10px] font-bold rounded-md border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400" data-target="filter-website" data-value="no">None</button>
                        </div>
                    </div>
                </div>

                {{-- AI Loader Indicator --}}
                <div id="ai-loader" class="hidden py-10 flex flex-col items-center justify-center gap-3">
                    <div class="w-7 h-7 rounded-full border-2 border-indigo-500 border-t-transparent animate-spin"></div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Fetching targets...</p>
                </div>

                {{-- Leads List Container --}}
                <div id="leads-list-container" class="overflow-y-auto pr-2 flex-1 space-y-2.5 transition-all duration-300" style="max-height: 380px;">
                    <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700/50">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" id="select-all-leads" class="form-checkbox rounded cursor-pointer">
                            <span>Select All Leads on Page</span>
                        </label>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold"><span id="selected-count">0</span> selected</span>
                    </div>

                    <div id="no-filtered-leads" class="hidden py-8 text-center text-slate-400 dark:text-slate-500">
                        <svg class="w-8 h-8 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs">No matching leads found</p>
                    </div>

                    <div id="leads-list-body" class="space-y-2.5">
                        {{-- AJAX leads will populate here --}}
                    </div>
                </div>

                {{-- Pagination Controls --}}
                <div id="leads-pagination" class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/60 shrink-0">
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                        Showing <span id="pagination-showing-start">0</span>-<span id="pagination-showing-end">0</span> of <span id="pagination-total">0</span> leads
                    </span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" id="btn-prev-page" class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 px-2" id="pagination-current-page">Page 1 of 1</span>
                        <button type="button" id="btn-next-page" class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Selection state
    let selectedLeadIds = new Set();

    const selectAllCheckbox = document.getElementById('select-all-leads');
    const selectedCountSpan = document.getElementById('selected-count');
    const autoGenerateCb = document.getElementById('auto_generate');
    const channelGroup = document.getElementById('channel-selection-group');
    const btnAiSelect = document.getElementById('btn-ai-select');
    const aiLoader = document.getElementById('ai-loader');
    const leadsListContainer = document.getElementById('leads-list-container');
    const inputNiche = document.getElementById('niche');
    const inputLocation = document.getElementById('location');
    const searchInput = document.getElementById('lead-search-input');
    const noFilteredLeadsMsg = document.getElementById('no-filtered-leads');
    const leadsListBody = document.getElementById('leads-list-body');

    // Filter elements
    const filterPhone = document.getElementById('filter-phone');
    const filterEmail = document.getElementById('filter-email');
    const filterWebsite = document.getElementById('filter-website');

    // Pagination state
    let currentPage = 1;

    // Mode Selector Elements
    const modeCards = document.querySelectorAll('.mode-card');
    const templateSelectorGroup = document.getElementById('template-selector-group');
    const channelCheckboxes = document.querySelectorAll('.channel-checkbox');

    // Initialize Template SearchableCombobox
    const cbTemplate = new SearchableCombobox('template-selector-group', '- Choose a template -');

    function updateModeUI() {
        const selectedMode = document.querySelector('input[name="outreach_mode"]:checked')?.value;
        modeCards.forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            if (radio.checked) {
                card.classList.add('border-indigo-500', 'bg-indigo-500/5', 'dark:bg-indigo-500/10', 'shadow-sm', 'shadow-indigo-500/10');
                card.classList.remove('border-slate-200', 'dark:border-slate-700');
            } else {
                card.classList.remove('border-indigo-500', 'bg-indigo-500/5', 'dark:bg-indigo-500/10', 'shadow-sm', 'shadow-indigo-500/10');
                card.classList.add('border-slate-200', 'dark:border-slate-700');
            }
        });

        if (selectedMode === 'template') {
            templateSelectorGroup.classList.remove('hidden');
            filterTemplateOptions();
        } else {
            templateSelectorGroup.classList.add('hidden');
        }
    }

    function filterTemplateOptions() {
        const selectedChannels = Array.from(channelCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        const options = templateSelectorGroup.querySelectorAll('.combobox-option[data-channel]');
        let selectedIsHidden = false;

        options.forEach(opt => {
            const ch = opt.getAttribute('data-channel');
            if (!ch) return; // Skip placeholder option
            const channelMatch = selectedChannels.length === 0 || selectedChannels.includes(ch);

            if (channelMatch) {
                opt.style.display = '';
                opt.classList.remove('hidden-by-channel');
            } else {
                opt.style.display = 'none';
                opt.classList.add('hidden-by-channel');
                if (cbTemplate.value == opt.getAttribute('data-value')) {
                    selectedIsHidden = true;
                }
            }
        });

        if (selectedIsHidden) {
            cbTemplate.selectByValue('');
        }
    }

    modeCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;
            updateModeUI();
        });
    });

    channelCheckboxes.forEach(cb => cb.addEventListener('change', filterTemplateOptions));

    // Niche & City Multi-Select State
    let selectedNiches = [];
    let selectedCities = [];

    function initMultiSelectCombobox(containerId, placeholder, onChange) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const trigger = container.querySelector('.combobox-trigger');
        const label = container.querySelector('.combobox-label');
        const dropdown = container.querySelector('.combobox-dropdown');
        const search = container.querySelector('.combobox-search');
        const options = Array.from(container.querySelectorAll('.combobox-option'));

        const selectedValues = new Set();

        function updateLabel() {
            if (selectedValues.size === 0) {
                label.textContent = placeholder;
            } else if (selectedValues.size === 1) {
                const only = options.find(opt => selectedValues.has(opt.getAttribute('data-value')));
                label.textContent = only ? only.textContent.trim() : `${selectedValues.size} selected`;
            } else {
                label.textContent = `${selectedValues.size} selected`;
            }
        }

        function updateOptionStyles() {
            options.forEach(opt => {
                const val = opt.getAttribute('data-value');
                if (!val) return;
                if (selectedValues.has(val)) {
                    opt.classList.add('bg-indigo-500/10', 'text-indigo-600', 'dark:text-indigo-400');
                } else {
                    opt.classList.remove('bg-indigo-500/10', 'text-indigo-600', 'dark:text-indigo-400');
                }
            });
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                search.value = '';
                search.focus();
                filterOptions('');
            }
        });

        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function filterOptions(query) {
            const q = query.toLowerCase().trim();
            options.forEach(opt => {
                const text = opt.textContent.toLowerCase();
                const isAll = opt.getAttribute('data-value') === '';
                opt.classList.toggle('hidden', !isAll && !text.includes(q));
            });
        }

        search.addEventListener('input', () => filterOptions(search.value));

        options.forEach(opt => {
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                const val = opt.getAttribute('data-value');
                if (!val) {
                    selectedValues.clear();
                } else if (selectedValues.has(val)) {
                    selectedValues.delete(val);
                } else {
                    selectedValues.add(val);
                }

                updateOptionStyles();
                updateLabel();
                onChange(Array.from(selectedValues));
            });
        });

        updateLabel();
    }

    initMultiSelectCombobox('combobox-niche', 'All Niches', function(values) {
        selectedNiches = values;
        fetchLeads(1);
    });

    initMultiSelectCombobox('combobox-city', 'All Cities', function(values) {
        selectedCities = values;
        fetchLeads(1);
    });

    document.querySelectorAll('.filter-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const value = this.getAttribute('data-value');
            const input = document.getElementById(target);
            if (!input) return;

            input.value = value;

            document.querySelectorAll(`.filter-toggle-btn[data-target="${target}"]`).forEach(sibling => {
                sibling.classList.remove('bg-indigo-500/10', 'text-indigo-600');
                sibling.classList.add('text-slate-500', 'dark:text-slate-400');
            });

            this.classList.add('bg-indigo-500/10', 'text-indigo-600');
            this.classList.remove('text-slate-500', 'dark:text-slate-400');

            fetchLeads(1);
        });
    });

    // Debounce Helper
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Fetch leads from paginated server endpoint
    async function fetchLeads(page = 1) {
        currentPage = page;
        const query = searchInput.value.trim();
        const niches = selectedNiches;
        const cities = selectedCities;
        const hasPhone = filterPhone.value;
        const hasEmail = filterEmail.value;
        const hasWebsite = filterWebsite.value;

        // Show loading state
        aiLoader.classList.remove('hidden');
        leadsListContainer.classList.add('opacity-40');
        leadsListBody.innerHTML = '';

        try {
            const url = new URL('{{ route("campaigns.leads.filter") }}', window.location.origin);
            url.searchParams.append('page', page);
            if (query) url.searchParams.append('search', query);
            niches.forEach(n => url.searchParams.append('niche[]', n));
            cities.forEach(c => url.searchParams.append('city[]', c));
            if (hasPhone) url.searchParams.append('has_phone', hasPhone);
            if (hasEmail) url.searchParams.append('has_email', hasEmail);
            if (hasWebsite) url.searchParams.append('has_website', hasWebsite);

            const response = await fetch(url);
            const data = await response.json();

            if (data.status === 'success') {
                renderLeads(data.leads);
                renderPagination(data.pagination);
            }
        } catch (error) {
            console.error('Error fetching leads:', error);
            window.showToast('Failed to load leads from database', 'error');
        } finally {
            aiLoader.classList.add('hidden');
            leadsListContainer.classList.remove('opacity-40');
        }
    }

    function renderLeads(leads) {
        leadsListBody.innerHTML = '';

        if (leads.length === 0) {
            noFilteredLeadsMsg.classList.remove('hidden');
            updateSelectAllCheckboxState();
            return;
        }
        noFilteredLeadsMsg.classList.add('hidden');

        leads.forEach(lead => {
            const isChecked = selectedLeadIds.has(parseInt(lead.id));
            const row = document.createElement('div');
            row.className = `lead-row flex items-start gap-3 p-3 rounded-lg border transition-all duration-200 ${isChecked ? 'border-indigo-500/30 bg-indigo-500/5 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-800'} hover:!border-indigo-500/20 hover:!bg-indigo-500/5`;
            row.setAttribute('data-id', lead.id);

            let contactInfo = '';
            if (lead.email) {
                contactInfo += `<span class="truncate">Email: ${lead.email}</span>`;
            } else if (lead.phone) {
                contactInfo += `<span class="truncate">Phone: ${lead.phone}</span>`;
            }

            row.innerHTML = `
                <input type="checkbox" value="${lead.id}" ${isChecked ? 'checked' : ''} class="lead-checkbox form-checkbox mt-0.5 rounded cursor-pointer">
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start gap-2">
                        <h4 class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate">${lead.business_name}</h4>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 shrink-0 border border-indigo-500/15">${lead.niche}</span>
                    </div>
                    <div class="flex items-center gap-3 mt-1 text-[10px] text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-0.5">
                            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            <span>${lead.city}</span>
                        </span>
                        ${contactInfo}
                    </div>
                </div>
            `;

            // Row Highlight & toggle on check click
            const checkbox = row.querySelector('.lead-checkbox');
            checkbox.addEventListener('change', function() {
                toggleLeadSelection(lead.id, checkbox.checked, row);
            });

            // Make the entire row clickable to select
            row.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    checkbox.checked = !checkbox.checked;
                    toggleLeadSelection(lead.id, checkbox.checked, row);
                }
            });

            leadsListBody.appendChild(row);
        });

        updateSelectAllCheckboxState();
    }

    function renderPagination(pagination) {
        const showingStart = document.getElementById('pagination-showing-start');
        const showingEnd = document.getElementById('pagination-showing-end');
        const totalSpan = document.getElementById('pagination-total');
        const currentPageSpan = document.getElementById('pagination-current-page');
        const btnPrev = document.getElementById('btn-prev-page');
        const btnNext = document.getElementById('btn-next-page');

        const total = pagination.total;
        const currentPageVal = pagination.current_page;
        const lastPage = pagination.last_page;
        const perPage = pagination.per_page;

        const start = total === 0 ? 0 : (currentPageVal - 1) * perPage + 1;
        const end = Math.min(currentPageVal * perPage, total);

        showingStart.textContent = start;
        showingEnd.textContent = end;
        totalSpan.textContent = total;
        currentPageSpan.textContent = `Page ${currentPageVal} of ${lastPage}`;

        btnPrev.disabled = currentPageVal <= 1;
        btnNext.disabled = currentPageVal >= lastPage;
    }

    function toggleLeadSelection(id, isSelected, row) {
        const numericId = parseInt(id);
        if (isSelected) {
            selectedLeadIds.add(numericId);
            if (row) {
                row.classList.add('border-indigo-500/30', 'bg-indigo-500/5', 'dark:bg-indigo-500/10');
                row.classList.remove('border-slate-200', 'dark:border-slate-800');
            }
        } else {
            selectedLeadIds.delete(numericId);
            if (row) {
                row.classList.remove('border-indigo-500/30', 'bg-indigo-500/5', 'dark:bg-indigo-500/10');
                row.classList.add('border-slate-200', 'dark:border-slate-800');
            }
        }
        updateSelectedCount();
        updateSelectAllCheckboxState();
    }

    function updateSelectedCount() {
        selectedCountSpan.textContent = selectedLeadIds.size;
    }

    function updateSelectAllCheckboxState() {
        const checkboxes = leadsListBody.querySelectorAll('.lead-checkbox');
        if (checkboxes.length === 0) {
            selectAllCheckbox.checked = false;
            return;
        }

        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!selectedLeadIds.has(parseInt(cb.value))) {
                allChecked = false;
            }
        });
        selectAllCheckbox.checked = allChecked;
    }

    // Handlers for search, pagination, and filter changes
    searchInput.addEventListener('input', debounce(() => fetchLeads(1), 300));

    document.getElementById('btn-prev-page').addEventListener('click', () => {
        if (currentPage > 1) fetchLeads(currentPage - 1);
    });
    document.getElementById('btn-next-page').addEventListener('click', () => {
        fetchLeads(currentPage + 1);
    });

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const shouldSelectAll = selectAllCheckbox.checked;
            const checkboxes = leadsListBody.querySelectorAll('.lead-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = shouldSelectAll;
                const id = parseInt(cb.value);
                const row = cb.closest('.lead-row');
                toggleLeadSelection(id, shouldSelectAll, row);
            });
        });
    }

    // Toggle Channel Selection View based on Auto-Generate checkbox
    if (autoGenerateCb && channelGroup) {
        autoGenerateCb.addEventListener('change', function() {
            if (autoGenerateCb.checked) {
                channelGroup.classList.remove('hidden');
            } else {
                channelGroup.classList.add('hidden');
            }
        });
    }

    // Intercept form submit to append all selected lead IDs
    const campaignForm = document.getElementById('campaign-create-form');
    campaignForm.addEventListener('submit', function(e) {
        // Clear any old hidden inputs
        campaignForm.querySelectorAll('.dynamic-lead-id').forEach(el => el.remove());

        if (selectedLeadIds.size === 0) {
            e.preventDefault();
            window.showToast('Please select at least 1 lead for this campaign!', 'error');
            return;
        }

        // Append selected IDs
        selectedLeadIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'lead_ids[]';
            input.value = id;
            input.className = 'dynamic-lead-id';
            campaignForm.appendChild(input);
        });
    });

    // Fetch first page of leads on load
    fetchLeads(1);

    // AI Auto-Select Integration
    if (btnAiSelect) {
        btnAiSelect.addEventListener('click', async function() {
            const nicheVal = inputNiche.value.trim();
            const locationVal = inputLocation.value.trim();

            if (!nicheVal && !locationVal) {
                window.showToast('Please fill in Target Niche or Target Location first so AI can match leads!', 'error');
                return;
            }

            // Show loader, hide list
            aiLoader.classList.remove('hidden');
            leadsListContainer.classList.add('opacity-40');
            btnAiSelect.disabled = true;

            try {
                const response = await fetch('{{ route("campaigns.suggest-leads") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        niche: nicheVal,
                        location: locationVal
                    })
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const result = await response.json();
                const matchedIds = result.ids || [];

                // Reset Selection
                selectedLeadIds.clear();

                // Add matched IDs
                matchedIds.forEach(id => selectedLeadIds.add(parseInt(id)));

                // Re-render current page checkboxes & counter
                fetchLeads(currentPage);

                if (matchedIds.length > 0) {
                    window.showToast(`AI matched and selected ${matchedIds.length} relevant leads!`, 'success');
                } else {
                    window.showToast('AI could not find close matches. You can still select leads manually.', 'error');
                }

            } catch (error) {
                console.error('AI Selection error:', error);
                window.showToast('An error occurred during AI matching. Please select leads manually.', 'error');
            } finally {
                aiLoader.classList.add('hidden');
                leadsListContainer.classList.remove('opacity-40');
                btnAiSelect.disabled = false;
            }
        });
    }
});
</script>
@endsection


