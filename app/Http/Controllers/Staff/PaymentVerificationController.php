<?php

namespace App\Http\Controllers\Staff;

use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Events\GuestBookingUpdated;
use App\Events\PaymentProofSubmitted;
use App\Events\RoomStatusChanged;
use App\Http\Controllers\Controller;
use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\AuditLogger;
use App\Support\Realtime;
use App\Support\StaffAlert;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The human in the loop. A guest has sent money over GCash or a bank transfer
 * and uploaded the receipt they were given; nothing about that is trustworthy
 * on its own, so a staff member reads the image, checks it against the actual
 * transfer, and either clears the booking or sends it back with a reason.
 */
class PaymentVerificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = in_array($request->query('status'), ['awaiting', 'verified', 'rejected'], true)
            ? $request->query('status')
            : 'awaiting';

        $query = Payment::query()
            ->with(['booking:id,guest_name,check_in,check_out,status', 'verifier:id,name'])
            ->whereNotNull('proof_path');

        match ($filter) {
            'verified' => $query->where('status', 'success'),
            'rejected' => $query->where('status', Payment::STATUS_REJECTED),
            default => $query->awaitingVerification(),
        };

        $payments = $query->latest('proof_submitted_at')->paginate(12)->withQueryString();

        $counts = [
            'awaiting' => Payment::whereNotNull('proof_path')->awaitingVerification()->count(),
            'verified' => Payment::whereNotNull('proof_path')->where('status', 'success')->count(),
            'rejected' => Payment::whereNotNull('proof_path')->where('status', Payment::STATUS_REJECTED)->count(),
        ];

        return view('staff.paymentverification.index', compact('payments', 'counts', 'filter'));
    }

    /**
     * Stream the uploaded receipt from the private disk. It is never a public
     * URL — a receipt carries the guest's name, the amount and their bank
     * reference, so reaching it requires an authenticated staff session.
     */
    public function proof(Payment $payment)
    {
        abort_if(blank($payment->proof_path), 404);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        $response = response()->file(Storage::disk('local')->path($payment->proof_path));
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * Review one claim reached from a staff alert.
     *
     * This GET is deliberately read-only. Mail clients and security scanners
     * follow links automatically, so approval remains the authenticated,
     * CSRF-protected POST below after a staff member has seen the receipt.
     */
    public function show(Payment $payment)
    {
        abort_if(blank($payment->proof_path), 404);

        $payment->load(['booking.reservations', 'verifier:id,name']);

        return response()
            ->view('staff.paymentverification.show', compact('payment'))
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Clear the payment: the booking becomes paid and the system issues its
     * own official receipt — the hash-verified PDF, not the guest's snapshot.
     */
    public function approve(Request $request, Payment $payment)
    {
        $staff = Auth::guard('staff')->user();
        $duplicateReference = false;

        try {
            $booking = DB::transaction(function () use ($payment, $staff, &$duplicateReference) {
                // Every payment lifecycle writer locks booking -> payment. Two
                // staff clicks, and the expiry command racing one of them, can
                // therefore produce only one terminal transition.
                $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->first();
                $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

                if (! $locked || ! $locked->isAwaitingVerification()) {
                    return null;
                }

                if (! $booking || $booking->status !== Booking::STATUS_PENDING_PAYMENT) {
                    return null;
                }

                $amountDue = $booking->payable_amount ?? $booking->total_price;

                // Never let an old/reused payment row settle a newly priced
                // booking. StoreProof refreshes this value on submission; this
                // second check protects legacy rows and any future writer.
                if (number_format((float) $locked->amount, 2, '.', '')
                    !== number_format((float) $amountDue, 2, '.', '')) {
                    return null;
                }

                $acceptedReferenceKey = Payment::acceptedReferenceKey(
                    $locked->proof_method,
                    $locked->proof_reference
                );

                if ($acceptedReferenceKey === null) {
                    return null;
                }

                if (Payment::where('accepted_reference_key', $acceptedReferenceKey)
                    ->where('id', '!=', $locked->id)
                    ->exists()) {
                    $duplicateReference = true;

                    return null;
                }

                $locked->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'verified_by' => $staff->id,
                    'verified_at' => now(),
                    'accepted_reference_key' => $acceptedReferenceKey,
                    'rejection_reason' => null,
                ]);

                $booking->update([
                    'status' => Booking::STATUS_PAID,
                    'payment_mode' => $locked->proof_method ?? 'manual',
                ]);

                AuditLogger::log(
                    'payment_proof_verified',
                    $locked,
                    ['status' => Payment::STATUS_AWAITING_VERIFICATION],
                    ['status' => 'success'],
                    "Staff {$staff->name} verified the {$locked->proof_method_label} proof of payment "
                        . "(ref {$locked->proof_reference}) for booking #{$booking->id}"
                );

                return $booking;
            });
        } catch (QueryException $e) {
            $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

            if (in_array($sqlState, ['23000', '23505'], true)
                && str_contains(strtolower($e->getMessage()), 'accepted_reference')) {
                // The unique index is the concurrency backstop: if two
                // bookings race with the same bank reference, one transaction
                // commits and the other returns a normal cashier-facing error.
                $duplicateReference = true;
                $booking = null;
            } else {
                throw $e;
            }
        }

        if ($booking === null) {
            if ($duplicateReference) {
                return back()->with('error', 'This bank or GCash reference was already accepted for another booking. Do not verify it again.');
            }

            return back()->with('error', 'This payment could not be verified. It may already be handled or the booking amount may have changed.');
        }

        // Both consoles care: the queue loses a row, the booking board gains a
        // paid reservation, and the guest's own page is waiting on the status.
        Realtime::emit(new PaymentProofSubmitted);
        Realtime::emit(new BookingChanged);
        Realtime::emit(new RoomStatusChanged);

        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(BookingStatusChanged::for($booking->refresh()));
        }

        // The guest is most likely watching My Bookings, not the single
        // booking page — that list has its own account-wide channel.
        if (GuestBookingUpdated::shouldEmitFor($booking)) {
            Realtime::emit(GuestBookingUpdated::paymentVerified($booking->refresh()));
        }

        // Financial authority ends with the cashier. Administrators receive
        // an informational notice for oversight; there is no second payment
        // approval action. Delivery failure must not roll back the decision.
        StaffAlert::paymentVerified($booking->refresh(), $payment->refresh());

        // The official receipt is generated inside the mailable. A dead SMTP —
        // or a booking with no account behind it — must not undo a
        // verification that already committed.
        try {
            $email = $booking->user?->email;

            if (blank($email)) {
                // Two flashes: both layouts turn `success` and `error` into
                // toasts, so the verification and its caveat each get said.
                return back()
                    ->with('success', "Payment verified. Booking #{$booking->id} is now paid.")
                    ->with('error', 'No guest email on file, so no receipt was sent.');
            }

            Mail::to($email)->send(new BookingPaidMail($booking, $payment->refresh()));
        } catch (\Throwable $e) {
            Log::error('Failed to send booking confirmation email after manual verification: ' . $e->getMessage());

            return back()
                ->with('success', "Payment verified. Booking #{$booking->id} is now paid.")
                ->with('error', 'The receipt email could not be sent — check the mail settings.');
        }

        return back()->with('success', "Payment verified. Booking #{$booking->id} is paid and the official receipt has been emailed.");
    }

    /**
     * Send it back. The booking stays pending_payment so the guest can correct
     * the receipt and upload again — a rejection is not a cancellation.
     */
    public function reject(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Give the guest a reason so they know what to fix.',
        ]);

        $staff = Auth::guard('staff')->user();

        $booking = DB::transaction(function () use ($payment, $staff, $validated) {
            $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->first();
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $booking
                || $booking->status !== Booking::STATUS_PENDING_PAYMENT
                || ! $locked
                || ! $locked->isAwaitingVerification()) {
                return null;
            }

            $locked->update([
                'status' => Payment::STATUS_REJECTED,
                'verified_by' => $staff->id,
                'verified_at' => now(),
                'accepted_reference_key' => null,
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            // A claim waits on staff, not on the guest. If the original
            // payment clock elapsed while the receipt was being reviewed,
            // give the guest a real window to correct and re-upload it after
            // rejection instead of expiring the booking immediately.
            $booking->forceFill(['pending_payment_since' => now()])->save();

            AuditLogger::log(
                'payment_proof_rejected',
                $locked,
                ['status' => Payment::STATUS_AWAITING_VERIFICATION],
                ['status' => Payment::STATUS_REJECTED],
                "Staff {$staff->name} rejected the proof of payment (ref {$locked->proof_reference}) "
                    . "for booking #{$locked->booking_id}: {$validated['rejection_reason']}"
            );

            return $booking;
        });

        if ($booking === null) {
            return back()->with('error', 'This payment was already handled by another staff member.');
        }

        Realtime::emit(new PaymentProofSubmitted);
        Realtime::emit(new BookingChanged);

        // A rejection is the one outcome the guest must act on, so tell them
        // the reason rather than leaving them to discover it on a refresh.
        if (GuestBookingUpdated::shouldEmitFor($booking)) {
            Realtime::emit(GuestBookingUpdated::paymentRejected($booking, $validated['rejection_reason']));
        }

        return back()->with('success', "Proof rejected. Booking #{$booking->id} is still awaiting payment and the guest can upload a corrected receipt.");
    }

}
