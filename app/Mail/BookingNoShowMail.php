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
 * It used to promise nothing about the money, because there was no refund
 * policy written down anywhere and inventing one in a transactional email would
 * have committed the hostel to terms nobody agreed to (PRODUCT.md: "tell the
 * truth or say nothing").
 *
 * There is one now, and the guest agreed to it at checkout: a paid booking
 * cannot be cancelled, only moved, and only if they ask before check-in time on
 * their arrival day. Missing that forfeits the booking with no refund. Saying
 * nothing is no longer the honest option — the same rule is on the checkout
 * terms, the booking page and the reschedule form, and a guest who reads all
 * four should not find this one silent about the outcome it is reporting.
 *
 * Still routes them to the front desk. The policy is the default, not a
 * machine's final word on a person's circumstances.
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
                'deadline'   => \App\Models\RescheduleRequest::deadlineFor($booking),
                'bookingUrl' => route('booking.show', $booking->id),
            ]);
    }
}
