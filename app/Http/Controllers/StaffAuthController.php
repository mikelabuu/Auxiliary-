<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\IssuesStaffOtp;
use App\Models\Staff;
use App\Models\StaffOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The staff second factor only.
 *
 * Email + password now goes through LoginController, which serves guests,
 * front desk and admins from one form. What survives here is step two: the
 * OTP screen, its verification, and resend.
 */
class StaffAuthController extends Controller
{
    use IssuesStaffOtp;

    // Show OTP form
    public function showOtpForm(Request $request)
    {
        // Landing here without having passed step one means nothing to verify.
        if (! $request->session()->has('staff_pending_id')) {
            return redirect()->route('login');
        }

        $staff = Staff::find($request->session()->get('staff_pending_id'));

        // An exempt role can only reach this screen with a pending session
        // opened before the roles changed under it — a deploy landing between
        // someone's password and their code. No code is coming, so drop the
        // half-authenticated state and let them sign in again; the second
        // attempt goes straight through.
        if ($staff && ! $this->otpRequiredFor($staff)) {
            $request->session()->forget('staff_pending_id');

            return redirect()->route('login');
        }

        // Both values are for display only. Showing them is safe: whoever is on
        // this screen already proved the password in step one, so neither the
        // masked address nor the expiry tells them anything they didn't supply.
        $expiresAt = $staff
            ? StaffOtp::where('staff_id', $staff->id)->latest('id')->value('otp_expires_at')
            : null;

        return view('staff.verify', [
            'maskedEmail' => $staff ? $this->maskEmail($staff->email) : null,
            'expiresAt' => $expiresAt,
        ]);
    }

    /**
     * f••••••@clsu.edu.ph — enough for the owner to recognise the inbox,
     * not enough to be worth harvesting off a shoulder-surfed screen.
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return str_repeat('•', mb_strlen($email));
        }

        $keep = mb_substr($local, 0, 1);
        $hidden = max(mb_strlen($local) - 1, 1);

        return $keep . str_repeat('•', min($hidden, 8)) . '@' . $domain;
    }

    // Verify OTP - step 2
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        $staffId = $request->session()->get('staff_pending_id');

        if (!$staffId) {
            return redirect()->route('login')->withErrors([
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
            return redirect()->route('login')->withErrors([
                'otp_code' => 'Session expired. Please log in again.',
            ]);
        }

        $staff = Staff::find($staffId);

        if (!$staff) {
            return redirect()->route('login')->withErrors([
                'otp_code' => 'Staff account not found.',
            ]);
        }

        // Same stale-session case as showOtpForm: never mail a code to a role
        // that is no longer asked for one.
        if (! $this->otpRequiredFor($staff)) {
            $request->session()->forget('staff_pending_id');

            return redirect()->route('login');
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
}
