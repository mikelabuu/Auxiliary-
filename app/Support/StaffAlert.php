<?php

namespace App\Support;

use App\Mail\StaffBookingAlertMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RescheduleRequest;
use App\Models\Staff;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Fire-and-forget desk alerts, in the same spirit as App\Support\Realtime.
 *
 * These mails are a convenience for staff, never part of the guest's
 * transaction. The queue is sync and the mailer is SMTP, so an unreachable
 * mail server would otherwise surface as a 500 in the middle of a guest
 * completing a booking or uploading a receipt. Everything here is wrapped: a
 * failed alert is logged and the guest's action still succeeds.
 */
class StaffAlert
{
    public static function newBooking(Booking $booking): void
    {
        self::send(fn () => StaffBookingAlertMail::newBooking($booking), 'new booking #' . $booking->id);
    }

    public static function proofSubmitted(Booking $booking, Payment $payment): void
    {
        self::send(
            fn () => StaffBookingAlertMail::proofSubmitted($booking, $payment),
            'proof of payment for booking #' . $booking->id,
            self::cashierRecipients()
        );
    }

    /** Tell administrators that the financial decision is complete. */
    public static function paymentVerified(Booking $booking, Payment $payment): void
    {
        self::send(
            fn () => StaffBookingAlertMail::paymentVerified($booking, $payment),
            'cashier verification for booking #' . $booking->id,
            self::adminRecipients()
        );
    }

    public static function rescheduleRequested(Booking $booking, RescheduleRequest $reschedule): void
    {
        self::send(
            fn () => StaffBookingAlertMail::rescheduleRequested($booking, $reschedule),
            'reschedule request for booking #' . $booking->id
        );
    }

    /**
     * Where the alerts go: the configured list if there is one, otherwise
     * every active staff member in the on-duty roles.
     *
     * @return array<int, string>
     */
    public static function recipients(): array
    {
        return self::resolveRecipients(
            config('staff.alerts.to'),
            (array) config('staff.alerts.roles', [])
        );
    }

    /** @return array<int, string> */
    public static function cashierRecipients(): array
    {
        return self::resolveRecipients(
            config('staff.alerts.cashier_to'),
            (array) config('staff.alerts.cashier_roles', ['cashier'])
        );
    }

    /** @return array<int, string> */
    public static function adminRecipients(): array
    {
        return self::resolveRecipients(
            config('staff.alerts.admin_to'),
            (array) config('staff.alerts.admin_roles', ['admin', 'master_admin'])
        );
    }

    /**
     * @param  array<int, string>  $roles
     * @return array<int, string>
     */
    private static function resolveRecipients($configured, array $roles): array
    {
        if (filled($configured)) {
            $allowed = collect(explode(',', (string) $configured))
                ->map(fn ($email) => trim($email))
                ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
                ->map(fn ($email) => strtolower($email))
                ->unique()
                ->all();

            // A configured delivery address is still not an identity. Only a
            // live account holding the required role may receive the guest's
            // financial details; suspending or reassigning it stops mail too.
            $addresses = Staff::query()
                ->whereIn('role', $roles)
                ->where('is_suspended', false)
                ->pluck('email')
                ->filter(fn ($email) => in_array(strtolower((string) $email), $allowed, true));
        } else {
            $addresses = Staff::query()
                ->whereIn('role', $roles)
                ->where('is_suspended', false)
                ->pluck('email');
        }

        return $addresses
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->take((int) config('staff.alerts.max_recipients', 5))
            ->values()
            ->all();
    }

    /** @param array<int, string>|null $recipients */
    private static function send(callable $makeMailable, string $what, ?array $recipients = null): void
    {
        if (! config('staff.alerts.enabled', true)) {
            return;
        }

        $recipients ??= self::recipients();

        if (empty($recipients)) {
            Log::info("[STAFF-ALERT] No recipients configured; skipped alert for {$what}.");

            return;
        }

        try {
            Mail::to($recipients)->send($makeMailable());
        } catch (\Throwable $e) {
            Log::warning("[STAFF-ALERT] Could not send alert for {$what}: " . $e->getMessage());
        }
    }
}
