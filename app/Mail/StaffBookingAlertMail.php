<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RescheduleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The desk-facing counterpart to BookingPaidMail: tells staff that something
 * needs them, so nobody has to sit watching the console.
 *
 * Three events warrant a mail. A new booking means a room is now held and a
 * payment is expected; an uploaded proof of payment means a guest is actively
 * waiting on a human decision; a reschedule request means a paid stay is being
 * asked to move, against a deadline the desk cannot extend. Each carries a
 * deep link straight to the screen where the work gets done.
 */
class StaffBookingAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public const KIND_NEW_BOOKING = 'new_booking';
    public const KIND_PROOF_SUBMITTED = 'proof_submitted';
    public const KIND_RESCHEDULE = 'reschedule_requested';

    public function __construct(
        public Booking $booking,
        public string $kind,
        public ?Payment $payment = null,
        public ?RescheduleRequest $reschedule = null,
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

    public static function rescheduleRequested(Booking $booking, RescheduleRequest $reschedule): self
    {
        return new self($booking, self::KIND_RESCHEDULE, null, $reschedule);
    }

    public function build()
    {
        $booking = $this->booking->loadMissing('reservations');
        $isProof = $this->kind === self::KIND_PROOF_SUBMITTED;
        $isReschedule = $this->kind === self::KIND_RESCHEDULE;

        $subject = match ($this->kind) {
            self::KIND_PROOF_SUBMITTED => "Proof of payment to verify — booking #{$booking->id}",
            self::KIND_RESCHEDULE => "Reschedule request — booking #{$booking->id}",
            default => "New booking #{$booking->id} — awaiting payment",
        };

        // Front desk clears proofs, the reschedule queue owns date changes, and
        // the booking hub is where a new booking is picked up. Send each alert
        // to the screen that resolves it.
        $actionUrl = match ($this->kind) {
            self::KIND_PROOF_SUBMITTED => route('staff.paymentverification.index'),
            self::KIND_RESCHEDULE => route('staff.reschedules.index'),
            default => route('staff.bookings.index', ['search' => $booking->id]),
        };

        return $this->subject($subject)
            ->markdown('emails.booking.staff-alert', [
                'booking' => $booking,
                'payment' => $this->payment,
                'reschedule' => $this->reschedule,
                'isProof' => $isProof,
                'isReschedule' => $isReschedule,
                'actionUrl' => $actionUrl,
                'rooms' => $booking->reservations->pluck('room_number')->implode(', '),
                'amount' => $booking->payable_amount ?: $booking->total_price,
            ]);
    }
}
