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
        // Global, not web-only: a hardening header is worth just as much on a
        // report download or an availability endpoint as on a rendered page.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

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

        // NOTE: there is no `app/Http/Kernel.php`, and that is deliberate.
        // A Laravel 10 kernel and its middleware classes (TrustProxies,
        // EncryptCookies, VerifyCsrfToken, TrimStrings,
        // PreventRequestsDuringMaintenance) survived the upgrade to 12 and sat
        // in the tree for a long time doing nothing — this file is where the
        // middleware stack is configured, and nothing bound the HTTP kernel
        // contract to that class, so the framework's own kernel ran instead.
        // Dead, but not harmless to read: the stale TrustProxies declared
        // `$proxies = '*'`, which looks like this app trusts every forwarding
        // header and would let a client spoof X-Forwarded-For — and so defeat
        // the IP-keyed login limiter — the moment anyone wired the kernel up.
        // They are all deleted. If this app is ever put behind a load balancer
        // or a reverse proxy, declare the real thing here with
        // `$middleware->trustProxies(at: [...])`, naming the proxy addresses.
        // Never '*'.

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