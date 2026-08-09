<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * "Your check-in date passed and you did not arrive."
 *
 * The most delicate of the three: this guest has already paid. Until now
 * bookings:mark-no-show changed a paid booking to no_show overnight and told
 * nobody, so the first they heard of it was finding a paid stay marked against
 * them — or never hearing at all.
 *
 * Deliberately promises nothing about the money. This system has no refund
 * policy written down anywhere, and inventing one in a transactional email
 * would commit the hostel to terms nobody agreed to (PRODUCT.md: "tell the
 * truth or say nothing"). It reports the status and routes the guest to the
 * front desk, which is where that decision actually gets made.
 */
class BookingNoShowMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build()
    {
        $booking = $this->booking;

        $booking->loadMissing('reservations');

        return $this->subject("Booking #{$booking->id} — we did not see you at check-in")
            ->markdown('emails.booking.no-show', [
                'booking'    => $booking,
                'bookingUrl' => route('booking.show', $booking->id),
            ]);
    }
}
