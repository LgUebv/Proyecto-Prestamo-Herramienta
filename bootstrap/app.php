<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Detrás del proxy de Railway (o cualquier otro) el HTTPS termina antes de
        // llegar a Apache: confiar en X-Forwarded-* para generar URLs https.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\BloquearAlmacenistaInactivo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
