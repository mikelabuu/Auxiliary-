<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suspension has to be enforced on every request, not just at login.
 *
 * is_suspended was only ever checked inside the login controllers, so
 * suspending an account did nothing to the sessions it already had — the
 * staff member kept full access until SESSION_LIFETIME ran out. This runs
 * on every staff route and tears the session down the moment the flag flips.
 */
class EnsureStaffNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = auth('staff')->user();

        if ($staff && $staff->is_suspended) {
            auth('staff')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Half the staff console is fetch()-driven; a 302 to the login
            // page would be parsed as a successful response body.
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended.',
                ], 403);
            }

            return redirect()->route('staff.login')->withErrors([
                'staff_email' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        return $next($request);
    }
}
