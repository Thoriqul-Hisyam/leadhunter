@extends('layouts.guest')

@section('title', 'Lupa Password — Sandesa')

@section('content')
    <div class="mb-6">
        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Lupa password?</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Masukkan email akun Anda. Kami kirimkan link untuk membuat password baru.</p>
    </div>

    <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus placeholder="nama@email.com" class="guest-input">
            @error('email')
                <p class="text-[11px] font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full py-3 px-4 bg-linear-to-r from-indigo-500 to-purple-600 text-white font-bold text-sm rounded-2xl shadow-lg shadow-indigo-500/20 cursor-pointer">
            Kirim link reset
        </button>
    </form>

    <p class="text-center text-xs text-slate-500 mt-6">
        <a href="{{ route('login') }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Kembali ke halaman masuk</a>
    </p>
@endsection
