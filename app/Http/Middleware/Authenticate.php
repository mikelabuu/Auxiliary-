<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{

    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {
            return route('login'); // make sure you have a login route
        }
        return null;
    }
}
