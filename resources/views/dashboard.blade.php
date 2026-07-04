@extends('layouts.app')

@section('title', 'Dashboard — Sandesa')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12">
    
    {{-- Left Column --}}
    <div class="lg:col-span-4 flex flex-col gap-6">
        
        {{-- Quick Actions --}}
        <div class="dash-card">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Quick actions</h3>
                    <p class="text-xs text-slate-500 mt-0.5">You can quick action at a time.</p>
                </div>
                <button class="w-8 h-8 rounded-full hover:bg-slate-50 flex items-center justify-center text-slate-400">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('campaigns.create') }}" class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/50 transition cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 text-left leading-tight">Create<br>Campaign</span>
                </a>
                <a href="{{ route('leads.index') }}" class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:border-sky-100 hover:bg-sky-50/50 transition cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center text-sky-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 text-left leading-tight">Import<br>Contacts</span>
                </a>
                <a href="#" class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:border-emerald-100 hover:bg-emerald-50/50 transition cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 text-left leading-tight">Design<br>Template</span>
                </a>
                <a href="{{ route('outreach.index') }}" class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:border-rose-100 hover:bg-rose-50/50 transition cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 text-left leading-tight">View<br>Reports</span>
                </a>
            </div>
        </div>

        {{-- Active Automations / Outreach Channels --}}
        <div class="dash-card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Outreach Channels</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Campaign status by communication channel.</p>
                </div>
                <button class="w-8 h-8 rounded-full hover:bg-slate-50 flex items-center justify-center text-slate-400">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                </button>
            </div>
            
            <div class="flex items-end gap-6 border-b border-slate-100 pb-6 mb-4 relative">
                <!-- Chart Background lines -->
                <div class="absolute right-0 bottom-6 w-32 h-20 flex gap-1 items-end opacity-20">
                    <div class="w-3 bg-slate-200 h-full rounded-t-sm"></div>
                    <div class="w-3 bg-slate-200 h-4/5 rounded-t-sm"></div>
                    <div class="w-3 bg-slate-200 h-3/5 rounded-t-sm"></div>
                    <div class="w-3 bg-slate-200 h-4/5 rounded-t-sm"></div>
                    <div class="w-3 bg-slate-200 h-full rounded-t-sm"></div>
                </div>

                <div class="flex items-start gap-4 relative z-10 w-full">
                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center flex-shrink-0 mt-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-slate-800 text-sm">Cold Email Campaigns</h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-sm font-bold text-slate-800">92%</span>
                            <span class="text-xs text-slate-400">/ success rate</span>
                        </div>
                    </div>
                    <div class="w-12 h-16 bg-blue-200 rounded-lg relative self-end flex items-center justify-center overflow-hidden">
                        <div class="absolute bottom-0 left-0 w-full bg-blue-400 h-[92%]"></div>
                        <span class="relative z-10 text-[10px] font-bold text-blue-900">92%</span>
                    </div>
                </div>
            </div>

            <div class="flex items-end gap-6 mb-6 relative">
                 <div class="flex items-start gap-4 relative z-10 w-full">
                    <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center flex-shrink-0 mt-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-slate-800 text-sm">WhatsApp Campaigns</h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            <span class="text-sm font-bold text-slate-800">88%</span>
                            <span class="text-xs text-slate-400">/ response rate</span>
                        </div>
                    </div>
                    <div class="w-12 h-10 bg-orange-100 rounded-lg relative self-end flex items-center justify-center overflow-hidden">
                        <div class="absolute bottom-0 left-0 w-full bg-orange-300 h-[88%]"></div>
                        <span class="relative z-10 text-[10px] font-bold text-orange-900">88%</span>
                    </div>
                </div>
            </div>

            <a href="{{ route('campaigns.index') }}" class="block text-center w-full py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-semibold transition shadow-md shadow-slate-900/20">
                Manage Campaigns
            </a>
        </div>

        {{-- Reputation Score --}}
        <div class="dash-card flex flex-col justify-between flex-1">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Reputation Score</h3>
                    <p class="text-xs text-slate-500 mt-0.5">See your details reputation score.</p>
                </div>
                <button class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                </button>
            </div>
            
            <div class="flex justify-between items-end mb-4">
                <span class="text-lg font-bold text-slate-800">Rate</span>
                <span class="text-lg font-bold text-blue-600">Good!</span>
            </div>

            <div class="relative flex justify-center mb-6 mt-4">
                <div class="w-48 h-24 relative overflow-hidden">
                    <div class="w-48 h-48 rounded-full border-[12px] border-slate-100 absolute top-0 left-0"></div>
                    <div class="w-48 h-48 rounded-full border-[12px] border-rose-500 absolute top-0 left-0" style="clip-path: polygon(0 0, 100% 0, 100% 50%, 0 50%); transform: rotate(45deg);"></div>
                    
                    {{-- Needle --}}
                    <div class="absolute bottom-0 left-1/2 w-1 h-20 bg-rose-500 origin-bottom rounded-t-full shadow" style="transform: translateX(-50%) rotate(40deg);">
                        <div class="w-3 h-3 bg-rose-500 rounded-full absolute -top-1 -left-1"></div>
                    </div>
                </div>
                <div class="absolute bottom-[-10px] left-1/2 transform -translate-x-1/2 flex items-baseline gap-1 bg-white px-2">
                    <span class="text-3xl font-bold text-rose-500">85.2</span>
                    <span class="text-sm font-bold text-rose-500">%</span>
                </div>
            </div>

            <div class="bg-emerald-50/50 rounded-xl p-3 flex items-start gap-3 mt-4 border border-emerald-100/50">
                <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </div>
                <p class="text-[11px] text-slate-600 font-medium">Reputation Score are auto generate based on recent engagements <span class="text-emerald-500 font-bold">85.2% high performance</span></p>
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="lg:col-span-8 flex flex-col gap-6">
        
        {{-- Top KPI Row --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Outreach Sent --}}
            <div class="dash-card py-5 flex flex-col justify-between h-40">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-bold text-slate-800 text-sm">Outreach Sent</h4>
                    <button class="w-6 h-6 rounded flex items-center justify-center text-blue-500 hover:bg-blue-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <div class="flex items-center gap-1 text-emerald-500 text-xs font-bold mb-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                            +6.3%
                        </div>
                        <div class="text-3xl font-bold text-slate-800 tracking-tight">{{ number_format($outreachSent ?? 1243) }}</div>
                    </div>
                    <div class="w-24 h-12 relative overflow-hidden">
                        {{-- Semi circle arch chart --}}
                        <svg viewBox="0 0 100 50" class="w-full h-full overflow-visible">
                            <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="#e2e8f0" stroke-width="4" stroke-linecap="round"/>
                            <path d="M 10 50 A 40 40 0 0 1 70 20" fill="none" stroke="#3b82f6" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                        <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 text-[10px] font-bold text-blue-500">1.2k+</span>
                    </div>
                </div>
            </div>

            {{-- Response Rate --}}
            <div class="dash-card py-5 flex flex-col justify-between h-40">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-bold text-slate-800 text-sm">Response Rate</h4>
                    <button class="w-6 h-6 rounded flex items-center justify-center text-blue-500 hover:bg-blue-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <div class="flex items-center gap-1 text-emerald-500 text-xs font-bold mb-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                            +4.2%
                        </div>
                        <div class="text-3xl font-bold text-slate-800 tracking-tight">24.8%</div>
                    </div>
                    <div class="flex items-end gap-1.5 h-12 w-24">
                        <div class="w-4 bg-orange-100 rounded-t-sm h-1/3"></div>
                        <div class="w-4 bg-orange-100 rounded-t-sm h-1/2"></div>
                        <div class="w-4 bg-orange-100 rounded-t-sm h-full relative">
                            <div class="absolute -top-7 left-1/2 transform -translate-x-1/2 bg-orange-100 text-orange-600 text-[10px] font-bold py-0.5 px-1.5 rounded">24.8%</div>
                        </div>
                        <div class="w-4 bg-orange-100 rounded-t-sm h-2/3"></div>
                    </div>
                </div>
            </div>

            {{-- Leads Scraped --}}
            <div class="dash-card py-5 flex flex-col justify-between h-40">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-bold text-slate-800 text-sm">Leads Scraped</h4>
                    <button class="w-6 h-6 rounded flex items-center justify-center text-blue-500 hover:bg-blue-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <div class="flex items-center gap-1 text-blue-500 text-xs font-bold mb-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                            +12.5%
                        </div>
                        <div class="text-3xl font-bold text-slate-800 tracking-tight">+{{ number_format($totalLeads ?? 1248) }}</div>
                    </div>
                    <div class="w-24 h-12 flex flex-col justify-end relative pb-1">
                         <span class="absolute top-0 right-2 text-[10px] font-bold text-blue-500">1k+</span>
                         <div class="w-full h-8 flex items-center">
                             <div class="w-full h-px bg-slate-200 absolute top-1/2"></div>
                             <div class="w-2 h-5 rounded-[2px] bg-blue-100 border border-blue-400 absolute left-[30%] z-10 transform -translate-y-1/2"></div>
                             <div class="w-2 h-5 rounded-[2px] bg-blue-100 border border-blue-400 absolute left-[70%] z-10 transform -translate-y-1/2"></div>
                             <div class="h-1 bg-blue-400 absolute left-[30%] w-[40%] transform translate-y-px"></div>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Campaign Performance Chart --}}
        <div class="dash-card flex-1 min-h-[340px] flex flex-col">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Campaign Performance</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Monitor how your latest sends are performing in real time.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Last 07 days
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <button class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                    </button>
                </div>
            </div>
            
            <div class="flex gap-4 mb-4">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span> Leads Scraped
                </div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-rose-400"></span> Outreach Sent
                </div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Replies Received
                </div>
            </div>

            <div class="flex-1 w-full relative">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        {{-- Bottom Row: Deliverability & Schedule --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Deliverability Score --}}
            <div class="dash-card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Deliverability Score</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Your inbox placement is healthy.</p>
                    </div>
                    <button class="w-8 h-8 rounded-full hover:bg-slate-50 flex items-center justify-center text-slate-400">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                    </button>
                </div>
                
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-baseline gap-1">
                        <div class="text-4xl font-bold text-slate-800 tracking-tight">82</div>
                        <div class="text-xl font-bold text-slate-800">/100</div>
                    </div>
                    <div class="text-xs text-slate-600 max-w-[120px] text-right font-medium leading-tight">Your inbox placement<br>is healthy</div>
                </div>

                <div class="text-xs text-slate-400 mb-2 font-medium">Placement</div>
                <div class="w-full bg-blue-50 rounded-full h-4 flex mb-8 overflow-hidden">
                    <div class="bg-blue-100 h-full w-[25%] border-r-2 border-white"></div>
                    <div class="bg-blue-200 h-full w-[25%] border-r-2 border-white"></div>
                    <div class="bg-blue-500 h-full w-[32%] relative border-r-2 border-white">
                        <div class="absolute -top-1 right-0 w-1 h-6 bg-blue-500 rounded"></div>
                    </div>
                    <div class="bg-slate-50 h-full flex-1"></div>
                </div>

                <div>
                    <h5 class="text-xs font-bold text-slate-800 mb-3">Indicators:</h5>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Spam complaints</span>
                            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-500"></span><span class="font-bold text-slate-800">Low</span></div>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Bounce rate</span>
                            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span class="font-bold text-slate-800">Stable</span></div>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Domain authentication</span>
                            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-500"></span><span class="font-bold text-slate-800">Verified</span></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Schedule Campaign --}}
            <div class="dash-card">
                 <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Schedule Campaign</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Your upcoming automated outreach sends.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1 text-xs font-semibold text-slate-800 bg-slate-50 px-2 py-1 rounded-md">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            18 May 2026 - 21 May 2026
                        </div>
                        <button class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                        </button>
                    </div>
                </div>

                <div class="relative w-full overflow-hidden mt-6">
                    {{-- Time axis --}}
                    <div class="flex justify-between text-[10px] text-slate-400 font-medium mb-4 px-2 border-b border-dashed border-slate-200 pb-2">
                        <span>07:00</span><span>7:15</span><span>7:30</span><span>7:45</span><span>8:00</span><span>8:15</span><span>8:30</span>
                    </div>

                    <div class="space-y-4">
                        {{-- Today Event --}}
                        <div>
                            <div class="text-xs font-semibold text-blue-500 mb-2 flex items-center gap-2">
                                Today (20 May 2026)
                                <div class="flex-1 border-t border-dashed border-blue-200"></div>
                            </div>
                            <div class="bg-blue-50 rounded-xl p-3 flex justify-between items-center border border-blue-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-400 text-white flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-bold text-slate-800">Jakarta Dentist Campaign</h5>
                                        <p class="text-xs text-slate-500">09:00 - 11:00 AM</p>
                                    </div>
                                </div>
                                <button class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Future Event --}}
                        <div>
                            <div class="text-xs font-semibold text-slate-400 mb-2">Thu 21 May 2026</div>
                            <div class="bg-orange-50 rounded-xl p-3 flex justify-between items-center border border-orange-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-orange-400 text-white flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-bold text-slate-800">Surabaya Cafe Campaign</h5>
                                        <p class="text-xs text-slate-500">10:00 - 12:00 AM</p>
                                    </div>
                                </div>
                                <button class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    // Gradients
    const gradientBlue = ctx.createLinearGradient(0, 0, 0, 300);
    gradientBlue.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
    gradientBlue.addColorStop(1, 'rgba(59, 130, 246, 0)');
    
    const gradientRose = ctx.createLinearGradient(0, 0, 0, 300);
    gradientRose.addColorStop(0, 'rgba(244, 63, 94, 0.2)');
    gradientRose.addColorStop(1, 'rgba(244, 63, 94, 0)');

    const gradientEmerald = ctx.createLinearGradient(0, 0, 0, 300);
    gradientEmerald.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
    gradientEmerald.addColorStop(1, 'rgba(16, 185, 129, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['May 14, 2026', 'May 15, 2026', 'May 16, 2026', 'May 17, 2026', 'May 18, 2026', 'May 19, 2026', 'May 20, 2026'],
            datasets: [
                {
                    label: 'Leads Scraped',
                    data: [240, 380, 420, 560, 680, 820, 950],
                    borderColor: '#3b82f6',
                    backgroundColor: gradientBlue,
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5
                },
                {
                    label: 'Outreach Sent',
                    data: [180, 290, 350, 480, 590, 710, 840],
                    borderColor: '#f43f5e',
                    backgroundColor: gradientRose,
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#f43f5e',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5
                },
                {
                    label: 'Replies Received',
                    data: [25, 45, 62, 88, 112, 145, 178],
                    borderColor: '#10b981',
                    backgroundColor: gradientEmerald,
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 12,
                    titleFont: { size: 13, family: 'Inter' },
                    bodyFont: { size: 12, family: 'Inter' },
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f8fafc',
                        drawBorder: false,
                        tickLength: 0
                    },
                    ticks: {
                        font: { family: 'Inter', size: 11, weight: '500' },
                        color: '#94a3b8',
                        padding: 10,
                        callback: function(value) {
                            return value;
                        }
                    },
                    border: { display: false }
                },
                x: {
                    grid: {
                        display: true,
                        color: '#f8fafc',
                        drawBorder: false,
                        tickLength: 0
                    },
                    ticks: {
                        font: { family: 'Inter', size: 11, weight: '500' },
                        color: '#94a3b8',
                        padding: 10
                    },
                    border: { display: false }
                }
            }
        }
    });
});
</script>
@endsection
