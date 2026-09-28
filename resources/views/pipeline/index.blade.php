@extends('layouts.app')

@section('title', 'Pipeline — Sandesa')

@section('content')
<div class="space-y-5">
    <div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Pipeline Lead</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Stage "Dihubungi" dan "Membalas" terisi otomatis saat pesan terkirim atau dibalas; stage lain diatur manual.</p>
    </div>

    {{-- Pencarian: hasil diperbarui langsung saat mengetik --}}
    <form method="GET" action="{{ route('pipeline.index') }}" id="pipeline-search" class="flex flex-col md:flex-row gap-2" role="search">
        <div class="relative flex-1 md:max-w-sm">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <x-icon name="search" class="w-4 h-4" />
            </span>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" autocomplete="off"
                   placeholder="Cari nama bisnis, email, telepon, kota..." aria-label="Cari lead"
                   class="form-input text-xs pl-9 w-full">
        </div>
        <select name="niche" class="form-input text-xs md:w-48" aria-label="Filter niche">
            <option value="">Semua niche</option>
            @foreach($niches as $niche)
                <option value="{{ $niche }}" @selected(($filters['niche'] ?? '') === $niche)>{{ $niche }}</option>
            @endforeach
        </select>
        <select name="city" class="form-input text-xs md:w-44" aria-label="Filter kota">
            <option value="">Semua kota</option>
            @foreach($cities as $city)
                <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
            @endforeach
        </select>
        <a href="{{ route('pipeline.index') }}" id="pipeline-reset" class="btn-secondary py-1.5 px-4 text-xs justify-center {{ array_filter($filters) ? '' : 'hidden' }}">Reset</a>
        <noscript><button type="submit" class="btn-secondary py-1.5 px-4 text-xs">Cari</button></noscript>
    </form>

    @if(session('success'))
        <div class="alert-success"><span>{{ session('success') }}</span></div>
    @endif

    <div id="pipeline-board" aria-live="polite">
        @include('pipeline.partials.board')
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('pipeline-search');
    const board = document.getElementById('pipeline-board');
    const reset = document.getElementById('pipeline-reset');
    let timer = null;
    let controller = null;

    const refresh = async () => {
        const params = new URLSearchParams(new FormData(form));
        for (const [key, value] of [...params.entries()]) {
            if (!value) params.delete(key);
        }

        // URL ikut berubah supaya hasil pencarian bisa di-refresh atau dibagikan
        history.replaceState(null, '', params.toString() ? `${form.action}?${params}` : form.action);
        reset.classList.toggle('hidden', !params.toString());

        controller?.abort();
        controller = new AbortController();
        params.set('partial', '1');
        board.classList.add('opacity-60');

        try {
            const response = await fetch(`${form.action}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (response.ok) {
                board.innerHTML = await response.text();
            }
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        } finally {
            board.classList.remove('opacity-60');
        }
    };

    form.addEventListener('input', (e) => {
        if (e.target.name !== 'search') return;
        clearTimeout(timer);
        timer = setTimeout(refresh, 300);
    });
    form.addEventListener('change', (e) => {
        if (e.target.tagName === 'SELECT') refresh();
    });
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        refresh();
    });
});
</script>
@endsection
