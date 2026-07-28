<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\StaffOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Notifications\StaffLoginOtpNotification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Services\AuditLogger;



class StaffAuthController extends Controller
{
    /**
     * A real bcrypt hash of a random string that is not any account's
     * password. Compared against when no account matches, so a bad email
     * costs the same ~100ms as a bad password — otherwise the early return
     * is a timing oracle for "does this staff email exist".
     */
    private const DUMMY_HASH = '$2y$12$uls4l0TXeBb6YY3SqUj4UOzd1KZDhgzXHxTtRt0clkhrK99q0nGf2';

    // Show login page
    public function showLoginForm()
    {
        return view('staff.login');
    }

    // Staff login - step 1 (email + password)
    public function loginStaff(Request $request)
    {
        $credentials = $request->validate([
            'staff_email' => 'required|email',
            'staff_password' => 'required',
        ]);

        $email = strtolower($credentials['staff_email']); // normalize
        $key = 'login-attempt:' . $email . '|' . $request->ip();

        // Throttle FIRST. This used to sit below the "no such account" branch,
        // which left email enumeration completely unlimited.
        if (RateLimiter::tooManyAttempts($key, 5)) { // 5 attempts allowed
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'staff_email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('staff_email');
        }

        $staff = Staff::where('email', $email)->first();

        // A missing account and a wrong password must be indistinguishable —
        // same message, and the same bcrypt cost so response time doesn't leak
        // which emails are real.
        if (!$staff) {
            Hash::check($credentials['staff_password'], self::DUMMY_HASH);
            RateLimiter::hit($key, 900);

            return back()->withErrors([
                'staff_email' => 'Invalid staff credentials',
            ])->onlyInput('staff_email');
        }

        if (Hash::check($credentials['staff_password'], $staff->password)) {
            // Checked only after the password is proven, so this can't be used
            // to probe which accounts exist.
            if ($staff->is_suspended) {
                RateLimiter::hit($key, 900);

                return back()->withErrors([
                    'staff_email' => 'Your Account has been suspended. Please Contact Support',
                ])->onlyInput('staff_email');
            }

            //  Clear limiter on success
            RateLimiter::clear($key);

            if (!config('staff.otp_enabled')) {
                Auth::guard('staff')->login($staff);
                $staff->update(['last_login_at' => now()]);
                $request->session()->regenerate();

                AuditLogger::log(
                    'staff_login',
                    $staff,
                    null,
                    null,
                    'Staff logged in (OTP disabled)',
                    $staff
                );

                return $this->redirectForRole($staff);
            }

            // Mark the session as pending BEFORE attempting delivery. The
            // mailer runs inline on this request, so a throwing SMTP call used
            // to 500 with no pending id set — stranding the staff member on a
            // login they had already passed.
            $request->session()->put('staff_pending_id', $staff->id);

            if (! $this->issueOtp($staff, 'otp_requested', 'Staff requested a login OTP')) {
                return redirect()->route('staff.otp.form')->withErrors([
                    'otp_code' => 'We could not email your code just now. Use Resend to try again.',
                ]);
            }

            return redirect()->route('staff.otp.form')->with('status', 'We sent a 6-digit OTP to your email.');
        }

        // Record a failed attempt (decay = 15 minutes)
        RateLimiter::hit($key, 900);

        return back()->withErrors([
            'staff_email' => 'Invalid staff credentials',
        ])->onlyInput('staff_email');
    }

    // Show OTP form
    public function showOtpForm()
    {
        return view('staff.verify');
    }

    // Verify OTP - step 2
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        $staffId = $request->session()->get('staff_pending_id');

        if (!$staffId) {
            return redirect()->route('staff.login')->withErrors([
                'otp_code' => 'Session expired. Please log in again.',
            ]);
        }

        // Keyed on the staff account alone, not the IP. Reaching this step
        // already requires a valid password, so there is no lockout-DoS to
        // worry about — and an IP in the key would let a rotating attacker
        // walk the whole 6-digit space.
        $key = 'otp-attempt:' . $staffId;

        // Check if too many OTP attempts
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'otp_code' => "Too many invalid OTP attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $otpRecord = StaffOtp::where('staff_id', $staffId)
            ->where('otp_code', $request->otp_code)
            ->whereNull('used_at')
            ->where('otp_expires_at', '>', now())
            ->latest()
            ->first();

        if ($otpRecord) {
            // Burn every outstanding code for this account, not just the one
            // that matched — otherwise an earlier unused OTP stays valid until
            // its own expiry and can be replayed.
            StaffOtp::where('staff_id', $staffId)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            // Log staff in
            $staff = Staff::find($staffId);
            Auth::guard('staff')->login($staff);
            $staff->update(['last_login_at' => now()]);

            // Clear session
            $request->session()->forget('staff_pending_id');
            $request->session()->regenerate();

            RateLimiter::clear($key); // clear OTP attempts on success

            return $this->redirectForRole($staff);
        }

        // Record failed OTP attempt
        RateLimiter::hit($key, 900); // decay = 15 mins

        return back()->withErrors([
            'otp_code' => 'Invalid or expired OTP. Please try again.',
        ]);
    }


    //resend logic

    public function resendOtp(Request $request)
    {
        $staffId = $request->session()->get('staff_pending_id');

        if (!$staffId) {
            return redirect()->route('staff.login')->withErrors([
                'otp_code' => 'Session expired. Please log in again.',
            ]);
        }

        $staff = Staff::find($staffId);

        if (!$staff) {
            return redirect()->route('staff.login')->withErrors([
                'otp_code' => 'Staff account not found.',
            ]);
        }

        // Create a unique rate limiter key
        $key = 'resend-otp:' . $staff->id;

        // Check if staff has exceeded limit
        if (RateLimiter::tooManyAttempts($key, 3)) { // max 3 attempts
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'otp_code' => "Too many OTP requests. Please wait {$seconds} seconds before trying again.",
            ]);
        }

        // Record this attempt (expires in 10 minutes)
        RateLimiter::hit($key, 600); // 600 seconds = 10 minutes

        if (! $this->issueOtp($staff, 'otp_requested_again', 'Staff requested a new login OTP')) {
            return back()->withErrors([
                'otp_code' => 'We could not email your code just now. Please try again in a moment.',
            ]);
        }

        return back()->with('status', 'A new OTP has been sent to your email.');
    }

    /**
     * Generate, store, audit and email a fresh login OTP.
     *
     * Returns false when delivery failed. The code is persisted either way —
     * the caller decides what to tell the staff member. Both the login step
     * and the resend button go through here so they cannot drift apart.
     */
    private function issueOtp(Staff $staff, string $action, string $description): bool
    {
        // random_int(), not rand(). rand() is Mersenne Twister: observing a
        // handful of codes is enough to infer the generator state and predict
        // the next one, which would defeat the whole second factor.
        $otp = random_int(100000, 999999);

        StaffOtp::create([
            'staff_id' => $staff->id,
            'otp_code' => (string) $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        AuditLogger::log(
            $action,
            $staff,
            null,
            ['otp_code' => substr((string) $otp, 0, 2) . '***'], // partial OTP for security
            $description,
            $staff // explicitly pass staff so staff_id is logged
        );

        // Mail goes out inline (the notification is not ShouldQueue and the
        // queue is sync), so an SMTP outage would otherwise surface as a 500
        // in the middle of the login flow.
        try {
            $staff->notify(new StaffLoginOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error('Failed to send staff login OTP', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    // Shared post-login redirect, used whether OTP is enabled or skipped.
    private function redirectForRole(Staff $staff)
    {
        switch ($staff->role) {
            case 'admin':
            case 'master_admin':
                return redirect('/staff/dashboard');
            case 'frontdesk':
                return redirect('/front-desk/dashboard');
            default:
                Auth::guard('staff')->logout();
                return redirect()->route('staff.login')->withErrors([
                    'staff_email' => 'Invalid staff role.',
                ]);
        }
    }
}
