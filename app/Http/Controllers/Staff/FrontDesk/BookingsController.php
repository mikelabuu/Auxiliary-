<?php 

namespace App\Http\Controllers\Staff\FrontDesk;

use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Events\GuestBookingUpdated;
use App\Events\RoomStatusChanged;
use App\Http\Controllers\Controller;
use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Checkout;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Realtime;
use App\Support\RefCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\AuditLogger;

class BookingsController extends Controller{

    /**
     * How money can arrive across the counter. Cash is the one the online
     * flow has no equivalent for — Payment::PROOF_METHODS covers only the two
     * a guest can transfer from home and photograph afterwards.
     */
    public const DESK_PAYMENT_METHODS = [
        'cash'          => 'Cash',
        'gcash'         => 'GCash',
        'bank_transfer' => 'Bank Transfer',
    ];

    public function viewBookings(Request $request)
    {
        $search = $request->input('search'); // search query
        $sort   = $request->input('sort', 'latest'); // sort option
        $status = $request->input('status', 'all'); // status tab
        $perPage = 12; // pagination

        // Tab counts reflect the whole book, not the current search
        $statusCounts = Booking::select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        // Base query with reservations eager loaded
        $query = Booking::with('reservations');

        if ($search) {
            // The box says "Search by booking ID" and the column beside it
            // prints BK-0004, so that is what gets pasted into it. Compared
            // raw against an integer column, MySQL cast it to 0 and the desk
            // was told the booking did not exist.
            //
            // A term that is not a code at all falls through to an id nothing
            // can have, which keeps a nonsense search showing nothing rather
            // than quietly showing the entire book.
            $query->where('id', RefCode::toId($search) ?? -1);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Sorting
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name':
                $query->orderBy('guest_name', 'asc');
                break;
            case 'check_in':
                $query->orderBy('check_in', 'asc');
                break;
            default: // latest
                $query->orderBy('created_at', 'desc');
        }

        // Paginate results
        $bookings = $query->paginate($perPage)->withQueryString();

        return view('staff.frontdesk.bookings.index', compact('bookings', 'search', 'sort', 'status', 'statusCounts'));
    }
    
    /**
     * Take payment over the counter for a booking made online.
     *
     * Every other route to `paid` assumes the guest settled remotely and
     * uploaded a receipt for staff to check. A Senior Citizen / PWD booking
     * cannot do that — the discount is granted against an original ID that has
     * to be handed over, so PaymentController refuses the online route for it
     * (see rejectIfNotPayable) and the guest is sent here instead. Without a
     * counter-payment action those bookings had no way to reach `paid` at all:
     * they would simply sit and expire.
     *
     * Not restricted to discounted bookings, because a guest turning up with
     * cash for an ordinary reservation is the same transaction and was equally
     * unrecordable before.
     *
     * The amount is never taken from the request. It is what the booking says
     * is owed — a posted figure is a discount nobody approved.
     */
    public function settle(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'method' => ['required', Rule::in(array_keys(self::DESK_PAYMENT_METHODS))],
            // The OR number, e-wallet reference, or whatever the desk wrote on
            // the slip. Optional: cash across a counter often has no reference
            // until the receipt is cut, and refusing the payment over it would
            // send the guest away with their money.
            'reference' => ['nullable', 'string', 'max:60'],
        ], [
            'method.required' => 'Say how the guest paid.',
        ]);

        $staff = auth('staff')->user();

        $payment = DB::transaction(function () use ($booking, $staff, $validated) {
            // Re-read under a lock: two desks clearing the same booking must
            // not both drive it to paid and both cut a receipt.
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'pending_payment') {
                return null;
            }

            $amount = $locked->payable_amount ?? $locked->total_price;

            // Reuse a half-finished attempt rather than orphaning it, the same
            // way PaymentController does — a booking ends up with one payment
            // row, not one per attempt.
            $payment = Payment::where('booking_id', $locked->id)
                ->whereIn('status', ['pending', Payment::STATUS_AWAITING_VERIFICATION, Payment::STATUS_REJECTED])
                ->latest('id')
                ->first();

            $attributes = [
                'amount'          => $amount,
                'status'          => 'success',
                'payment_type'    => 'manual',
                'gateway'         => $validated['method'],
                'proof_reference' => $validated['reference'] ?: null,
                'paid_at'         => now(),
                'verified_by'     => $staff->id,
                'verified_at'     => now(),
                'rejection_reason' => null,
            ];

            if ($payment) {
                $payment->update($attributes);
            } else {
                $payment = Payment::create($attributes + [
                    'booking_id'   => $locked->id,
                    'user_id'      => $locked->user_id,
                    'reference_no' => strtoupper(Str::random(10)),
                ]);
            }

            $locked->update([
                'status'       => 'paid',
                'payment_mode' => $validated['method'],
            ]);

            AuditLogger::log(
                'booking_settled_at_desk',
                $locked,
                ['status' => 'pending_payment'],
                ['status' => 'paid'],
                "Front desk staff {$staff->name} took " . self::DESK_PAYMENT_METHODS[$validated['method']]
                    . " payment of ₱" . number_format((float) $amount, 2) . " for booking #{$locked->id}"
                    . ($validated['reference'] ? " (ref {$validated['reference']})" : '')
            );

            return $payment;
        });

        if ($payment === null) {
            return back()->with('error', 'That booking is no longer awaiting payment — someone may have settled it already.');
        }

        $booking->refresh();

        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());

        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(BookingStatusChanged::for($booking));
        }

        // The guest is very likely standing at the desk, but the account page
        // they left open elsewhere should not go on showing an unpaid booking.
        if (GuestBookingUpdated::shouldEmitFor($booking)) {
            Realtime::emit(GuestBookingUpdated::paymentVerified($booking));
        }

        // The official receipt is generated inside the mailable. A dead SMTP —
        // or a booking with no account behind it — must never undo a payment
        // that has already been taken and committed.
        $email = $booking->user?->email;

        if (blank($email)) {
            return back()
                ->with('success', "Payment recorded. Booking #{$booking->id} is now paid.")
                ->with('error', 'No guest email on file, so no receipt was sent.');
        }

        try {
            Mail::to($email)->send(new BookingPaidMail($booking, $payment->refresh()));
        } catch (\Throwable $e) {
            Log::error('Failed to email the receipt after a front-desk settlement: ' . $e->getMessage());

            return back()
                ->with('success', "Payment recorded. Booking #{$booking->id} is now paid.")
                ->with('error', 'The receipt email could not be sent — check the mail settings.');
        }

        return back()->with('success', "Payment recorded. Booking #{$booking->id} is paid and the official receipt has been emailed.");
    }

    public function checkout(Booking $booking)
    {
        $staff = auth('staff')->user();

        DB::transaction(function () use ($booking, $staff) {

            $booking->update(['status' => 'completed']);

            foreach ($booking->reservations as $reservation) {
                $reservation->room->update(['status' => 'available']);
            }

            CheckOut::create([
                'booking_id' => $booking->id,
                'checked_out_at' => now(config('hostel.timezone')),
                'method' => 'manual',
                'processed_by' => $staff->id,
            ]);

            AuditLogger::log(
                'booking_checked_out',
                $booking,
                ['status' => 'active'],
                ['status' => 'completed'],
                "Booking #{$booking->id} checked out by {$staff->name}"
            );
        });

        // Emitted after the transaction commits, so every console that
        // re-queries on this signal reads the freed rooms rather than racing
        // an open transaction. The admin-side checkout already did this; the
        // front desk doing the same work did not, which is why the dashboard
        // room map lagged behind desk activity.
        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());
        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(BookingStatusChanged::for($booking->refresh()));
        }

        return back()->with('success', "Booking #{$booking->id} checked out.");
    }
}