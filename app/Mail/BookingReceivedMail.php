<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\PaymentWindow;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * "We have your booking, here is how long the rooms are held."
 *
 * The guest used to hear nothing at all between placing a booking and paying
 * for it. BookingPaidMail covers the happy ending; the desk got
 * StaffBookingAlertMail the moment a booking landed — but the person who made
 * it got no acknowledgement, and no warning that the payment window
 * (App\Support\PaymentWindow) was already counting down against them.
 *
 * Deliberately has no PDF and no receipt row: nothing has been paid yet. It
 * states what was booked, what is owed, and when the hold lapses.
 */
class BookingReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  bool  $afterDiscountDecision  Sent because a discount was just
     *        decided rather than because a booking was just made. Same facts,
     *        different news: the guest made this booking days ago and what is
     *        new is that it now has an amount and a clock.
     */
    public function __construct(
        public Booking $booking,
        public bool $afterDiscountDecision = false,
    ) {
    }

    public function build()
    {
        $booking = $this->booking;

        // May be queued and rehydrated without the relation.
        $booking->loadMissing('reservations');

        // Only a pending_payment booking is on the clock. One awaiting a
        // discount decision is waiting on staff, so promising it a deadline
        // would be a lie.
        $holdEndsAt = PaymentWindow::deadlineFor($booking);

        $subject = match (true) {
            $this->afterDiscountDecision && $booking->discount > 0
                => "Discount approved — booking #{$booking->id} is ready to settle",
            $this->afterDiscountDecision
                => "Booking #{$booking->id} is ready to settle",
            default
                => "Booking #{$booking->id} received — your rooms are on hold",
        };

        return $this->subject($subject)
            ->markdown('emails.booking.received', [
                'booking'    => $booking,
                'holdEndsAt' => $holdEndsAt,
                'holdLabel'  => PaymentWindow::label(),
                // Whether the check-in cap is what ends this hold rather than
                // the window. "24 hours from when you booked" is simply untrue
                // for a stay starting tonight, and this is the mail a guest
                // will hold us to.
                'endsAtArrival' => $holdEndsAt
                    && $holdEndsAt->equalTo(PaymentWindow::checkInMomentFor($booking)),
                // A Senior/PWD booking is settled in person, so the online
                // payment link would send the guest somewhere that turns them
                // away. See PaymentController::rejectIfNotPayable().
                'paysAtDesk' => (bool) $booking->wants_discount,
                'payUrl'     => route('bookings.pay', $booking->id),
                'bookingUrl' => route('booking.show', $booking->id),
            ]);
    }
}
