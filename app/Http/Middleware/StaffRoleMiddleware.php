<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Must be authenticated as staff
        if (!auth('staff')->check()) {
            return redirect()->route('login');
        }

        $staff = auth('staff')->user();

        // Check role
        if (!in_array($staff->role, $roles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
