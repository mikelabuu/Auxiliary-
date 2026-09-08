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
    public const KIND_PAYMENT_VERIFIED = 'payment_verified';
    public const KIND_RESCHEDULE = 'reschedule_requested';

    public function __construct(
        public Booking $booking,
        public string $kind,
        public ?Payment $payment = null,
        public ?RescheduleRequest $reschedule = null,
    ) {}

    public static function newBooking(Booking $booking): self
    {
        return new self($booking, self::KIND_NEW_BOOKING);
    }

    public static function proofSubmitted(Booking $booking, Payment $payment): self
    {
        return new self($booking, self::KIND_PROOF_SUBMITTED, $payment);
    }

    public static function paymentVerified(Booking $booking, Payment $payment): self
    {
        return new self($booking, self::KIND_PAYMENT_VERIFIED, $payment);
    }

    public static function rescheduleRequested(Booking $booking, RescheduleRequest $reschedule): self
    {
        return new self($booking, self::KIND_RESCHEDULE, null, $reschedule);
    }

    public function build()
    {
        $booking = $this->booking->loadMissing('reservations');
        $this->payment?->loadMissing('verifier:id,name,role');
        $isProof = $this->kind === self::KIND_PROOF_SUBMITTED;
        $isVerified = $this->kind === self::KIND_PAYMENT_VERIFIED;
        $isReschedule = $this->kind === self::KIND_RESCHEDULE;

        $subject = match ($this->kind) {
            self::KIND_PROOF_SUBMITTED => "Proof of payment to verify — booking #{$booking->id}",
            self::KIND_PAYMENT_VERIFIED => "Payment verified by cashier — booking #{$booking->id}",
            self::KIND_RESCHEDULE => "Reschedule request — booking #{$booking->id}",
            default => "New booking #{$booking->id} — awaiting payment",
        };

        $amount = (float) ($booking->payable_amount ?: $booking->total_price);

        $preheader = match ($this->kind) {
            self::KIND_PROOF_SUBMITTED => '₱' . number_format($amount, 2)
                . " payment proof for booking #{$booking->id} is waiting for review.",
            self::KIND_PAYMENT_VERIFIED => '₱' . number_format($amount, 2)
                . " payment for booking #{$booking->id} was verified by the cashier.",
            self::KIND_RESCHEDULE => "A reschedule request for booking #{$booking->id} needs a staff decision.",
            default => "New booking #{$booking->id} is holding room inventory while payment is pending.",
        };

        // Front desk clears proofs, the reschedule queue owns date changes, and
        // the booking hub is where a new booking is picked up. Send each alert
        // to the screen that resolves it.
        $actionPath = match ($this->kind) {
            self::KIND_PROOF_SUBMITTED => route('staff.paymentverification.show', $this->payment, absolute: false),
            self::KIND_PAYMENT_VERIFIED => route('staff.paymentverification.show', $this->payment, absolute: false),
            self::KIND_RESCHEDULE => route('staff.reschedules.index', absolute: false),
            default => route('staff.bookings.index', ['search' => $booking->id], absolute: false),
        };

        // This mail is built during a guest request. Never inherit its Host
        // header for a staff-facing link: APP_URL is the deployment-controlled
        // canonical origin, while Host may be supplied by the requester.
        $actionUrl = rtrim((string) config('app.url'), '/') . $actionPath;

        return $this->subject($subject)
            ->markdown('emails.booking.staff-alert', [
                'booking' => $booking,
                'payment' => $this->payment,
                'reschedule' => $this->reschedule,
                'isProof' => $isProof,
                'isVerified' => $isVerified,
                'isReschedule' => $isReschedule,
                'actionUrl' => $actionUrl,
                'preheader' => $preheader,
                'rooms' => $booking->reservations->pluck('room_number')->implode(', '),
                'amount' => $amount,
            ]);
    }
}
