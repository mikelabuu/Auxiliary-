<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\RescheduleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The desk's answer to a request to move a paid stay.
 *
 * One mailable for both outcomes rather than two, because the guest is reading
 * for the same three facts either way — which dates apply now, what the desk
 * said, and what they have to do next — and splitting it would be two templates
 * that have to keep agreeing about the deadline.
 *
 * A decline is the more important of the two to get right. The guest is left
 * holding a paid booking for dates they have already told us they cannot make,
 * and bookings:mark-no-show is still coming for it, so the mail has to say so.
 */
class RescheduleDecidedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public RescheduleRequest $reschedule,
    ) {
    }

    public function build()
    {
        $booking = $this->booking->loadMissing('reservations');
        $approved = $this->reschedule->status === RescheduleRequest::STATUS_APPROVED;

        $subject = $approved
            ? "Booking #{$booking->id} moved to " . $booking->check_in->format('M d, Y')
            : "We could not move booking #{$booking->id}";

        return $this->subject($subject)
            ->markdown('emails.booking.reschedule-decided', [
                'booking'    => $booking,
                'reschedule' => $this->reschedule,
                'approved'   => $approved,
                // The deadline is recomputed from the booking as it now stands.
                // On an approval that is the *new* arrival day, which is the
                // one the guest has to work to from here.
                'deadline'   => RescheduleRequest::deadlineFor($booking),
                'bookingUrl' => route('booking.show', $booking->id),
            ]);
    }
}
