<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    // API-only backend: the React frontend is a separate static site, so there are no web routes.
    // Health checks use GET /api/health, which also checks the database.
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // On Render, HTTPS ends at Render's proxy, which forwards plain HTTP to the container and
        // describes the original request in X-Forwarded-* headers. Trusting them lets Laravel see the
        // real scheme (https) and client IP. Safe because the container is reachable only through
        // that proxy (docs/DECISIONS.md #15).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
