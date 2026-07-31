<?php

namespace App\Support;

use App\Mail\BookingReceivedMail;
use App\Models\Booking;
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
        $email = $booking->user?->email;

        if (blank($email)) {
            // Walk-ins and desk-entered bookings often have no account behind
            // them. That is normal, not an error.
            Log::info("[GUEST-MAIL] Booking #{$booking->id} has no guest email; skipped the received notice.");

            return;
        }

        try {
            Mail::to($email)->send(new BookingReceivedMail($booking));
        } catch (\Throwable $e) {
            Log::warning("[GUEST-MAIL] Could not send the received notice for booking #{$booking->id}: " . $e->getMessage());
        }
    }
}
