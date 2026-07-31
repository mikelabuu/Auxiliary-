<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * "We have your booking, here is how long the rooms are held."
 *
 * The guest used to hear nothing at all between placing a booking and paying
 * for it. BookingPaidMail covers the happy ending; the desk got
 * StaffBookingAlertMail the moment a booking landed — but the person who made
 * it got no acknowledgement, and no warning that
 * config('bookings.expiry_minutes') was already counting down against them.
 *
 * Deliberately has no PDF and no receipt row: nothing has been paid yet. It
 * states what was booked, what is owed, and when the hold lapses.
 */
class BookingReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build()
    {
        $booking = $this->booking;

        // May be queued and rehydrated without the relation.
        $booking->loadMissing('reservations');

        // Only a pending_payment booking is on the clock. One awaiting a
        // discount decision is waiting on staff, so promising it a deadline
        // would be a lie.
        $holdEndsAt = ($booking->status === 'pending_payment' && $booking->pending_payment_since)
            ? $booking->pending_payment_since->copy()->addMinutes((int) config('bookings.expiry_minutes'))
            : null;

        return $this->subject("Booking #{$booking->id} received — your rooms are on hold")
            ->markdown('emails.booking.received', [
                'booking'    => $booking,
                'holdEndsAt' => $holdEndsAt,
                'payUrl'     => route('bookings.pay', $booking->id),
                'bookingUrl' => route('booking.show', $booking->id),
            ]);
    }
}
