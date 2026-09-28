@extends('layouts.guest')

@section('title', 'Buat Password Baru — Sandesa')

@section('content')
    <div class="mb-6">
        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Buat password baru</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Minimal 8 karakter.</p>
    </div>

    <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required class="guest-input">
            @error('email')
                <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Password baru</label>
            <input type="password" name="password" id="password" required autocomplete="new-password" class="guest-input">
            @error('password')
                <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Ulangi password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" class="guest-input">
        </div>

        <button type="submit" class="w-full py-3 px-4 bg-linear-to-r from-indigo-500 to-purple-600 text-white font-bold text-sm rounded-2xl shadow-lg shadow-indigo-500/20 cursor-pointer">
            Simpan password
        </button>
    </form>
@endsection
