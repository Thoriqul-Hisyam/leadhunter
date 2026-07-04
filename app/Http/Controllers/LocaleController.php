<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => 'required|in:id,en',
        ]);

        $locale = $validated['locale'];

        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        } else {
            $request->session()->put('locale', $locale);
        }

        // Set googtrans cookie for Google Translate (forces dynamic translation)
        if ($locale === 'en') {
            setrawcookie('googtrans', '/id/en', time() + 3600 * 24 * 30, '/');
            if (isset($_SERVER['HTTP_HOST'])) {
                // Strip port from HTTP_HOST if present (e.g. 127.0.0.1:8000 -> 127.0.0.1)
                $host = explode(':', $_SERVER['HTTP_HOST'])[0];
                setrawcookie('googtrans', '/id/en', time() + 3600 * 24 * 30, '/', $host);
                $hostParts = explode('.', $host);
                if (count($hostParts) > 1) {
                    setrawcookie('googtrans', '/id/en', time() + 3600 * 24 * 30, '/', '.' . $host);
                }
            }
        } else {
            // Reset to default (Indonesian)
            setrawcookie('googtrans', '/id/id', time() + 3600 * 24 * 30, '/');
            if (isset($_SERVER['HTTP_HOST'])) {
                $host = explode(':', $_SERVER['HTTP_HOST'])[0];
                setrawcookie('googtrans', '/id/id', time() + 3600 * 24 * 30, '/', $host);
                $hostParts = explode('.', $host);
                if (count($hostParts) > 1) {
                    setrawcookie('googtrans', '/id/id', time() + 3600 * 24 * 30, '/', '.' . $host);
                }
            }
        }

        return redirect()->back();
    }
}

