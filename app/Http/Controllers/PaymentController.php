<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Support\Realtime;
use App\Support\StaffAlert;
use App\Events\PaymentProofSubmitted;
use App\Events\StaffNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Fork in the road: settle manually (GCash / bank transfer, proved by an
     * uploaded receipt and cleared by staff) or run the simulated card
     * gateway. Both land on the same place — a paid booking and an official
     * receipt — but only the manual route has a human in the loop.
     */
    public function pay(Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($redirect = $this->rejectIfNotPayable($booking)) {
            return $redirect;
        }

        // Already waiting on staff. Sending them round the loop again would
        // let one booking stack up several claimed payments.
        if ($this->awaitingProofFor($booking)) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'Your proof of payment is already with our staff for verification.');
        }

        return view('public.payment.choose', [
            'booking' => $booking,
            'amount' => $this->amountFor($booking),
            'lastRejected' => Payment::where('booking_id', $booking->id)
                ->where('status', Payment::STATUS_REJECTED)
                ->latest('id')
                ->first(),
        ]);
    }

    /**
     * The original behaviour, now reached explicitly rather than by default.
     */
    public function sandbox(Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($redirect = $this->rejectIfNotPayable($booking)) {
            return $redirect;
        }

        if ($this->awaitingProofFor($booking)) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'Your proof of payment is already with our staff for verification.');
        }

        $payment = $this->pendingPaymentFor($booking) ?? $this->openPayment($booking, 'online', 'sandbox');

        return redirect()->route('sandbox.pay', $payment->id);
    }

    /**
     * The upload form: where to send the money, and the fields that let staff
     * match a receipt against a real transfer.
     */
    public function proofForm(Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($redirect = $this->rejectIfNotPayable($booking)) {
            return $redirect;
        }

        if ($this->awaitingProofFor($booking)) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'Your proof of payment is already with our staff for verification.');
        }

        return view('public.payment.proof', [
            'booking' => $booking,
            'amount' => $this->amountFor($booking),
            'methods' => Payment::PROOF_METHODS,
        ]);
    }

    /**
     * Record the guest's claim. Nothing is confirmed here — the booking stays
     * pending_payment and the payment sits at awaiting_verification until a
     * staff member has actually looked at the image.
     */
    public function storeProof(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($redirect = $this->rejectIfNotPayable($booking)) {
            return $redirect;
        }

        // Re-checked after validation as well: two tabs submitting at once
        // must not both create a claim.
        if ($this->awaitingProofFor($booking)) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'Your proof of payment is already with our staff for verification.');
        }

        $validated = $request->validate([
            'proof_method' => ['required', Rule::in(array_keys(Payment::PROOF_METHODS))],
            'proof_reference' => ['required', 'string', 'max:60'],
            'proof' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ], [
            'proof.required' => 'Please attach a photo or screenshot of your receipt.',
            'proof.max' => 'The receipt image must be 4MB or smaller.',
            'proof_reference.required' => 'Enter the reference number printed on your receipt.',
        ]);

        // Private disk. A receipt shows a guest's name, phone and balance —
        // it must never be reachable by guessing a public URL, so it is served
        // only through the authorised staff preview route.
        $path = $request->file('proof')->store('payment_proofs');

        $payment = $this->pendingPaymentFor($booking) ?? $this->openPayment($booking, 'manual', $validated['proof_method']);

        $payment->update([
            'status' => Payment::STATUS_AWAITING_VERIFICATION,
            'payment_type' => 'manual',
            'gateway' => $validated['proof_method'],
            'proof_path' => $path,
            'proof_method' => $validated['proof_method'],
            'proof_reference' => $validated['proof_reference'],
            'proof_submitted_at' => now(),
            // A retry after a rejection must not carry the old verdict.
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ]);

        // The verification queue is worked live — a guest who has just uploaded
        // a receipt is standing by for a decision.
        Realtime::emit(new PaymentProofSubmitted());
        Realtime::emit(StaffNotification::proofSubmitted($payment->fresh()->load('booking')));

        // And by email, for whoever is not sitting in front of the console.
        StaffAlert::proofSubmitted($booking, $payment);

        return redirect()->route('booking.show', $booking->id)
            ->with('success', 'Proof of payment submitted. Our front desk will verify it shortly.');
    }

    /**
     * Payments belong to whoever owns the booking. Without this any signed-in
     * guest could drive a payment against someone else's reservation.
     */
    private function authorizeBooking(Booking $booking): void
    {
        abort_unless($booking->user_id === Auth::id(), 403);
    }

    /** Only a booking actually awaiting payment may start one. */
    private function rejectIfNotPayable(Booking $booking)
    {
        if ($booking->status !== 'pending_payment') {
            return redirect()->route('booking.show', $booking->id)
                ->with('error', 'This booking is not awaiting payment.');
        }

        return null;
    }

    private function awaitingProofFor(Booking $booking): bool
    {
        return Payment::where('booking_id', $booking->id)
            ->awaitingVerification()
            ->exists();
    }

    /**
     * A half-finished attempt is reused rather than orphaned — a guest who
     * opens the card gateway, backs out and uploads a GCash receipt instead
     * should end up with one payment row, not two.
     */
    private function pendingPaymentFor(Booking $booking): ?Payment
    {
        return Payment::where('booking_id', $booking->id)
            ->where('status', 'pending')
            ->first();
    }

    private function openPayment(Booking $booking, string $type, string $gateway): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $this->amountFor($booking),
            'status' => 'pending',
            'payment_type' => $type,
            'reference_no' => strtoupper(Str::random(10)),
            'gateway' => $gateway,
        ]);
    }

    private function amountFor(Booking $booking): float
    {
        return (float) ($booking->payable_amount ?? $booking->total_price);
    }
}
