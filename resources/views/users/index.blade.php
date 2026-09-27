@extends('layouts.app')

@section('title', 'Kelola User — Sandesa')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                <span>Kelola Pengguna</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Manajemen akun pengguna dan pembagian wewenang peran (RBAC) dalam sistem.</p>
        </div>
        
        <div>
            <a href="{{ route('users.create') }}" class="btn-primary flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/20 py-2.5 px-5 rounded-xl font-bold text-sm text-white transform hover:-translate-y-0.5 active:translate-y-0 transition cursor-pointer">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                <span>Tambah User Baru</span>
            </a>
        </div>
    </div>

    {{-- Session Flash Alerts --}}
    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Main Glass Card Container --}}
    <div class="dash-card bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
        
        {{-- Table Filters / Search --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <form action="{{ route('users.index') }}" method="GET" class="w-full sm:max-w-xs relative flex items-center gap-2">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..." 
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-xl text-xs outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition font-medium">
                
                @if(request('search'))
                    <a href="{{ route('users.index') }}" class="absolute right-3 text-slate-400 hover:text-slate-600" aria-label="Hapus pencarian"><x-icon name="x-mark" class="w-3.5 h-3.5" /></a>
                @endif
            </form>

            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                Total: {{ $users->total() }} Pengguna
            </div>
        </div>

        {{-- Users Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800/60">
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Pengguna</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Alamat Email</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Peran (Role)</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            {{-- User Avatar + Name --}}
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm shrink-0">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=6366f1&color=fff&rounded=true" alt="Avatar" class="w-full h-full object-cover">
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-xs font-bold text-slate-800 dark:text-white block leading-tight">{{ $user->name }}</span>
                                        @if($user->id === auth()->id())
                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded-full mt-1">Akun Anda</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ $user->email }}</span>
                            </td>

                            {{-- Role Badges --}}
                            <td class="py-4 px-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full {{ $role->slug === 'admin' ? 'bg-purple-500/10 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400 border border-purple-500/20' : 'bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400 border border-indigo-500/20' }}">
                                            <span>{{ $role->name }}</span>
                                        </span>
                                    @empty
                                        <span class="inline-flex text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 px-2 py-0.5 rounded-full">No Role</span>
                                    @endforelse
                                </div>
                            </td>

                            {{-- Action buttons --}}
                            <td class="py-4 px-4 whitespace-nowrap text-right text-xs font-semibold">
                                <div class="flex items-center justify-end gap-2.5">
                                    {{-- Edit link --}}
                                    <a href="{{ route('users.edit', $user->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                        <span>Edit</span>
                                    </a>

                                    @if($user->id !== auth()->id())
                                        {{-- Delete form --}}
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return handleConfirm(event, this.form, 'Hapus User', 'Apakah Anda yakin ingin menghapus user {{ $user->name }} dari sistem? Tindakan ini tidak dapat dibatalkan.', 'Hapus User')"
                                                class="text-rose-600 dark:text-rose-400 hover:underline cursor-pointer border-0 bg-transparent flex items-center gap-1 font-semibold">
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 px-4 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <span class="mb-2 text-slate-300 dark:text-slate-600"><x-icon name="search" class="w-8 h-8" /></span>
                                    <span class="text-xs font-semibold">Tidak ada pengguna yang cocok</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="mt-6 border-t border-slate-100 dark:border-slate-800/60 pt-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
