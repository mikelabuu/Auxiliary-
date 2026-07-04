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

                if ($guard === 'web') {
                    return redirect('/');
                }

                // Redirect staff
                if ($guard === 'staff') {

                    $staff = Auth::guard('staff')->user();

                    if ($staff->role === 'admin') {
                        return redirect()->route('staff.dashboard');
                    }

                    if ($staff->role === 'master_admin') {
                        return redirect()->route('staff.dashboard');
                    }

                    if ($staff->role === 'frontdesk') {
                        return redirect()->route('frontdesk.dashboard.index');
                    }
                }
            }
        }

        return $next($request);
    }
}
