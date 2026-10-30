<?php

use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // En production, Nginx (Webuzo) reçoit le HTTPS et transmet à Apache :
        // on fait confiance à ses en-têtes pour reconnaître les pages sécurisées.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'));

        // Un compte suspendu ou exclu est déconnecté à sa prochaine requête.
        $middleware->appendToGroup('web', EnsureAccountIsActive::class);
        $middleware->appendToGroup('api', EnsureAccountIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
