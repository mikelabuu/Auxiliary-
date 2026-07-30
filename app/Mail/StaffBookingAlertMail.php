<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The desk-facing counterpart to BookingPaidMail: tells staff that something
 * needs them, so nobody has to sit watching the console.
 *
 * Two events warrant a mail. A new booking means a room is now held and a
 * payment is expected; an uploaded proof of payment means a guest is actively
 * waiting on a human decision. Both carry a deep link straight to the screen
 * where the work gets done.
 */
class StaffBookingAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public const KIND_NEW_BOOKING = 'new_booking';
    public const KIND_PROOF_SUBMITTED = 'proof_submitted';

    public function __construct(
        public Booking $booking,
        public string $kind,
        public ?Payment $payment = null,
    ) {
    }

    public static function newBooking(Booking $booking): self
    {
        return new self($booking, self::KIND_NEW_BOOKING);
    }

    public static function proofSubmitted(Booking $booking, Payment $payment): self
    {
        return new self($booking, self::KIND_PROOF_SUBMITTED, $payment);
    }

    public function build()
    {
        $booking = $this->booking->loadMissing('reservations');
        $isProof = $this->kind === self::KIND_PROOF_SUBMITTED;

        $subject = $isProof
            ? "Proof of payment to verify — booking #{$booking->id}"
            : "New booking #{$booking->id} — awaiting payment";

        // Front desk clears proofs; the booking hub is where a new booking is
        // picked up. Send each alert to the screen that resolves it.
        $actionUrl = $isProof
            ? route('staff.paymentverification.index')
            : route('staff.bookings.index', ['search' => $booking->id]);

        return $this->subject($subject)
            ->markdown('emails.booking.staff-alert', [
                'booking' => $booking,
                'payment' => $this->payment,
                'isProof' => $isProof,
                'actionUrl' => $actionUrl,
                'rooms' => $booking->reservations->pluck('room_number')->implode(', '),
                'amount' => $booking->payable_amount ?: $booking->total_price,
            ]);
    }
}
