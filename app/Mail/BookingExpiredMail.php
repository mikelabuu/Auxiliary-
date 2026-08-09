<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * "The hold on your rooms lapsed, and they are back on sale."
 *
 * The counterpart to BookingReceivedMail, which promises the guest a deadline.
 * Until this existed the deadline passed in silence: bookings:expire flipped
 * the status, released the rooms and wrote an ExpiryLog, and the only person
 * told was whoever happened to have the booking page open at that second.
 * Everyone else discovered it by coming back to a reservation that had vanished.
 *
 * Carries no apology and no offer, because nothing went wrong on either side —
 * it states what happened and how to book again.
 */
class BookingExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build()
    {
        $booking = $this->booking;

        $booking->loadMissing('reservations');

        return $this->subject("Booking #{$booking->id} released — payment window closed")
            ->markdown('emails.booking.expired', [
                'booking'  => $booking,
                'rebookUrl' => route('home') . '#rooms',
            ]);
    }
}
