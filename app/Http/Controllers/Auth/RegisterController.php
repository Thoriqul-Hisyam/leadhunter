<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    /**
     * Handle a registration request.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max-255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi 👤',
            'email.required' => 'Email wajib diisi ✉️',
            'email.email' => 'Format email tidak valid ✉️',
            'email.unique' => 'Email ini sudah terdaftar, silakan gunakan email lain atau masuk ✉️',
            'password.required' => 'Password wajib diisi 🔑',
            'password.confirmed' => 'Konfirmasi password tidak cocok 🔑',
            'password.min' => 'Password minimal terdiri dari 8 karakter 🔑',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Auto-assign default 'user' role
        $user->assignRole('user');

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Registrasi berhasil! Selamat datang di LeadHunter AI, ' . $user->name . '! ✨');
    }
}
