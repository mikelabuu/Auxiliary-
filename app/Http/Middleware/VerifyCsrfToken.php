<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Empty, and it should stay that way: the only entry was
     * `sandbox/webhook/*`, the callback for the simulated card gateway, and
     * both are gone. Nothing in this app posts without a session any more.
     *
     * Note this class is itself a Laravel 10 leftover — the web group is
     * configured in `bootstrap/app.php`, which does not bind an HTTP kernel,
     * so neither this nor `app/Http/Kernel.php` is loaded at runtime. Real
     * exemptions belong in `$middleware->validateCsrfTokens(except: [...])`.
     *
     * @var array<int, string>
     */
    protected $except = [];
}
