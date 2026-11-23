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
        // The one-click launcher publishes the app through a Cloudflare tunnel, which connects from this
        // machine and sends X-Forwarded-Proto/Host. Trust only the loopback proxy so links and assets are
        // generated as https://<tunnel host> instead of http://localhost.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        // UI first: screens over KAMMA's own tables render from an in-memory preview schema until the
        // migrations are run (App\Http\Middleware\PreviewWhenNotMigrated).
        $middleware->web(prepend: [\App\Http\Middleware\ResetPreviewState::class]);
        $middleware->alias([
            'kamma.preview' => \App\Http\Middleware\PreviewWhenNotMigrated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
