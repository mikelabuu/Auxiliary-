<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'staff.role' => \App\Http\Middleware\StaffRoleMiddleware::class,
            'staff.active' => \App\Http\Middleware\EnsureStaffNotSuspended::class,
        ]);

        // The gateway callback has no session to carry a token. This has to be
        // declared here: the route-level withoutMiddleware() it used to rely on
        // named App\Http\Middleware\VerifyCsrfToken, which is a leftover from
        // the Laravel 10 layout and is not the class actually in the web group,
        // so the exclusion silently did nothing and the webhook always 419'd.
        $middleware->validateCsrfTokens(except: [
            'sandbox/webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withBindings([
        Illuminate\Contracts\Console\Kernel::class => App\Console\Kernel::class,
    ])
    ->create();