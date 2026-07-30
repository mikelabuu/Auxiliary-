<?php

namespace App\Support;

use App\Mail\StaffBookingAlertMail;
use App\Models\Booking;
use App\Models\Payment;
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
            'proof of payment for booking #' . $booking->id
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
        $configured = config('staff.alerts.to');

        if (filled($configured)) {
            $addresses = collect(explode(',', (string) $configured))
                ->map(fn ($email) => trim($email))
                ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        } else {
            $addresses = Staff::query()
                ->whereIn('role', (array) config('staff.alerts.roles', []))
                ->where('is_suspended', false)
                ->pluck('email')
                ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        }

        return $addresses
            ->unique()
            ->take((int) config('staff.alerts.max_recipients', 5))
            ->values()
            ->all();
    }

    private static function send(callable $makeMailable, string $what): void
    {
        if (! config('staff.alerts.enabled', true)) {
            return;
        }

        $recipients = self::recipients();

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
