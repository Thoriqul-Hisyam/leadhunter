<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // One-click unsubscribe (RFC 8058) dikirim oleh server email tanpa token CSRF; URL-nya sudah bertanda tangan.
        // Webhook gateway WhatsApp juga dipanggil dari server luar; diamankan dengan token di URL.
        $middleware->validateCsrfTokens(except: ['unsubscribe/*', 'webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
