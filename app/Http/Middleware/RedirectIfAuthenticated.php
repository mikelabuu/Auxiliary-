<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Redirect users
                if ($guard === 'web') {
                    if (!$request->is('booking*')) {
                        return redirect('/booking');
                    }
                }

                // Redirect staff
                if ($guard === 'staff') {
                    if (!$request->is('staff/dashboard*')) {
                        return redirect('/staff/dashboard');
                    }
                }
            }
        }

        return $next($request);
    }
}
