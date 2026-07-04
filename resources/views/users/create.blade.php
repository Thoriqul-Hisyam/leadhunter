@extends('layouts.app')

@section('title', 'Tambah User — Sandesa')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Header --}}
    <div>
        <a href="{{ route('users.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white transition flex items-center gap-1.5 mb-2 group">
            <svg class="w-3.5 h-3.5 transform group-hover:-translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            <span>Kembali ke Daftar User</span>
        </a>
        <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
            <span>Tambah User Baru</span>
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Buat akun pengguna baru dan tentukan hak akses peran dalam sistem.</p>
    </div>

    {{-- Main Glass Card Form --}}
    <div class="dash-card bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-8 shadow-sm">
        
        <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </span>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                        placeholder="Nama Lengkap User" 
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition font-medium">
                </div>
                @error('name')
                    <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Alamat Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </span>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        placeholder="email@leadhunter.com" 
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition font-medium">
                </div>
                @error('email')
                    <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </span>
                    <input type="password" name="password" id="password" required
                        placeholder="Minimal 8 karakter" 
                        class="w-full pl-10 pr-11 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition font-medium">
                    <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-indigo-500 dark:text-slate-500 dark:hover:text-indigo-400 transition cursor-pointer" tabindex="-1">
                        <svg class="w-4 h-4 eye-show" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        <svg class="w-4 h-4 eye-hide hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    </button>
                </div>
                @error('password')
                    <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Assign Roles --}}
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-3">Roles</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($roles as $role)
                        <label class="flex items-start p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-850 rounded-2xl hover:border-indigo-500/60 dark:hover:border-indigo-500/60 transition cursor-pointer select-none relative group">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                                    {{ in_array($role->id, old('roles', [])) || ($role->slug === 'user' && !old('roles')) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-350 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer">
                            </div>
                            <div class="ml-3 text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-300 block mb-0.5">{{ $role->name }}</span>
                                <span class="text-slate-500 dark:text-slate-400 leading-normal">{{ $role->description }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('roles')
                    <p class="text-[11px] font-semibold text-rose-500 mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit and Cancel buttons --}}
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100 dark:border-slate-800/60">
                <a href="{{ route('users.index') }}" class="btn-secondary px-6 py-2.5 text-sm font-semibold rounded-xl text-slate-600 hover:text-slate-800 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white transition">
                    Batal
                </a>
                <button type="submit" 
                    class="btn-primary bg-gradient-to-r from-indigo-500 to-purple-600 shadow-lg shadow-indigo-500/20 py-2.5 px-6 rounded-xl font-bold text-sm text-white transform hover:-translate-y-0.5 active:translate-y-0 transition cursor-pointer">
                    Simpan User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const eyeShow = btn.querySelector('.eye-show');
        const eyeHide = btn.querySelector('.eye-hide');
        if (input.type === 'password') {
            input.type = 'text';
            eyeShow.classList.add('hidden');
            eyeHide.classList.remove('hidden');
        } else {
            input.type = 'password';
            eyeShow.classList.remove('hidden');
            eyeHide.classList.add('hidden');
        }
    }
</script>
@endsection
