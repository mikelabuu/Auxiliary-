<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Must be authenticated as staff
        if (!auth('staff')->check()) {
            return redirect()->route('staff.login');
        }

        $staff = auth('staff')->user();

        // Suspension is re-checked on every request, not only at login.
        // StaffAuthController rejects a suspended account at the login form,
        // but that did nothing for a session that already existed — a staff
        // member suspended mid-shift kept full access until their session
        // lapsed (SESSION_LIFETIME, up to two hours). Suspension exists to cut
        // off a departed or compromised employee, which is exactly the case
        // where they are already signed in.
        if ($staff->is_suspended) {
            Auth::guard('staff')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('staff.login')->withErrors([
                'staff_email' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        // Check role
        if (!in_array($staff->role, $roles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
