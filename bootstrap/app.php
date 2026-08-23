<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // In production nginx terminates TLS and forwards to PHP-FPM over plain
        // HTTP on the loopback. Without this, Laravel only ever sees that inner
        // hop: `$request->secure()` returns false on an HTTPS request, so
        // `asset()` emits http:// URLs into a page the browser loaded over
        // https:// — and the browser blocks every one of them as mixed content.
        //
        // The visible result is not an error page. It is Alpine failing to
        // load, which means the five views using `x-cloak` stay permanently
        // invisible (`[x-cloak] { display: none !important }`) and the nine
        // using `x-data` render but ignore every click. Modals don't open,
        // dropdowns don't drop. Nothing is logged, because nothing threw.
        // The same bug also suppresses HSTS, which SecurityHeaders only sends
        // when `$request->secure()` is true.
        //
        // Loopback only, and never '*': the trusted list is what decides
        // whether X-Forwarded-For can be believed, and a spoofable one hands
        // any client a fresh IP per request — which defeats the IP-keyed login
        // limiter outright. If a proxy is ever added off-box, add its real
        // address here; do not widen it. AWS ELB's header is left out of the
        // set below because nothing here runs behind one.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Global, not web-only: a hardening header is worth just as much on a
        // report download or an availability endpoint as on a rendered page.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Guest-side suspension, enforced on every request rather than only at
        // login. `staff.active` already did this for the staff guard by being
        // named on each staff route group; the `web` guard had no equivalent,
        // so a suspended guest kept the session they were already holding.
        // Group-wide rather than route-by-route: the point is that there is no
        // page a suspended account can still reach, and an allowlist of routes
        // is one forgotten entry away from not being that.
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserNotSuspended::class,
        ]);

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
        // They are all deleted. The real declaration now lives at the top of
        // this closure, naming the loopback rather than '*' — see the note
        // there for what the wrong value costs.

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