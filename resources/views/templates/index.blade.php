@extends('layouts.app')

@section('title', 'Template Outreach - Sandesa')
@section('header', 'Template Pesan')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    {{-- Header Intro --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm" style="animation: fadeInUp 0.4s ease backwards;">
        <div class="flex items-center gap-3">
            <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Pustaka Template Outreach</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Buat, ubah, dan kelola template siap pakai yang dikelompokkan per niche dan channel outreach.</p>
            </div>
        </div>
        <a href="{{ route('templates.create') }}" class="btn-primary py-2.5 px-5 text-xs font-bold shadow-lg shadow-indigo-500/25 rounded-xl flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Buat Template</span>
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success shadow-lg shadow-emerald-500/10" style="animation: fadeInUp 0.4s ease backwards;">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Filter Panel --}}
    <div class="glass-card p-5 border border-slate-100 dark:border-slate-800/60" style="animation: fadeInUp 0.4s ease backwards; animation-delay: 0.05s;">
        <form id="templates-filter-form" action="{{ route('templates.index') }}" method="GET" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                <div class="relative md:col-span-2">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input id="niche-filter-input" type="text" name="niche" placeholder="Cari nama template, niche, channel, nada, bahasa, subjek..." value="{{ request('niche') }}" class="w-full pl-8 pr-3 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg outline-none focus:border-indigo-500 transition text-slate-700 dark:text-slate-200">
                </div>
                <input type="hidden" name="channel" id="channel-filter" value="{{ request('channel', '') }}">
                <div class="grid grid-cols-3 gap-1">
                    <button type="button" class="channel-filter-btn px-2 py-2 text-[10px] font-bold rounded-lg border transition {{ request('channel', '') === '' ? 'bg-indigo-500/10 text-indigo-600 border-indigo-500/30' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400' }}" data-value="">Semua</button>
                    <button type="button" class="channel-filter-btn px-2 py-2 text-[10px] font-bold rounded-lg border transition {{ request('channel') === 'email' ? 'bg-indigo-500/10 text-indigo-600 border-indigo-500/30' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400' }}" data-value="email">Email</button>
                    <button type="button" class="channel-filter-btn px-2 py-2 text-[10px] font-bold rounded-lg border transition {{ request('channel') === 'whatsapp' ? 'bg-indigo-500/10 text-indigo-600 border-indigo-500/30' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400' }}" data-value="whatsapp">WhatsApp</button>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" id="reset-template-filters" class="btn-secondary py-2 px-4 text-xs font-bold rounded-xl">Reset</button>
            </div>
        </form>
    </div>

    <div id="templates-table-container">
        @include('templates.partials.table', ['templates' => $templates])
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm = document.getElementById('templates-filter-form');
        const nicheInput = document.getElementById('niche-filter-input');
        const channelFilter = document.getElementById('channel-filter');
        const filterButtons = document.querySelectorAll('.channel-filter-btn');
        const resetBtn = document.getElementById('reset-template-filters');
        const tableContainer = document.getElementById('templates-table-container');
        let nicheDebounce;

        const updateChannelButtons = (activeValue) => {
            filterButtons.forEach(btn => {
                const isActive = (btn.getAttribute('data-value') || '') === (activeValue || '');
                btn.classList.toggle('bg-indigo-500/10', isActive);
                btn.classList.toggle('text-indigo-600', isActive);
                btn.classList.toggle('border-indigo-500/30', isActive);
                btn.classList.toggle('bg-white', !isActive);
                btn.classList.toggle('dark:bg-slate-900', !isActive);
                btn.classList.toggle('border-slate-200', !isActive);
                btn.classList.toggle('dark:border-slate-700', !isActive);
                btn.classList.toggle('text-slate-500', !isActive);
                btn.classList.toggle('dark:text-slate-400', !isActive);
            });
        };

        const fetchTemplates = async (pageUrl = null) => {
            const url = new URL(pageUrl || filterForm.action, window.location.origin);
            if (!pageUrl) {
                url.searchParams.set('niche', nicheInput.value.trim());
                url.searchParams.set('channel', channelFilter.value || '');
            }
            url.searchParams.set('ajax', '1');

            try {
                tableContainer.classList.add('opacity-60');
                const response = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!response.ok) return;

                const data = await response.json();
                if (data.status === 'success') {
                    tableContainer.innerHTML = data.html;
                    const cleanUrl = new URL(url.toString());
                    cleanUrl.searchParams.delete('ajax');
                    window.history.replaceState({}, '', cleanUrl.toString());
                }
            } catch (err) {
                console.error('Failed to fetch templates:', err);
                window.showToast('Gagal memuat template.', 'error');
            } finally {
                tableContainer.classList.remove('opacity-60');
            }
        };

        if (filterForm) {
            filterForm.addEventListener('submit', (e) => e.preventDefault());
        }

        if (nicheInput) {
            nicheInput.addEventListener('input', () => {
                clearTimeout(nicheDebounce);
                nicheDebounce = setTimeout(() => fetchTemplates(), 300);
            });
        }

        filterButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                if (!channelFilter) return;
                channelFilter.value = this.getAttribute('data-value') || '';
                updateChannelButtons(channelFilter.value);
                fetchTemplates();
            });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                nicheInput.value = '';
                channelFilter.value = '';
                updateChannelButtons('');
                fetchTemplates();
            });
        }

        document.addEventListener('click', (e) => {
            const paginationLink = e.target.closest('#templates-table-container a[href*="page="]');
            if (!paginationLink) return;
            e.preventDefault();
            fetchTemplates(paginationLink.href);
        });
    });

    async function toggleTemplateActive(id, checkbox) {
        const label = checkbox.closest('label').querySelector('.status-label');
        const oldState = checkbox.checked;
        
        // Optimistic UI updates
        label.textContent = checkbox.checked ? 'Aktif' : 'Nonaktif';

        try {
            const response = await fetch(`/templates/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const result = await response.json();
            
            if (response.ok && result.status === 'success') {
                checkbox.checked = result.is_active;
                label.textContent = result.is_active ? 'Aktif' : 'Nonaktif';
                window.showToast('Status template berhasil diperbarui.', 'success');
            } else {
                throw new Error('Failed');
            }
        } catch (e) {
            checkbox.checked = !oldState;
            label.textContent = !oldState ? 'Aktif' : 'Nonaktif';
            window.showToast('Gagal mengubah status template.', 'error');
        }
    }
</script>
@endsection


