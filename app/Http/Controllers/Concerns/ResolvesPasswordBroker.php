<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Staff;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;

/**
 * Which password broker a "forgot password" request belongs to.
 *
 * config/auth.php has defined a `staff` broker all along, pointed at the staff
 * provider — but nothing ever asked for it. Both reset controllers called
 * `Password::sendResetLink()` and `Password::reset()`, which use the DEFAULT
 * broker, and the default is `users`. So a staff address went to the guest
 * table, was not found, and came back "We can't find a user with that email
 * address."
 *
 * The effect was that no staff account could ever recover a forgotten
 * password. For an admin that is an annoyance; for the only master_admin it is
 * a locked door with no key, because master_admin is also the only role that
 * can reset anybody else's password from the console. The one account that
 * cannot afford to be locked out was the one with no way back in.
 *
 * Staff is checked first, matching LoginController: an address resolves to one
 * identity, and staff wins if it somehow exists on both sides. Signup and
 * staff creation both reject cross-table addresses, so that should never
 * happen — but the two places that decide "whose account is this?" must agree,
 * or a guest could be sent a reset for a staff account's address.
 */
trait ResolvesPasswordBroker
{
    protected function brokerFor(?string $email): PasswordBroker
    {
        $email = strtolower(trim((string) $email));

        return Password::broker(
            $email !== '' && Staff::where('email', $email)->exists() ? 'staff' : 'users'
        );
    }

    /** Whether this address belongs to a staff account. */
    protected function isStaffAddress(?string $email): bool
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' && Staff::where('email', $email)->exists();
    }
}
