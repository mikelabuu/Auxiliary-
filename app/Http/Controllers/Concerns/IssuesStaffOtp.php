<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Staff;
use App\Models\StaffOtp;
use App\Notifications\StaffLoginOtpNotification;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * The staff second-factor step, shared by the unified login (which starts it)
 * and StaffAuthController (which finishes it on the OTP screen). Extracted so
 * the two cannot drift apart — the login and resend paths previously each
 * carried their own copy of this sequence.
 */
trait IssuesStaffOtp
{
    /**
     * Must this account clear an emailed code after its password?
     *
     * Two conditions, deliberately separate: `otp_enabled` is the whole-feature
     * switch, `otp_roles` narrows it to the accounts worth the extra step. The
     * step used to be all-or-nothing, which meant protecting the master admin
     * also put a mailed code in front of every front-desk sign-in — on a shared
     * counter machine that is signed in and out of all day.
     *
     * Lives here rather than in LoginController because the resend path needs
     * the same answer, and the two drifting apart is how a role that should
     * never see the OTP screen ends up able to request a code on it.
     */
    protected function otpRequiredFor(Staff $staff): bool
    {
        return (bool) config('staff.otp_enabled')
            && in_array($staff->role, (array) config('staff.otp_roles', []), true);
    }

    /**
     * Generate, store, audit and email a fresh login OTP.
     *
     * Returns false when delivery failed. The code is persisted either way —
     * the caller decides what to tell the staff member.
     */
    protected function issueOtp(Staff $staff, string $action, string $description): bool
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

    /** Shared post-login redirect, used whether OTP is enabled or skipped. */
    protected function redirectForRole(Staff $staff)
    {
        switch ($staff->role) {
            case 'admin':
            case 'master_admin':
                return redirect()->intended('/staff/dashboard');
            case 'frontdesk':
                return redirect()->intended('/front-desk/dashboard');
            default:
                Auth::guard('staff')->logout();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your staff account has no valid role assigned.',
                ]);
        }
    }
}
