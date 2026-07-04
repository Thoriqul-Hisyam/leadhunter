<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi ✉️',
            'email.email' => 'Format email tidak valid ✉️',
            'password.required' => 'Password wajib diisi 🔑',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Prevent redirecting to background/AJAX endpoints on initial login
            $intended = redirect()->getIntendedUrl();
            if ($intended && (
                str_contains($intended, 'scrape-status') || 
                str_contains($intended, 'filter') || 
                str_contains($intended, 'compose') || 
                str_contains($intended, 'toggle')
            )) {
                $request->session()->forget('url.intended');
                return redirect()->route('dashboard')
                    ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '! ✨');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '! ✨');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah ❌',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar. Sampai jumpa kembali! 👋');
    }
}
