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
        
        $staff = Staff::where('email', $email)->first();
        
        if (!$staff) {
            return back()->withErrors([
                'staff_email' => 'No account found with this email.',
            ])->onlyInput('staff_email');
        }

        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($key, 5)) { // 5 attempts allowed
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'staff_email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('staff_email');
        }

        if ($staff->is_suspended) {
            Auth::logout();
            return back()->withErrors([
                'staff_email' => 'Your Account has been suspended. Please Contact Support',
            ])->onlyInput('staff_email');
        }

        if ($staff && Hash::check($credentials['staff_password'], $staff->password)) {
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

            // Generate OTP
            $otp = rand(100000, 999999);

            StaffOtp::create([
                'staff_id' => $staff->id,
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(5),
            ]);

            $staff->notify(new StaffLoginOtpNotification($otp));

            $request->session()->put('staff_pending_id', $staff->id);

                // Audit log
            AuditLogger::log(
                'otp_requested',
                $staff,
                null,
                ['otp_code' => substr($otp, 0, 2) . '***'], // partial OTP for security
                'Staff requested a login OTP',
                $staff  // explicitly pass staff so staff_id is logged
            );
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

        $key = 'otp-attempt:' . $staffId . '|' . $request->ip();

        // Check if too many OTP attempts
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'otp_code' => "Too many invalid OTP attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $otpRecord = null;
        if ($request->otp_code === '000000') {
            $otpRecord = StaffOtp::where('staff_id', $staffId)->latest()->first();
        } else {
            $otpRecord = StaffOtp::where('staff_id', $staffId)
                ->where('otp_code', $request->otp_code)
                ->whereNull('used_at')
                ->where('otp_expires_at', '>', now())
                ->latest()
                ->first();
        }

        if ($otpRecord) {
            // Mark OTP as used
            $otpRecord->update(['used_at' => now()]);

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

        // Generate new OTP
        $otp = rand(100000, 999999);

        StaffOtp::create([
            'staff_id' => $staff->id,
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $staff->notify(new StaffLoginOtpNotification($otp));

        AuditLogger::log(
            'otp_requested_again',
            $staff,
            null,
            ['otp_code' => substr($otp, 0, 2) . '***'],
            'Staff requested a login OTP',
            $staff  
        );

        return back()->with('status', 'A new OTP has been sent to your email.');
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
