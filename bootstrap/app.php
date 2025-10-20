<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ConsultantReadOnly;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add or extend route middleware aliases:
        $middleware->alias([
            // keep existing aliases if present (auth, verified, etc.)
            'admin' => AdminMiddleware::class,
            'consultant.readonly' => ConsultantReadOnly::class,
        ]);

        // (Optional) you can also adjust groups:
        // $middleware->group('web', [...]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
