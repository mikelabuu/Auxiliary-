<?php

namespace App\Support;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingExpiredMail;
use App\Mail\BookingNoShowMail;
use App\Mail\BookingReceivedMail;
use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Guest-facing transactional mail, wrapped the same way App\Support\StaffAlert
 * wraps the desk alerts.
 *
 * The queue is sync and the mailer is whatever .env says, so an unreachable
 * SMTP host would otherwise surface as a 500 in the middle of a guest
 * completing a booking. A booking that succeeded must never be lost to a mail
 * server being down: every failure here is logged and swallowed.
 */
class GuestNotice
{
    /**
     * Sent the moment a booking is placed. Until this existed the guest heard
     * nothing between booking and paying — while the desk got an alert
     * immediately and the payment window had already started counting down.
     */
    public static function bookingReceived(Booking $booking): void
    {
        self::send($booking, new BookingReceivedMail($booking), 'received notice');
    }

    /**
     * The payment window closed and bookings:expire released the rooms.
     *
     * The guest was promised a deadline by bookingReceived() above; this is the
     * other half of that promise being kept out loud, rather than the
     * reservation simply disappearing.
     */
    public static function bookingExpired(Booking $booking): void
    {
        self::send($booking, new BookingExpiredMail($booking), 'expiry notice');
    }

    /** Written confirmation of a cancellation the guest asked for themselves. */
    public static function bookingCancelled(Booking $booking, ?string $reason = null): void
    {
        self::send($booking, new BookingCancelledMail($booking, $reason), 'cancellation notice');
    }

    /** A paid booking whose check-in date passed with nobody arriving. */
    public static function bookingNoShow(Booking $booking): void
    {
        self::send($booking, new BookingNoShowMail($booking), 'no-show notice');
    }

    /**
     * Resolve the recipient and post, swallowing anything that goes wrong.
     *
     * Three of the four callers are scheduled commands working through a batch,
     * where one guest's dead mailbox must not stop the rest of the batch being
     * told — and none of the four may fail the operation they are reporting on.
     * The booking is already expired, cancelled or marked; mail is the epilogue.
     */
    private static function send(Booking $booking, Mailable $mailable, string $what): void
    {
        $email = $booking->user?->email;

        if (blank($email)) {
            // Walk-ins and desk-entered bookings often have no account behind
            // them. That is normal, not an error.
            Log::info("[GUEST-MAIL] Booking #{$booking->id} has no guest email; skipped the {$what}.");

            return;
        }

        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning("[GUEST-MAIL] Could not send the {$what} for booking #{$booking->id}: " . $e->getMessage());
        }
    }
}
