@extends('layouts.app')

@section('title', 'Edit Role — Sandesa')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Header --}}
    <div>
        <a href="{{ route('roles.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white transition flex items-center gap-1.5 mb-2 group">
            <svg class="w-3.5 h-3.5 transform group-hover:-translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            <span>Kembali ke Daftar Role</span>
        </a>
        <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
            <span>Edit Role</span>
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Ubah level hak akses dan deskripsi peran untuk role {{ $role->name }}.</p>
    </div>

    {{-- Main Glass Card Form --}}
    <div class="dash-card bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
        
        <form action="{{ route('roles.update', $role->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Role Name --}}
            <div>
                <label for="name" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Nama Peran (Role Name)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </span>
                    <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" required
                        placeholder="Contoh: Manager" 
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition font-medium">
                </div>
                @error('name')
                    <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Role Slug --}}
            <div>
                <label for="slug" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Slug Sistem </label>
                
                @if(in_array($role->slug, ['admin', 'user']))
                    <span class="block text-[10px] text-amber-600 dark:text-amber-500 mb-2 font-medium flex items-center gap-1">
                        <span>⚠️</span>
                        <span>Role bawaan sistem terproteksi. Slug sistem tidak dapat diubah agar tidak merusak otorisasi program.</span>
                    </span>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input type="text" value="{{ $role->slug }}" disabled 
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-100 dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl text-sm font-semibold text-slate-400 dark:text-slate-500 select-none cursor-not-allowed">
                        <input type="hidden" name="slug" value="{{ $role->slug }}">
                    </div>
                @else
                    <span class="block text-[10px] text-slate-400 dark:text-slate-500 mb-2 font-medium">Identifier unik berbentuk huruf kecil yang digunakan secara terprogram di dalam kode middleware (misal: `manager`).</span>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m-5 7a2 2 0 01-2-2m-2 2a2 2 0 012-2m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <input type="text" name="slug" id="slug" value="{{ old('slug', $role->slug) }}" required
                            placeholder="Contoh: manager" 
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition font-medium">
                    </div>
                @endif
                @error('slug')
                    <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Deskripsi Role</label>
                <div class="relative">
                    <textarea name="description" id="description" rows="3" 
                        placeholder="Jelaskan wewenang atau hak akses dari peran ini dalam sistem..."
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition font-medium resize-none">{{ old('description', $role->description) }}</textarea>
                </div>
                @error('description')
                    <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Assign Permissions --}}
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-3">Hak Akses (Permissions)</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($permissions as $permission)
                        <label class="flex items-start p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-850 rounded-2xl hover:border-indigo-500/60 dark:hover:border-indigo-500/60 transition cursor-pointer select-none relative group">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                    {{ in_array($permission->id, old('permissions', $rolePermissionIds)) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-350 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer">
                            </div>
                            <div class="ml-3 text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-300 block mb-0.5">{{ $permission->name }}</span>
                                <span class="text-slate-500 dark:text-slate-400 leading-normal">{{ $permission->description }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('permissions')
                    <p class="text-[11px] font-semibold text-rose-500 mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit and Cancel buttons --}}
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100 dark:border-slate-800/60">
                <a href="{{ route('roles.index') }}" class="btn-secondary px-6 py-2.5 text-sm font-semibold rounded-xl text-slate-600 hover:text-slate-800 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white transition">
                    Batal
                </a>
                <button type="submit" 
                    class="btn-primary bg-gradient-to-r from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/20 py-2.5 px-6 rounded-xl font-bold text-sm text-white transform hover:-translate-y-0.5 active:translate-y-0 transition cursor-pointer">
                    Perbarui Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
