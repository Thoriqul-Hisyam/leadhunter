@extends('layouts.app')

@section('title', 'Antrean — Sandesa')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Antrean & Worker</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Scraping, generate AI, pengiriman, follow-up, dan cek balasan berjalan di background. Halaman ini menunjukkan apakah semuanya berjalan.</p>
    </div>

    @if(session('success'))
        <div class="alert-success"><span>{{ session('success') }}</span></div>
    @endif

    {{-- Status --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($health as $component)
            <div class="dash-card py-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full {{ $component['healthy'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $component['label'] }}</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                    @if($component['busy'])
                        Sedang menjalankan job panjang.
                    @elseif($component['last_seen'])
                        Terakhir aktif {{ $component['last_seen']->diffForHumans() }}.
                    @else
                        Belum pernah terlihat berjalan.
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    @php $down = collect($health)->reject(fn ($c) => $c['healthy']); @endphp
    @if($down->isNotEmpty())
        <div class="dash-card border border-amber-500/30">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Cara menjalankan</h3>
            @if(app()->isLocal())
                <p class="text-xs text-slate-600 dark:text-slate-300">Jalankan <code>composer run dev</code> (server, dua queue worker, scheduler, dan Vite sekaligus).</p>
            @else
                <p class="text-xs text-slate-600 dark:text-slate-300 mb-3">Di server setiap proses berjalan sendiri. Nyalakan yang mati:</p>
                <ul class="space-y-2.5">
                    @foreach($down as $name => $component)
                        @php $hint = \App\Services\SystemHealth::runHint($name); @endphp
                        <li class="text-xs text-slate-600 dark:text-slate-300">
                            <strong class="text-slate-800 dark:text-slate-100">{{ $component['label'] }}</strong> lewat {{ $hint['via'] }}:
                            <code class="block mt-1 p-2 rounded-lg bg-slate-100 dark:bg-slate-800 break-all select-all">{{ $hint['command'] }}</code>
                        </li>
                    @endforeach
                </ul>
                @if($down->has('scheduler') && $down->count() > 1)
                    <p class="text-[11px] text-slate-500 mt-3">Job detak untuk worker dikirim oleh scheduler, jadi selama cron mati worker yang menganggur bisa ikut tampak mati. Nyalakan cron dulu, lalu cek lagi setelah 1–2 menit.</p>
                @endif
                <p class="text-[11px] text-slate-500 mt-3">Supervisor: cek dengan <code>sudo supervisorctl status</code>. Setelah deploy kode baru: <code>php artisan queue:restart</code>. Contoh konfigurasi lengkap ada di <code>docs/deployment.md</code>.</p>
            @endif
        </div>
    @endif

    {{-- Pending --}}
    <div class="glass-card p-6">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Job menunggu</h3>
        <div class="overflow-x-auto">
            <table class="fancy-table min-w-full">
                <thead>
                    <tr><th>Antrean</th><th class="text-right">Menunggu</th><th class="text-right">Sedang berjalan</th><th>Tertua</th></tr>
                </thead>
                <tbody>
                    @forelse($pending as $row)
                        <tr>
                            <td class="text-xs font-semibold">{{ $row->queue }}</td>
                            <td class="text-xs text-right">{{ $row->total - $row->running }}</td>
                            <td class="text-xs text-right">{{ $row->running }}</td>
                            <td class="text-xs text-slate-500">{{ $row->oldest ? \Illuminate\Support\Carbon::createFromTimestamp($row->oldest)->setTimezone(config('app.timezone'))->diffForHumans() : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-xs text-slate-400 text-center py-6">Tidak ada job yang menunggu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Failed --}}
    <div class="glass-card p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Job gagal ({{ $failed->total() }})</h3>
            @if($failed->total() > 0)
                <div class="flex gap-2">
                    <form action="{{ route('queue.retry-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-secondary py-1.5 px-3 text-xs font-bold"><x-icon name="reply" class="w-3.5 h-3.5" /> Coba lagi semua</button>
                    </form>
                    <form action="{{ route('queue.flush') }}" method="POST" onsubmit="return handleConfirm(event, this, 'Hapus semua job gagal?', 'Riwayat error akan dihapus dan job tidak bisa dicoba lagi.', 'Ya, hapus')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-secondary py-1.5 px-3 text-xs font-bold text-rose-600"><x-icon name="x-mark" class="w-3.5 h-3.5" /> Hapus semua</button>
                    </form>
                </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="fancy-table min-w-full">
                <thead>
                    <tr><th>Job</th><th style="width: 50%">Penyebab gagal</th><th>Waktu</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($failed as $job)
                        <tr>
                            <td class="text-xs"><div class="font-semibold">{{ $job->name }}</div><div class="text-[10px] text-slate-400">{{ $job->queue }}</div></td>
                            <td class="text-[11px] text-rose-600 dark:text-rose-400 break-words">{{ $job->error }}</td>
                            <td class="text-[11px] text-slate-500 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($job->failed_at)->translatedFormat('d M H:i') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <form action="{{ route('queue.retry', $job->uuid) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Coba lagi</button>
                                </form>
                                <form action="{{ route('queue.forget', $job->uuid) }}" method="POST" class="inline ml-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-semibold text-slate-500 hover:text-rose-500">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-xs text-slate-400 text-center py-6">Tidak ada job gagal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $failed->links() }}</div>
    </div>
</div>
@endsection
