<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $defaultLocale = config('app.locale', 'id');
        $supported = ['id', 'en'];

        $locale = $defaultLocale;

        if (Auth::check()) {
            $userLocale = Auth::user()->locale ?? null;
            if (in_array($userLocale, $supported, true)) {
                $locale = $userLocale;
            }
        } else {
            $sessionLocale = $request->session()->get('locale');
            if (in_array($sessionLocale, $supported, true)) {
                $locale = $sessionLocale;
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}

