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
            // The app's own version understands the staff guard and sends each
            // role to its own dashboard. It was never registered, so Laravel's
            // default ran instead and only ever checked `web` — an authenticated
            // admin opening /login was served the login form.
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);

        // NOTE: the CSRF exemption that used to live here covered
        // `sandbox/webhook/*`, the callback for the simulated card gateway.
        // Both the gateway and its webhook have been removed — settlement is
        // manual (upload a receipt, staff verify) and has no server-to-server
        // callback. There is nothing left in this app that legitimately posts
        // without a session, so no route should be exempt from CSRF; if that
        // ever changes, declare it here rather than with a route-level
        // withoutMiddleware(), which silently does nothing against the web
        // group's actual middleware class.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withBindings([
        Illuminate\Contracts\Console\Kernel::class => App\Console\Kernel::class,
    ])
    ->create();