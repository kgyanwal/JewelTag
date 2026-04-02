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
   ->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', [
        // 1. Initialize Tenancy first
        // \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
        // 2. IMMEDIATELY set the timezone so everything following uses it
        \App\Http\Middleware\SetTenantTimezone::class, 
    ]);
})

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();