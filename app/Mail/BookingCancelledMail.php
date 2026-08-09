<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The guest's own written record that they cancelled, and that we agree.
 *
 * A cancellation is the one status change the guest already knows about, so
 * this is not news — it is evidence. Someone who cancels and later finds a
 * charge, or is asked whether they ever cancelled, has nothing to point at
 * without it; the CancellationLog row is on our side of the glass.
 */
class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public ?string $reason = null)
    {
    }

    public function build()
    {
        $booking = $this->booking;

        $booking->loadMissing('reservations');

        return $this->subject("Booking #{$booking->id} cancelled")
            ->markdown('emails.booking.cancelled', [
                'booking'   => $booking,
                'reason'    => $this->reason,
                'rebookUrl' => route('home') . '#rooms',
            ]);
    }
}
