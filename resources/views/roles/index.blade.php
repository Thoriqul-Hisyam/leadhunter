@extends('layouts.app')

@section('title', 'Kelola Role — Sandesa')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                <span>Kelola Hak Akses (Role)</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Buat dan atur tingkatan otorisasi serta deskripsi peran pengguna dalam sistem.</p>
        </div>
        
        <div>
            <a href="{{ route('roles.create') }}" class="btn-primary flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/20 py-2.5 px-5 rounded-xl font-bold text-sm text-white transform hover:-translate-y-0.5 active:translate-y-0 transition cursor-pointer">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                <span>Buat Role Baru</span>
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
        <div class="mb-4 text-xs font-bold text-slate-405 uppercase tracking-wider">
            Daftar Peran Otorisasi
        </div>

        {{-- Roles Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800/60">
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Peran (Role)</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Slug Sistem</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Deskripsi Peran</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Aktif Pengguna</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($roles as $role)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            {{-- Role Name --}}
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-800 dark:text-white">{{ $role->name }}</span>
                                    @if(in_array($role->slug, ['admin', 'user']))
                                        <span class="inline-flex text-[9px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded-full border border-indigo-500/20 shrink-0">Sistem</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Slug --}}
                            <td class="py-4 px-4 whitespace-nowrap">
                                <code class="text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-1 rounded-lg">{{ $role->slug }}</code>
                            </td>

                            {{-- Description & Permissions --}}
                            <td class="py-4 px-4">
                                <div class="space-y-1.5">
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 line-clamp-1 leading-normal max-w-sm">{{ $role->description ?: 'Tidak ada deskripsi.' }}</span>
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($role->permissions as $perm)
                                            <span class="inline-flex text-[9px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded-full border border-indigo-500/20" title="{{ $perm->description }}">
                                                {{ $perm->name }}
                                            </span>
                                        @empty
                                            <span class="inline-flex text-[9px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 px-1.5 py-0.5 rounded-full">
                                                Belum ada hak akses
                                            </span>
                                        @endforelse
                                    </div>
                                </div>
                            </td>

                            {{-- Active User Count --}}
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-0.5 rounded-full">
                                    {{ $role->users_count }} User
                                </span>
                            </td>

                            {{-- Action buttons --}}
                            <td class="py-4 px-4 whitespace-nowrap text-right text-xs font-semibold">
                                <div class="flex items-center justify-end gap-2.5">
                                    {{-- Edit link --}}
                                    <a href="{{ route('roles.edit', $role->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                        <span>Edit</span>
                                    </a>

                                    @if(!in_array($role->slug, ['admin', 'user']))
                                        {{-- Delete form --}}
                                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return handleConfirm(event, this.form, 'Hapus Role 🛡️', 'Apakah Anda yakin ingin menghapus role {{ $role->name }} dari sistem? Ini akan melepaskan hak akses dari semua user terkait.', 'Hapus Role')"
                                                class="text-rose-600 dark:text-rose-400 hover:underline cursor-pointer border-0 bg-transparent flex items-center gap-1 font-semibold">
                                                <span>Hapus</span
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-600 cursor-not-allowed select-none flex items-center gap-1" title="Role bawaan sistem terlindungi.">
                                            <span>Terkunci</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 px-4 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <span class="text-3xl mb-2">🔍</span>
                                    <span class="text-xs font-semibold">Tidak ada role terdaftar</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
