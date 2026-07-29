<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\IssuesStaffOtp;
use App\Models\Staff;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One login for everybody — guests, front desk and admins.
 *
 * There used to be two forms against two guards (/login for guests,
 * /staff/login for staff), which meant two rate limiters, two error
 * vocabularies and two places for an auth bug to hide. This resolves the
 * identity first, then hands off to the right guard.
 *
 * The security properties established in the auth hardening pass are kept
 * deliberately intact here, and are the reason this reads more carefully than
 * a plain Auth::attempt():
 *
 *   - the throttle is checked BEFORE any lookup, so nothing is enumerable
 *   - every failure returns one identical message
 *   - a miss still costs one bcrypt comparison, so timing says nothing
 *   - suspension is checked only after the password is proven
 */
class LoginController extends Controller
{
    use IssuesStaffOtp;

    /**
     * A real bcrypt hash of a random string that is not any account's
     * password. Compared against when no account matches, so a bad email
     * costs the same ~100ms as a bad password.
     */
    private const DUMMY_HASH = '$2y$12$uls4l0TXeBb6YY3SqUj4UOzd1KZDhgzXHxTtRt0clkhrK99q0nGf2';

    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900;

    public function show()
    {
        return view('public.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($credentials['email']));
        $password = $credentials['password'];
        $key = 'login-attempt:' . $email . '|' . $request->ip();

        // Throttle first. Anything before this point would be enumerable.
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        $staff = Staff::where('email', $email)->first();
        $user = $staff ? null : User::where('email', $email)->first();

        // Staff takes precedence when an address somehow exists on both sides.
        // Duplicates were cleaned up, and signup/staff-creation now reject
        // cross-table addresses, so this should never fire — log it if it does.
        if ($staff && User::where('email', $email)->exists()) {
            Log::warning('Login: email exists as both staff and guest.', ['email' => $email]);
        }

        $account = $staff ?? $user;

        if (! $account || ! Hash::check($password, $account->password)) {
            // Keep the cost identical whether or not the address exists.
            if (! $account) {
                Hash::check($password, self::DUMMY_HASH);
            }

            RateLimiter::hit($key, self::DECAY_SECONDS);

            return $this->failed();
        }

        RateLimiter::clear($key);

        return $staff
            ? $this->loginStaff($request, $staff)
            : $this->loginGuest($request, $user);
    }

    // -------------------------------------------------------------
    // Guards
    // -------------------------------------------------------------

    private function loginStaff(Request $request, Staff $staff)
    {
        // Checked only after the password is proven, so it can't be used to
        // probe which addresses belong to staff.
        if ($staff->is_suspended) {
            return back()->withErrors([
                'email' => 'Your account has been suspended. Please contact support.',
            ])->onlyInput('email');
        }

        if (! config('staff.otp_enabled')) {
            Auth::guard('staff')->login($staff);
            $staff->update(['last_login_at' => now()]);
            $request->session()->regenerate();

            AuditLogger::log('staff_login', $staff, null, null, 'Staff logged in (OTP disabled)', $staff);

            return $this->redirectForRole($staff);
        }

        // Fresh session id before the half-authenticated state is written, so a
        // pre-seeded (fixated) id cannot ride along into step two. regenerate()
        // keeps session data, so the CSRF token on the OTP form stays valid.
        $request->session()->regenerate();

        // Mark the session pending BEFORE attempting delivery — the mailer runs
        // inline, so a throwing SMTP call would otherwise strand a staff member
        // who had already passed step one.
        $request->session()->put('staff_pending_id', $staff->id);

        if (! $this->issueOtp($staff, 'otp_requested', 'Staff requested a login OTP')) {
            return redirect()->route('staff.otp.form')->withErrors([
                'otp_code' => 'We could not email your code just now. Use Resend to try again.',
            ]);
        }

        return redirect()->route('staff.otp.form')
            ->with('status', 'We sent a 6-digit code to your email.');
    }

    private function loginGuest(Request $request, User $user)
    {
        if ($user->is_suspended) {
            return back()->withErrors([
                'email' => 'Your account has been suspended. Please contact support.',
            ])->onlyInput('email');
        }

        Auth::guard('web')->login($user);
        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->intended('/checkout');
    }

    // -------------------------------------------------------------

    /**
     * One message for every failure mode — unknown address, wrong password,
     * wrong guard. Anything more specific tells an attacker which addresses
     * are real and which of those are staff.
     */
    private function failed()
    {
        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }
}
