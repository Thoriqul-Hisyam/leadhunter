<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sandesa — Cari leads, outreach personal otomatis bertenaga AI, dan kelola campaign dengan mudah.">
    <title>@yield('title', 'Sandesa')</title>
    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <style>
        body { background-color: #f8f6fc; }
        .glass-bg {
            background: radial-gradient(circle at 10% 10%, #e8e3f5 0%, transparent 40%),
                        radial-gradient(circle at 90% 90%, #f6e6ed 0%, transparent 40%),
                        radial-gradient(circle at 50% 50%, #eff2f9 0%, transparent 60%);
            background-color: #f9f8fc;
            background-attachment: fixed;
        }
        .nav-pill {
            display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.6rem 1.25rem;
            border-radius: 9999px; font-size: 0.875rem; font-weight: 600;
            transition: all 0.2s;
        }
        .nav-pill.active { background-color: #0f172a; color: #ffffff; }
        .nav-pill:not(.active) { background-color: #ffffff; color: #64748b; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border: 1px solid rgba(226, 232, 240, 0.6); }
        .nav-pill:not(.active):hover { color: #0f172a; border-color: #cbd5e1; }
        
        .dash-card {
            background-color: #ffffff; border-radius: 1.5rem; padding: 1.5rem;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.03), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(241, 245, 249, 1);
        }
        
        /* Custom scrollbar for webkit */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Hide Google Translate top banner bar & branding */
        .goog-te-banner-frame {
            display: none !important;
        }
        body {
            top: 0px !important;
        }
        .goog-te-gadget {
            font-size: 0px !important;
        }
        .goog-te-gadget span {
            display: none !important;
        }
        .goog-te-gadget img {
            display: none !important;
        }
        .goog-te-combo {
            display: none !important;
        }
        .goog-text-highlight {
            background-color: transparent !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 dark:text-slate-200 glass-bg min-h-screen flex flex-col">
    {{-- ===== TOP NAVIGATION ===== --}}
    <nav id="main-navbar" class="w-full px-6 py-4 flex items-center justify-between gap-4 sticky top-0 z-50 relative transition-all duration-300">
        {{-- Left: Sandesa Logo (Notion-style) --}}
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 mr-4 group" title="Sandesa Dashboard">
            <!-- Notion-style 3D Isometric SVG Logomark -->
            <svg class="w-9 h-9 transition-transform duration-200 group-hover:scale-105 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 268">
                <defs>
                    <!-- Official Sandesa Indigo-to-Violet Gradient for the 'S' -->
                    <linearGradient id="sandesaGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#6366f1" /> <!-- Indigo 500 -->
                        <stop offset="100%" stop-color="#a855f7" /> <!-- Purple/Violet 500 -->
                    </linearGradient>
                    <!-- Glowing pastel gradient for the 3D top lid -->
                    <linearGradient id="sandesaTopGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#818cf8" /> <!-- Indigo 400 -->
                        <stop offset="100%" stop-color="#c084fc" /> <!-- Purple/Violet 400 -->
                    </linearGradient>
                </defs>
                <!-- Outer silhouette (tilted box boundary) -->
                <path class="fill-slate-900 dark:fill-white" d="M164.09.608 16.092 11.538C4.155 12.573 0 20.374 0 29.726v162.245c0 7.284 2.585 13.516 8.826 21.843l34.789 45.237c5.715 7.284 10.912 8.844 21.825 8.327l171.864-10.404c14.532-1.035 18.696-7.801 18.696-19.24V55.207c0-5.911-2.336-7.614-9.21-12.66l-1.185-.856L198.37 8.409C186.94.1 182.27-.952 164.09.608Z"/>
                <!-- Top Face (Tilted top lid - glowing pastel gradient) -->
                <path fill="url(#sandesaTopGrad)" d="M69.327 52.22c-14.033.945-17.216 1.159-25.186-5.323L23.876 30.778c-2.06-2.086-1.026-4.69 4.163-5.207l142.274-10.395c11.947-1.043 18.17 3.12 22.842 6.758l24.401 17.68c1.043.525 3.638 3.637.517 3.637L71.146 52.095l-1.819.125Z"/>
                <!-- Front Face (tilted main container) -->
                <path class="fill-white dark:fill-slate-950" d="M52.967 236.174V81.222c0-6.767 2.077-9.887 8.3-10.413L230.02 60.93c5.724-.517 8.31 3.12 8.31 9.879v153.917c0 6.767-1.044 12.49-10.387 13.008l-161.487 9.361c-9.343.517-13.489-2.594-13.489-10.921Z"/>
                <!-- Center skewed Letter 'S' in premium serif style (perfectly centered, colorful gradient) -->
                <g transform="matrix(0.97, -0.10, 0, 0.95, 7, 45)">
                    <text x="145" y="165" font-family="Georgia, serif" font-weight="900" font-size="142" text-anchor="middle" fill="url(#sandesaGrad)">S</text>
                </g>
            </svg>
            
            <!-- Clean brand wordmark in elegant matching typography -->
            <span class="text-xl font-bold text-slate-800 dark:text-white hidden sm:block tracking-tight" style="font-family: 'Inter', sans-serif;">Sandesa</span>
        </a>
        
        {{-- Middle: Nav Pills (Absolutely Centered) --}}
        @auth
        <div class="hidden lg:flex items-center gap-3 absolute left-1/2 -translate-x-1/2">
            <a href="{{ route('dashboard') }}" class="nav-pill {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                {{ __('Dashboard') }}
            </a>
            <a href="{{ route('leads.index') }}" class="nav-pill {{ request()->routeIs('leads.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ __('Leads') }}
            </a>
            <a href="{{ route('campaigns.index') }}" class="nav-pill {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                {{ __('Campaign') }}
            </a>
            <a href="{{ route('templates.index') }}" class="nav-pill {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('Templates') }}
            </a>
            <a href="{{ route('outreach.index') }}" class="nav-pill {{ request()->routeIs('outreach.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ __('Outreach') }}
            </a>
            @if(Auth::user()->is_admin)
                <a href="{{ route('users.index') }}" class="nav-pill {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ __('Users') }}
                </a>
                <a href="{{ route('roles.index') }}" class="nav-pill {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    {{ __('Roles') }}
                </a>
            @endif
        </div>
        @endauth
        
        {{-- Right: Search & Profile --}}
        <div class="flex items-center gap-3">
            @auth
                <div class="relative hidden md:block">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" placeholder="Search..." class="w-56 bg-white border border-slate-200/80 pl-9 pr-4 py-2 rounded-full text-sm outline-none focus:border-indigo-500 shadow-sm transition placeholder-slate-400 font-medium">
                </div>
                <form action="{{ route('locale.update') }}" method="POST" id="locale-form" class="shrink-0">
                    @csrf
                    <input type="hidden" name="locale" id="locale-hidden-input" value="{{ app()->getLocale() }}">
                    <button type="button" id="locale-toggle-btn"
                        class="relative flex items-center w-[72px] h-9 bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-full shadow-sm hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-300 cursor-pointer select-none overflow-hidden group"
                        title="{{ app()->getLocale() === 'id' ? 'Switch to English' : 'Ganti ke Bahasa Indonesia' }}">
                        {{-- Sliding indicator pill --}}
                        <span id="locale-slider" class="absolute top-0.5 w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 shadow-md transition-all duration-300 ease-in-out {{ app()->getLocale() === 'en' ? 'left-[calc(100%-2.125rem-2px)]' : 'left-0.5' }}"></span>
                        {{-- ID flag (real image) --}}
                        <span class="relative z-10 flex items-center justify-center w-8 h-8 transition-all duration-300 {{ app()->getLocale() === 'id' ? 'scale-110' : 'scale-90 grayscale opacity-60' }}" id="flag-id">
                            <img src="https://flagcdn.com/w40/id.png" srcset="https://flagcdn.com/w80/id.png 2x" alt="ID" class="w-5 h-auto rounded-sm shadow-sm">
                        </span>
                        {{-- EN flag (real image) --}}
                        <span class="relative z-10 flex items-center justify-center w-8 h-8 transition-all duration-300 {{ app()->getLocale() === 'en' ? 'scale-110' : 'scale-90 grayscale opacity-60' }}" id="flag-en">
                            <img src="https://flagcdn.com/w40/gb.png" srcset="https://flagcdn.com/w80/gb.png 2x" alt="EN" class="w-5 h-auto rounded-sm shadow-sm">
                        </span>
                    </button>
                </form>
                <!-- Live Notification Bell with Frosted Glass Dropdown -->
                <div class="relative" id="notification-bell-container">
                    <button id="notification-bell-btn" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 rounded-full shadow-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition relative cursor-pointer focus:outline-none" title="Notifikasi Scraping">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <!-- Unread notification dot badge -->
                        <span id="notification-badge" class="absolute top-0.5 right-0.5 w-2.5 h-2.5 bg-rose-500 rounded-full border border-white dark:border-slate-800 hidden animate-pulse"></span>
                    </button>

                    <!-- Premium Glassmorphic Dropdown Menu -->
                    <div id="notification-dropdown" class="absolute right-0 mt-3 w-80 max-w-sm rounded-2xl shadow-xl backdrop-blur-md bg-white/95 dark:bg-slate-900/95 border border-slate-200/60 dark:border-slate-800/60 transform origin-top-right scale-95 opacity-0 pointer-events-none transition-all duration-200 ease-out z-50">
                        <!-- Header -->
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800/60 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-slate-800 dark:text-white">Notifikasi</span>
                                <span id="unread-count-badge" class="text-[10px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded-full hidden">0</span>
                            </div>
                            <button id="clear-notifications-btn" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer border-0 bg-transparent">Hapus Semua</button>
                        </div>

                        <!-- Notification Items Container -->
                        <div id="notification-list" class="max-h-72 overflow-y-auto py-1 divide-y divide-slate-100 dark:divide-slate-800/60">
                            <!-- Polled notification history will render here -->
                        </div>

                        <!-- Footer -->
                        <div class="px-4 py-2.5 text-center border-t border-slate-100 dark:border-slate-800/60">
                            <span class="text-[9px] font-medium text-slate-400 dark:text-slate-500 tracking-wider uppercase">LeadHunter AI Notification Center</span>
                        </div>
                    </div>
                </div>

                <!-- Premium Glassmorphic User Profile Dropdown -->
                <div class="relative" id="user-menu-container">
                    <button id="user-menu-btn" class="flex items-center gap-2 focus:outline-none cursor-pointer group">
                        <div class="w-9 h-9 rounded-full bg-indigo-50 dark:bg-slate-800 flex items-center justify-center overflow-hidden shadow-sm border border-white dark:border-slate-700 shrink-0 transition group-hover:border-indigo-300">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=6366f1&color=fff&rounded=true" alt="User" class="w-full h-full object-cover">
                        </div>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 hidden md:block group-hover:text-slate-900 dark:group-hover:text-white transition leading-none">{{ Auth::user()->name }}</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition transform group-hover:translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <!-- User Dropdown Menu Content -->
                    <div id="user-menu-dropdown" class="absolute right-0 mt-3 w-56 rounded-2xl shadow-xl backdrop-blur-md bg-white/95 dark:bg-slate-900/95 border border-slate-200/60 dark:border-slate-800/60 transform origin-top-right scale-95 opacity-0 pointer-events-none transition-all duration-200 ease-out z-50 overflow-hidden">
                        <div class="px-4 py-3.5 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20">
                            <span class="text-xs font-black text-slate-800 dark:text-white block leading-tight">{{ Auth::user()->name }}</span>
                            <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 block mt-1 truncate">{{ Auth::user()->email }}</span>
                            
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach(Auth::user()->roles as $r)
                                    <span class="text-[8px] font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded-full uppercase tracking-wider">{{ $r->name }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="py-1">
                            @if(Auth::user()->is_admin)
                                <a href="{{ route('users.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <span>{{ __('Kelola Pengguna') }}</span>
                                </a>
                                <a href="{{ route('roles.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <span>{{ __('Kelola Hak Akses') }}</span>
                                </a>
                                <div class="border-t border-slate-100 dark:border-slate-800/60 my-1"></div>
                            @endif
                            <form action="{{ route('logout') }}" method="POST" class="w-full">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50/50 dark:hover:bg-rose-950/15 transition text-left cursor-pointer border-0 bg-transparent">
                                    <span>{{ __('Keluar') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white px-3.5 py-2 transition">{{ __('Masuk') }}</a>
                </div>
            @endauth
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="flex-1 w-full max-w-[1600px] mx-auto p-4 md:p-6 lg:p-8 pt-2">
        @yield('content')
    </main>

    {{-- Global SearchableCombobox JS Support --}}
    <script>
        (function () {
            const nav = document.getElementById('main-navbar');
            if (!nav) return;

            function updateNavScrolledState() {
                if (window.scrollY > 8) {
                    nav.classList.add('bg-white/85', 'dark:bg-slate-900/85', 'backdrop-blur-md', 'shadow-sm');
                } else {
                    nav.classList.remove('bg-white/85', 'dark:bg-slate-900/85', 'backdrop-blur-md', 'shadow-sm');
                }
            }

            updateNavScrolledState();
            window.addEventListener('scroll', updateNavScrolledState, { passive: true });
        })();

        class SearchableCombobox {
            constructor(container, placeholder, onSelect) {
                this.container = typeof container === 'string' ? document.getElementById(container) : container;
                if (!this.container) return;
                
                this.hiddenInput = this.container.querySelector('.combobox-hidden-input');
                this.trigger = this.container.querySelector('.combobox-trigger');
                this.label = this.container.querySelector('.combobox-label');
                this.dropdown = this.container.querySelector('.combobox-dropdown');
                this.search = this.container.querySelector('.combobox-search');
                this.optionsContainer = this.container.querySelector('.combobox-options');
                this.placeholder = placeholder || 'Select option';
                this.onSelect = onSelect;
                this.value = this.hiddenInput ? this.hiddenInput.value : '';
                
                // Expose instance on DOM element
                this.container.combobox = this;
                
                this.init();
            }
            
            init() {
                if (this.trigger) {
                    this.trigger.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.toggleDropdown();
                    });
                }
                
                document.addEventListener('click', (e) => {
                    if (!this.container.contains(e.target)) {
                        this.closeDropdown();
                    }
                });
                
                if (this.search) {
                    this.search.addEventListener('input', () => {
                        this.filterOptions();
                    });
                }
                
                this.setupOptions();
            }
            
            toggleDropdown() {
                document.querySelectorAll('.combobox-dropdown').forEach(d => {
                    if (d !== this.dropdown) d.classList.add('hidden');
                });
                
                if (this.dropdown) {
                    const isHidden = this.dropdown.classList.contains('hidden');
                    if (isHidden) {
                        this.dropdown.classList.remove('hidden');
                        if (this.search) {
                            this.search.focus();
                            this.search.value = '';
                        }
                        this.filterOptions();
                    } else {
                        this.closeDropdown();
                    }
                }
            }
            
            closeDropdown() {
                if (this.dropdown) {
                    this.dropdown.classList.add('hidden');
                }
            }
            
            filterOptions() {
                if (!this.search || !this.optionsContainer) return;
                const query = this.search.value.toLowerCase().trim();
                const options = this.optionsContainer.querySelectorAll('.combobox-option');
                
                options.forEach(opt => {
                    const text = opt.textContent.toLowerCase();
                    if (text.includes(query) || opt.getAttribute('data-value') === '') {
                        opt.classList.remove('hidden');
                    } else {
                        opt.classList.add('hidden');
                    }
                });
            }
            
            setupOptions() {
                if (!this.optionsContainer) return;
                const options = this.optionsContainer.querySelectorAll('.combobox-option');
                options.forEach(opt => {
                    opt.onclick = (e) => {
                        e.stopPropagation();
                        this.selectOption(opt);
                    };
                });
            }
            
            selectOption(optionEl) {
                const val = optionEl.getAttribute('data-value');
                const text = optionEl.textContent;
                
                this.value = val;
                if (this.label) {
                    this.label.textContent = text;
                }
                
                if (this.hiddenInput) {
                    const oldVal = this.hiddenInput.value;
                    this.hiddenInput.value = val;
                    
                    // Dispatch change event so form listeners or dynamic handlers catch it
                    if (oldVal !== val) {
                        this.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                
                if (this.optionsContainer) {
                    this.optionsContainer.querySelectorAll('.combobox-option').forEach(opt => {
                        opt.classList.remove('bg-indigo-500/10', 'text-indigo-600', 'dark:text-indigo-400');
                    });
                }
                
                optionEl.classList.add('bg-indigo-500/10', 'text-indigo-600', 'dark:text-indigo-400');
                
                this.closeDropdown();
                
                if (this.onSelect) {
                    this.onSelect(val);
                }
            }
            
            selectByValue(val) {
                if (!this.optionsContainer) return;
                const optionEl = this.optionsContainer.querySelector(`.combobox-option[data-value="${val}"]`);
                if (optionEl) {
                    this.selectOption(optionEl);
                } else {
                    this.value = val;
                    if (this.hiddenInput) {
                        this.hiddenInput.value = val;
                    }
                    if (this.label) {
                        this.label.textContent = val ? val : this.placeholder;
                    }
                }
            }
            
            addOption(value, text) {
                if (!this.optionsContainer) return;
                const opt = document.createElement('div');
                opt.className = "combobox-option px-2.5 py-1.5 text-xs rounded-lg hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition text-slate-700 dark:text-slate-300 font-medium";
                opt.setAttribute('data-value', value);
                opt.textContent = text;
                
                opt.onclick = (e) => {
                    e.stopPropagation();
                    this.selectOption(opt);
                };
                
                this.optionsContainer.appendChild(opt);
            }
        }

        // Auto-initialize standard custom comboboxes on load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.custom-combobox').forEach(el => {
                // Skip those explicitly initialized with custom page logic (like in campaigns/create)
                if (el.dataset.customInit === 'true' || el.id === 'combobox-niche' || el.id === 'combobox-city') return;
                new SearchableCombobox(el);
            });

            // Initialize Scraping Notifications Engine
            initScrapeNotifications();

            // Initialize User Profile Dropdown
            initUserProfileDropdown();
        });

        // Global Dynamic Floating Glassmorphic Toast Notification
        window.showToast = window.showToast || function(message, type = 'success') {
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
        };

        // Scraping Background Jobs Notifications Engine
        function initScrapeNotifications() {
            const bellBtn = document.getElementById('notification-bell-btn');
            const dropdown = document.getElementById('notification-dropdown');
            const clearBtn = document.getElementById('clear-notifications-btn');
            
            if (!bellBtn || !dropdown) return;
            
            let isFirstLoad = true;
            
            // Dropdown Toggle
            bellBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isClosed = dropdown.classList.contains('pointer-events-none');
                if (isClosed) {
                    openDropdown();
                } else {
                    closeDropdown();
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
                    closeDropdown();
                }
            });
            
            if (clearBtn) {
                clearBtn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    try {
                        const response = await fetch('/leads/scrape-status?action=clear');
                        if (response.ok) {
                            checkScrapeStatus();
                        }
                    } catch (err) {
                        console.error('Error clearing notifications:', err);
                    }
                });
            }
            
            async function openDropdown() {
                dropdown.classList.remove('pointer-events-none', 'scale-95', 'opacity-0');
                dropdown.classList.add('scale-100', 'opacity-100');
                
                // Mark all notifications as read in the database
                try {
                    const response = await fetch('/leads/scrape-status?action=mark-read');
                    if (response.ok) {
                        checkScrapeStatus();
                    }
                } catch (err) {
                    console.error('Error marking notifications as read:', err);
                }
            }
            
            function closeDropdown() {
                dropdown.classList.remove('scale-100', 'opacity-100');
                dropdown.classList.add('pointer-events-none', 'scale-95', 'opacity-0');
            }
            
            function timeAgo(dateString) {
                const date = new Date(dateString);
                const diff = Date.now() - date.getTime();
                const seconds = Math.floor(diff / 1000);
                const minutes = Math.floor(seconds / 60);
                const hours = Math.floor(minutes / 60);
                const days = Math.floor(hours / 24);
                
                if (seconds < 10) return 'Baru saja';
                if (seconds < 60) return `${seconds} detik lalu`;
                if (minutes < 60) return `${minutes} menit lalu`;
                if (hours < 24) return `${hours} jam lalu`;
                if (days === 1) return 'Kemarin';
                return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
            }
            
            function renderNotifications(notifications) {
                const listContainer = document.getElementById('notification-list');
                const badge = document.getElementById('notification-badge');
                const countBadge = document.getElementById('unread-count-badge');
                
                if (!listContainer) return;
                
                // Count unread
                const unreadCount = notifications.filter(n => !n.is_read).length;
                
                // Show/hide unread badge
                if (unreadCount > 0) {
                    badge.classList.remove('hidden');
                    countBadge.textContent = unreadCount;
                    countBadge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                    countBadge.classList.add('hidden');
                }
                
                if (notifications.length === 0) {
                    listContainer.innerHTML = `
                        <div class="px-4 py-8 text-center flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                            <svg class="w-10 h-10 mb-2 opacity-50 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="text-xs font-semibold">Tidak ada notifikasi</span>
                        </div>
                    `;
                    return;
                }
                
                listContainer.innerHTML = notifications.map(item => {
                    let iconBg = '';
                    let iconText = '';
                    let iconSvg = '';
                    
                    if (item.type === 'running') {
                        iconBg = 'bg-indigo-500/10 dark:bg-indigo-500/20';
                        iconText = 'text-indigo-600 dark:text-indigo-400';
                        iconSvg = `<svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
                    } else if (item.type === 'success') {
                        iconBg = 'bg-emerald-500/10 dark:bg-emerald-500/20';
                        iconText = 'text-emerald-600 dark:text-emerald-400';
                        iconSvg = `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
                    } else if (item.type === 'failed') {
                        iconBg = 'bg-rose-500/10 dark:bg-rose-500/20';
                        iconText = 'text-rose-600 dark:text-rose-400';
                        iconSvg = `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
                    }
                    
                    const isUnreadStyle = item.is_read ? '' : 'bg-indigo-50/30 dark:bg-indigo-950/10';
                    
                    return `
                        <div class="px-4 py-3 flex items-start gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors ${isUnreadStyle}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${iconBg} ${iconText}">
                                ${iconSvg}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">${item.title}</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-normal mt-0.5">${item.message}</p>
                                <span class="text-[9px] text-slate-400 dark:text-slate-500 block mt-1 font-medium">${timeAgo(item.created_at)}</span>
                            </div>
                        </div>
                    `;
                }).join('');
            }
            
            // Live status polling
            async function checkScrapeStatus() {
                try {
                    const response = await fetch("/leads/scrape-status");
                    if (!response.ok) return;
                    
                    const data = await response.json();
                    const dbNotifications = data.notifications || [];
                    
                    // Render current notification list
                    renderNotifications(dbNotifications);
                    
                    // Toast triggers based on seen ID
                    let lastSeenId = parseInt(localStorage.getItem('sandesa_last_seen_notification_id') || '0');
                    
                    // Find max ID in response
                    let maxId = 0;
                    dbNotifications.forEach(notif => {
                        if (notif.id > maxId) {
                            maxId = notif.id;
                        }
                    });
                    
                    if (isFirstLoad) {
                        // Seed last seen to avoid toast storms of past history
                        if (lastSeenId === 0 && maxId > 0) {
                            localStorage.setItem('sandesa_last_seen_notification_id', maxId);
                        }
                        isFirstLoad = false;
                        return;
                    }
                    
                    if (maxId > lastSeenId) {
                        // Gather new ones and trigger toasts in chronological order
                        const newNotifs = dbNotifications
                            .filter(notif => notif.id > lastSeenId)
                            .sort((a, b) => a.id - b.id);
                            
                        newNotifs.forEach(notif => {
                            window.showToast(notif.message, notif.type === 'failed' ? 'error' : 'success');
                            
                            // Realtime notification in page
                            if (notif.type === 'success' && window.location.pathname.endsWith('/leads')) {
                                window.showToast('Data leads baru tersedia! Muat ulang halaman ini untuk melihat daftar terbaru.', 'success');
                            }
                        });
                        
                        localStorage.setItem('sandesa_last_seen_notification_id', maxId);
                    }
                } catch (err) {
                    console.error('Error polling scraping status:', err);
                }
            }
            
            // Initial render and immediate poll
            checkScrapeStatus();
            
            // Poll every 5 seconds
            setInterval(checkScrapeStatus, 5000);
        }

        // User Profile Dropdown Menu Engine
        function initUserProfileDropdown() {
            const menuBtn = document.getElementById('user-menu-btn');
            const dropdown = document.getElementById('user-menu-dropdown');
            
            if (!menuBtn || !dropdown) return;
            
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isClosed = dropdown.classList.contains('pointer-events-none');
                if (isClosed) {
                    // Close notification dropdown if open
                    const notifyDropdown = document.getElementById('notification-dropdown');
                    if (notifyDropdown) {
                        notifyDropdown.classList.remove('scale-100', 'opacity-100');
                        notifyDropdown.classList.add('pointer-events-none', 'scale-95', 'opacity-0');
                    }
                    
                    dropdown.classList.remove('pointer-events-none', 'scale-95', 'opacity-0');
                    dropdown.classList.add('scale-100', 'opacity-100');
                } else {
                    closeDropdown();
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && !menuBtn.contains(e.target)) {
                    closeDropdown();
                }
            });
            
            function closeDropdown() {
                dropdown.classList.remove('scale-100', 'opacity-100');
                dropdown.classList.add('pointer-events-none', 'scale-95', 'opacity-0');
            }
        }
        // Global Confirm Modal Engine
        let globalConfirmPendingForm = null;
        let globalConfirmCallback = null;

        window.handleConfirm = function(event, form, title, message, btnText) {
            event.preventDefault();
            globalConfirmPendingForm = form;
            globalConfirmCallback = null;
            openGlobalConfirmModal(title, message, btnText);
            return false;
        };

        window.handleConfirmAction = function(title, message, btnText, callback) {
            globalConfirmPendingForm = null;
            globalConfirmCallback = callback;
            openGlobalConfirmModal(title, message, btnText);
        };

        function openGlobalConfirmModal(title, message, btnText) {
            document.getElementById('globalConfirmModalTitle').innerText = title || 'Are you sure?';
            document.getElementById('globalConfirmModalMessage').innerText = message || 'This action cannot be undone.';
            document.getElementById('globalConfirmModalBtn').innerText = btnText || 'Yes';
            
            const modal = document.getElementById('globalConfirmModal');
            const content = document.getElementById('globalConfirmModalContent');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            }, 10);
        }

        window.closeGlobalConfirmModal = function() {
            const modal = document.getElementById('globalConfirmModal');
            const content = document.getElementById('globalConfirmModalContent');
            
            modal.classList.add('opacity-0');
            content.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                globalConfirmPendingForm = null;
                globalConfirmCallback = null;
            }, 300);
        };

        document.addEventListener('DOMContentLoaded', () => {
            const confirmBtn = document.getElementById('globalConfirmModalBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    const form = globalConfirmPendingForm;
                    const cb = globalConfirmCallback;
                    
                    // Don't close modal immediately to show submitting state if needed, or close it.
                    // For forms it's fine to close immediately.
                    closeGlobalConfirmModal();
                    
                    if (form) {
                        form.submit();
                    } else if (cb) {
                        cb();
                    }
                });
            }
        });
    </script>

    <!-- Global Confirm Modal -->
    <div id="globalConfirmModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity opacity-0 duration-300">
        <div class="glass-card w-full max-w-sm p-6 relative overflow-hidden transform scale-95 transition-transform duration-300 text-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800" id="globalConfirmModalContent">
            <div class="w-16 h-16 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-500 text-3xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2" id="globalConfirmModalTitle">Are you sure?</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6" id="globalConfirmModalMessage">This action cannot be undone.</p>
            
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeGlobalConfirmModal()" class="btn-secondary px-6">Cancel</button>
                <button type="button" id="globalConfirmModalBtn" class="btn-primary shadow-lg shadow-indigo-500/25 px-6 font-bold">
                    Yes
                </button>
            </div>
        </div>
    </div>

    <!-- Google Translate Widget Container (hidden) -->
    <div id="google_translate_element" style="display:none"></div>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'id',
                includedLanguages: 'id,en',
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <!-- Language Toggle Button Init -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('locale-toggle-btn');
            const localeInput = document.getElementById('locale-hidden-input');
            const slider = document.getElementById('locale-slider');
            const flagId = document.getElementById('flag-id');
            const flagEn = document.getElementById('flag-en');
            
            if (!toggleBtn || !localeInput) return;
            
            toggleBtn.addEventListener('click', () => {
                const currentLocale = localeInput.value;
                const newLocale = currentLocale === 'id' ? 'en' : 'id';
                
                // Update hidden input
                localeInput.value = newLocale;
                
                // Animate slider position
                if (slider) {
                    if (newLocale === 'en') {
                        slider.style.left = 'calc(100% - 2.125rem - 2px)';
                    } else {
                        slider.style.left = '2px';
                    }
                }
                
                // Toggle active flag styles
                if (flagId && flagEn) {
                    if (newLocale === 'id') {
                        flagId.classList.add('scale-110', 'grayscale-0');
                        flagId.classList.remove('scale-90', 'grayscale', 'opacity-60');
                        flagEn.classList.add('scale-90', 'grayscale', 'opacity-60');
                        flagEn.classList.remove('scale-110', 'grayscale-0');
                    } else {
                        flagEn.classList.add('scale-110', 'grayscale-0');
                        flagEn.classList.remove('scale-90', 'grayscale', 'opacity-60');
                        flagId.classList.add('scale-90', 'grayscale', 'opacity-60');
                        flagId.classList.remove('scale-110', 'grayscale-0');
                    }
                }
                
                // Update tooltip
                toggleBtn.title = newLocale === 'id' ? 'Switch to English' : 'Ganti ke Bahasa Indonesia';
                
                // Set googtrans cookie for Google Translate
                const cookieVal = newLocale === 'en' ? '/id/en' : '/id/id';
                document.cookie = "googtrans=" + cookieVal + "; path=/";
                document.cookie = "googtrans=" + cookieVal + "; path=/; domain=" + window.location.hostname;
                document.cookie = "googtrans=" + cookieVal + "; path=/; domain=." + window.location.hostname;
                
                // Short delay for visual animation before page reloads
                setTimeout(() => {
                    localeInput.form.submit();
                }, 250);
            });
        });
    </script>
</body>
</html>
