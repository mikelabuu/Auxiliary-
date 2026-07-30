<?php

namespace App\Http\Controllers\Staff;

use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Events\GuestBookingUpdated;
use App\Events\PaymentProofSubmitted;
use App\Events\RoomStatusChanged;
use App\Http\Controllers\Controller;
use App\Mail\BookingPaidMail;
use App\Models\Payment;
use App\Services\AuditLogger;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        return response()->file(Storage::disk('local')->path($payment->proof_path));
    }

    /**
     * Clear the payment: the booking becomes paid and the system issues its
     * own official receipt — the hash-verified PDF, not the guest's snapshot.
     */
    public function approve(Request $request, Payment $payment)
    {
        $staff = Auth::guard('staff')->user();

        $booking = DB::transaction(function () use ($payment, $staff) {
            // Re-read under a lock. Two staff opening the same queue and both
            // clicking Verify must not both drive the booking to paid.
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isAwaitingVerification()) {
                return null;
            }

            $booking = $locked->booking;

            if (! $booking || $booking->status !== 'pending_payment') {
                return null;
            }

            $locked->update([
                'status' => 'success',
                'paid_at' => now(),
                'verified_by' => $staff->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            $booking->update([
                'status' => 'paid',
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

        if ($booking === null) {
            return back()->with('error', 'This payment was already handled by another staff member.');
        }

        // Both consoles care: the queue loses a row, the booking board gains a
        // paid reservation, and the guest's own page is waiting on the status.
        Realtime::emit(new PaymentProofSubmitted());
        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());

        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(BookingStatusChanged::for($booking->refresh()));
        }

        // The guest is most likely watching My Bookings, not the single
        // booking page — that list has its own account-wide channel.
        if (GuestBookingUpdated::shouldEmitFor($booking)) {
            Realtime::emit(GuestBookingUpdated::paymentVerified($booking->refresh()));
        }

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
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isAwaitingVerification()) {
                return null;
            }

            $locked->update([
                'status' => Payment::STATUS_REJECTED,
                'verified_by' => $staff->id,
                'verified_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            AuditLogger::log(
                'payment_proof_rejected',
                $locked,
                ['status' => Payment::STATUS_AWAITING_VERIFICATION],
                ['status' => Payment::STATUS_REJECTED],
                "Staff {$staff->name} rejected the proof of payment (ref {$locked->proof_reference}) "
                    . "for booking #{$locked->booking_id}: {$validated['rejection_reason']}"
            );

            return $locked->booking;
        });

        if ($booking === null) {
            return back()->with('error', 'This payment was already handled by another staff member.');
        }

        Realtime::emit(new PaymentProofSubmitted());
        Realtime::emit(new BookingChanged());

        // A rejection is the one outcome the guest must act on, so tell them
        // the reason rather than leaving them to discover it on a refresh.
        if (GuestBookingUpdated::shouldEmitFor($booking)) {
            Realtime::emit(GuestBookingUpdated::paymentRejected($booking, $validated['rejection_reason']));
        }

        return back()->with('success', "Proof rejected. Booking #{$booking->id} is still awaiting payment and the guest can upload a corrected receipt.");
    }

    /**
     * Re-authentication before money moves, matching the other consequential
     * staff actions in the console.
     */
    public function verifyPassword(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $staff = Auth::guard('staff')->user();

        if (! $staff || ! Hash::check($request->password, $staff->password)) {
            return response()->json(['success' => false, 'message' => 'Incorrect password'], 422);
        }

        return response()->json(['success' => true]);
    }
}
