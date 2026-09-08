<?php

namespace App\Http\Controllers;

use App\Events\PaymentProofSubmitted;
use App\Events\StaffNotification;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Support\PaymentWindow;
use App\Support\Realtime;
use App\Support\StaffAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * The one way to settle: send the money over GCash or a bank transfer,
     * then upload the receipt for staff to check against the actual transfer.
     *
     * There used to be a fork here — this page offered a choice between the
     * manual route and a simulated card gateway, and `/pay/sandbox` ran the
     * latter. The gateway was only ever a demo: it moved no real funds, marked
     * a booking paid on a button press, and had no human in the loop. Manual
     * settlement with staff verification is the design this hostel actually
     * operates, so the fork and the gateway behind it are gone and this route
     * serves the upload form directly.
     */
    public function pay(Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($redirect = $this->rejectIfNotPayable($booking, checkWindow: false)) {
            return $redirect;
        }

        // Already waiting on staff. Sending them round the loop again would
        // let one booking stack up several claimed payments.
        if ($this->awaitingProofFor($booking)) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'Your proof of payment is already with our staff for verification.');
        }

        if ($redirect = $this->rejectIfNotPayable($booking)) {
            return $redirect;
        }

        return view('public.payment.proof', [
            'booking' => $booking,
            'amount' => $this->amountFor($booking),
            'methods' => Payment::PROOF_METHODS,
            // Carried over from the retired choice page: a guest whose receipt
            // was turned down needs to see why, at the moment they are about
            // to upload the corrected one.
            'lastRejected' => Payment::where('booking_id', $booking->id)
                ->where('status', Payment::STATUS_REJECTED)
                ->latest('id')
                ->first(),
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

        if ($redirect = $this->rejectIfNotPayable($booking, checkWindow: false)) {
            return $redirect;
        }

        // Re-checked after validation as well: two tabs submitting at once
        // must not both create a claim.
        if ($this->awaitingProofFor($booking)) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'Your proof of payment is already with our staff for verification.');
        }

        if ($redirect = $this->rejectIfNotPayable($booking)) {
            return $redirect;
        }

        $validated = $request->validate([
            'proof_method' => ['required', Rule::in(array_keys(Payment::PROOF_METHODS))],
            'proof_reference' => ['required', 'string', 'max:60', 'regex:/[A-Za-z0-9]/'],
            'proof' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ], [
            'proof.required' => 'Please attach a photo or screenshot of your receipt.',
            'proof.max' => 'The receipt image must be 4MB or smaller.',
            'proof_reference.required' => 'Enter the reference number printed on your receipt.',
            'proof_reference.regex' => 'The payment reference must contain at least one letter or number.',
        ]);

        // Private disk. A receipt shows a guest's name, phone and balance —
        // it must never be reachable by guessing a public URL, so it is served
        // only through the authorised staff preview route.
        $path = $request->file('proof')->store('payment_proofs', 'local');

        try {
            $result = DB::transaction(function () use ($booking, $validated, $path) {
                // Booking creation locks these same room rows first. This
                // closes the deadline race where a lapsed room is sold to a
                // second guest while the first guest's proof is being queued.
                $roomNumbers = $booking->reservations()->pluck('room_number');

                if ($roomNumbers->isNotEmpty()) {
                    Room::whereIn('room_number', $roomNumbers)->lockForUpdate()->get();
                }

                $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->first();

                if (! $lockedBooking) {
                    return ['message' => 'This booking is no longer available.', 'level' => 'error'];
                }

                if ($message = $this->notPayableMessage($lockedBooking, checkWindow: false)) {
                    return ['message' => $message, 'level' => 'error'];
                }

                if ($this->awaitingProofFor($lockedBooking)) {
                    return [
                        'message' => 'Your proof of payment is already with our staff for verification.',
                        'level' => 'info',
                    ];
                }

                if ($message = $this->notPayableMessage($lockedBooking)) {
                    return ['message' => $message, 'level' => 'error'];
                }

                $payment = $this->pendingPaymentFor($lockedBooking)
                    ?? $this->openPayment($lockedBooking, 'manual', $validated['proof_method']);

                $payment->update([
                    // A rejected/abandoned row may predate a discount or other
                    // legitimate price change. The claim being reviewed must
                    // always carry the amount currently owed by the booking.
                    'amount' => $this->amountFor($lockedBooking),
                    'status' => Payment::STATUS_AWAITING_VERIFICATION,
                    'payment_type' => 'manual',
                    'gateway' => $validated['proof_method'],
                    'proof_path' => $path,
                    'proof_method' => $validated['proof_method'],
                    'proof_reference' => $validated['proof_reference'],
                    'accepted_reference_key' => null,
                    'proof_submitted_at' => now(),
                    // A retry after a rejection must not carry the old verdict.
                    'verified_by' => null,
                    'verified_at' => null,
                    'rejection_reason' => null,
                ]);

                return ['booking' => $lockedBooking, 'payment' => $payment];
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }

        if (! isset($result['payment'])) {
            Storage::disk('local')->delete($path);

            return redirect()->route('booking.show', $booking->id)
                ->with($result['level'], $result['message']);
        }

        /** @var Booking $booking */
        $booking = $result['booking'];
        /** @var Payment $payment */
        $payment = $result['payment'];

        // The verification queue is worked live — a guest who has just uploaded
        // a receipt is standing by for a decision.
        Realtime::emit(new PaymentProofSubmitted);
        Realtime::emit(StaffNotification::proofSubmitted($payment->fresh()->load('booking')));

        // And by email, for whoever is not sitting in front of the console.
        StaffAlert::proofSubmitted($booking, $payment);

        return redirect()->route('booking.show', $booking->id)
            ->with('success', 'Proof of payment submitted. Our cashier will verify it shortly.');
    }

    /**
     * Payments belong to whoever owns the booking. Without this any signed-in
     * guest could drive a payment against someone else's reservation.
     */
    private function authorizeBooking(Booking $booking): void
    {
        abort_unless($booking->user_id === Auth::id(), 403);
    }

    /**
     * Only a booking actually awaiting payment may start one — and only one
     * that is allowed to be paid online at all.
     *
     * A Senior Citizen / PWD discount is granted against an original ID that
     * has to be handed over and looked at. Uploading a photograph of one is
     * how the discount is *requested*; it is not how the law says it is
     * granted, and a receipt screenshot proves nothing about who is holding
     * the card. So a discounted booking is settled at the front desk, in
     * person, and this route refuses it rather than letting a guest pay online
     * for a rate they have not yet established they are entitled to.
     *
     * `wants_discount` is the single condition because it is kept honest at
     * both ends: BookingController::store only sets it when there are seniors
     * on the booking, and it is cleared again the moment the request is
     * withdrawn or rejected (DiscountController::cancel,
     * DiscountAdminController::reject) — at which point the guest is paying
     * the ordinary rate and this route opens back up.
     */
    private function rejectIfNotPayable(Booking $booking, bool $checkWindow = true)
    {
        if ($message = $this->notPayableMessage($booking, $checkWindow)) {
            return redirect()->route('booking.show', $booking->id)
                ->with('error', $message);
        }

        return null;
    }

    private function notPayableMessage(Booking $booking, bool $checkWindow = true): ?string
    {
        if ($booking->status !== Booking::STATUS_PENDING_PAYMENT) {
            return 'This booking is not awaiting payment.';
        }

        if ($booking->wants_discount) {
            return 'A Senior Citizen / PWD booking is settled at our front desk. Bring the original ID for every discounted guest — we cannot take this payment online.';
        }

        $deadline = $checkWindow ? PaymentWindow::deadlineFor($booking) : null;

        if ($deadline && now()->greaterThanOrEqualTo($deadline)) {
            return 'This booking’s payment window has ended. If you already transferred the money, contact the front desk before making another booking.';
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
     * A half-finished attempt is reused rather than orphaned, so a booking
     * ends up with one payment row and not two.
     *
     * Nothing opens a `pending` payment any more — the retired card gateway
     * was the only thing that did, and storeProof() moves straight to
     * awaiting_verification. This still runs so a row left `pending` by that
     * flow before it was removed is picked up and completed rather than
     * stranded beside a new one.
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
