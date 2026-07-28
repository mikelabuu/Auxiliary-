<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Guards whose users carry an `is_suspended` flag, and where to send them
     * once their session has been torn down.
     */
    private const SUSPENDABLE_GUARDS = [
        'web'   => 'login',
        'staff' => 'staff.login',
    ];

    /**
     * Suspension is enforced on every request, not only at sign-in.
     *
     * Both AuthController and StaffAuthController reject a suspended account at
     * their login form, but that did nothing for a session that already
     * existed: a guest or staff member suspended mid-session kept full access
     * until the session lapsed on its own (SESSION_LIFETIME, up to two hours).
     * Suspension is how a compromised or departed account is cut off, which is
     * exactly the case where it is already signed in.
     */
    protected function authenticate($request, array $guards)
    {
        parent::authenticate($request, $guards);

        foreach ($guards ?: [null] as $guard) {
            $name = $guard ?? 'web';

            if (! array_key_exists($name, self::SUSPENDABLE_GUARDS)) {
                continue;
            }

            $user = auth()->guard($guard)->user();

            if ($user && $user->is_suspended) {
                $this->logOutSuspended($request, $name);
            }
        }
    }

    /**
     * Tear the session down and bounce the account to the login form that
     * belongs to its guard, carrying the reason.
     */
    private function logOutSuspended(Request $request, string $guardName): void
    {
        auth()->guard($guardName === 'web' ? null : $guardName)->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $route = self::SUSPENDABLE_GUARDS[$guardName];
        $field = $guardName === 'staff' ? 'staff_email' : 'email';

        abort(
            redirect()->route($route)->withErrors([
                $field => 'Your account has been suspended. Please contact support.',
            ])
        );
    }

    protected function redirectTo($request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Staff consoles have their own login form. This used to return
        // route('login') for every guard, so a staff member whose session had
        // expired was dropped on the customer login page, where their
        // credentials cannot work. StaffRoleMiddleware already redirects to
        // staff.login; this brings the two into agreement.
        //
        // Keyed off the route's own `auth:staff` middleware rather than a list
        // of URL prefixes, because the admin booking hub is mounted at
        // /bookings and would not match a /staff/* pattern.
        if ($this->isStaffRoute($request)) {
            return route('staff.login');
        }

        return route('login');
    }

    private function isStaffRoute($request): bool
    {
        $route = $request->route();

        if ($route === null) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'auth:staff')) {
                return true;
            }
        }

        return false;
    }
}
