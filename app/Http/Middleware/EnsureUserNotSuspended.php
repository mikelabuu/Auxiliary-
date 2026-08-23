<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The guest-side twin of EnsureStaffNotSuspended.
 *
 * Suspension was only ever checked inside LoginController::loginGuest(), which
 * is the one moment a suspended guest is NOT trying to get in — they are
 * already in. Suspending an account did nothing to the session it already had,
 * so the guest kept browsing, kept booking, and kept paying until
 * SESSION_LIFETIME ran out.
 *
 * Worse, the login check then read as broken rather than absent. A suspended
 * guest who submitted the login form got `back()` from the controller, and the
 * `guest` middleware on GET /login redirected the still-authenticated session
 * to `/` — carrying the flashed error with it. The visible result was the
 * suspension notice appearing on the landing page of an account that was very
 * much still signed in: "it says I'm suspended, and I'm still logged in."
 *
 * Runs on every web request, so the flag takes effect on the guest's next
 * click rather than at their next login.
 */
class EnsureUserNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->is_suspended) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Checkout and the account pages talk to the server with fetch();
            // a 302 to the login form would be read as a successful response
            // body and rendered into the page.
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended.',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        return $next($request);
    }
}
