<?php

namespace App\Http\Controllers\Staff;

use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Events\GuestBookingUpdated;
use App\Events\RoomStatusChanged;
use App\Exceptions\RoomUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RescheduleRequest;
use App\Models\Reservation;
use App\Services\AuditLogger;
use App\Support\GuestNotice;
use App\Support\Realtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The desk half of moving a paid stay.
 *
 * A guest can only ask (App\Http\Controllers\RescheduleController). This is
 * where the answer is given, and approving here is the only thing in the system
 * that rewrites `bookings.check_in` / `check_out` after a booking is paid.
 *
 * Three things make that more than a date swap, and all three are why this is a
 * staff decision rather than a self-service button:
 *
 *   · The rooms may be gone. The guest keeps the same rooms — moving them to
 *     different ones is a different conversation — so the new range is checked
 *     against every other live hold before anything is written.
 *   · The price may change. Nights are what a stay is billed by, so a shorter
 *     or longer stay is a different amount owed. The stay is re-priced at the
 *     nightly rate already recorded on its reservations, and any difference is
 *     settled at the desk — this system never moves money by itself.
 *   · It may simply be a bad idea. A decline with a note is a real outcome.
 */
class RescheduleAdminController extends Controller
{
    public function index(Request $request)
    {
        $filter = in_array($request->query('status'), RescheduleRequest::STATUSES, true)
            ? $request->query('status')
            : RescheduleRequest::STATUS_PENDING;

        $requests = RescheduleRequest::with(['booking.reservations', 'reviewer'])
            ->where('status', $filter)
            // Pending is a queue and works oldest-first — the request that has
            // been waiting longest is also the one closest to its deadline.
            // Everything else is a log, and reads newest-first.
            ->orderBy('submitted_at', $filter === RescheduleRequest::STATUS_PENDING ? 'asc' : 'desc')
            ->paginate(12)
            ->withQueryString();

        $counts = collect(RescheduleRequest::STATUSES)
            ->mapWithKeys(fn ($s) => [$s => RescheduleRequest::where('status', $s)->count()])
            ->all();

        return view('staff.reschedules.index', compact('requests', 'counts', 'filter'));
    }

    /**
     * Move the stay.
     *
     * Everything that can refuse the move runs inside one transaction behind a
     * row lock, for the same reason BookingController::store does: two desks
     * approving two requests for overlapping dates on the same rooms would each
     * pass an availability check the other invalidated.
     */
    public function approve(Request $request, RescheduleRequest $reschedule)
    {
        $validated = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:500'],
        ]);

        $staff = Auth::guard('staff')->user();

        try {
            $booking = DB::transaction(function () use ($reschedule, $staff, $validated) {
                $locked = RescheduleRequest::whereKey($reschedule->id)->lockForUpdate()->first();

                if (! $locked || ! $locked->isPending()) {
                    return null;
                }

                $booking = Booking::whereKey($locked->booking_id)->lockForUpdate()->first();

                // The booking may have moved on while the request sat in the
                // queue — expired, checked in, or forfeited by the no-show
                // sweep. Moving the dates of any of those is meaningless.
                if (! $booking || ! in_array($booking->status, RescheduleRequest::RESCHEDULABLE_STATUSES, true)) {
                    throw new RoomUnavailable('Booking #' . $locked->booking_id . ' is no longer a paid, upcoming stay, so its dates cannot be moved.');
                }

                $roomNumbers = $booking->reservations->pluck('room_number')
                    ->map(fn ($n) => trim((string) $n))
                    ->filter()
                    ->unique()
                    ->all();

                // The same guard the booking passed on the way in, minus this
                // booking itself — its own hold over the old dates must not
                // count against it, and neither must an overlap with the range
                // it is moving out of.
                $clashes = Reservation::query()
                    ->join('bookings', 'bookings.id', '=', 'reservations.booking_id')
                    ->whereIn('reservations.room_number', $roomNumbers)
                    ->where('bookings.id', '!=', $booking->id)
                    ->tap(fn ($q) => Booking::applyActiveHold($q))
                    ->whereDate('bookings.check_in', '<', $locked->requested_check_out)
                    ->whereDate('bookings.check_out', '>', $locked->requested_check_in)
                    ->pluck('reservations.room_number')
                    ->map(fn ($n) => trim((string) $n))
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($clashes)) {
                    throw new RoomUnavailable(
                        'Cannot move this stay: ' . implode(', ', $clashes)
                        . ' ' . (count($clashes) === 1 ? 'is' : 'are') . ' already booked over the new dates. Decline the request and offer the guest other dates.'
                    );
                }

                $oldDates = [
                    'check_in'  => Carbon::parse($booking->check_in)->toDateString(),
                    'check_out' => Carbon::parse($booking->check_out)->toDateString(),
                ];

                // Re-price at the nightly rates already on the reservations, so
                // a rate change since the booking was made cannot be applied
                // retroactively to a guest who has already paid.
                $nights = max(1, $locked->requested_check_in->diffInDays($locked->requested_check_out));
                $newTotal = round((float) $booking->reservations->sum('price') * $nights, 2);
                $paidBefore = (float) ($booking->payable_amount ?? $booking->total_price);

                // What is owed can go up but never down. There is no refund
                // policy — moving to a shorter stay does not buy a credit, and
                // writing a lower payable_amount would have manufactured one:
                // the booking would then read as overpaid, and the next person
                // to look at the books would go hunting for money to return.
                // A longer stay is a genuine balance, collected at the desk.
                $newPayable = round(max($paidBefore, $newTotal - (float) $booking->discount), 2);

                $booking->update([
                    'check_in'       => $locked->requested_check_in->toDateString(),
                    'check_out'      => $locked->requested_check_out->toDateString(),
                    'total_price'    => $newTotal,
                    'payable_amount' => $newPayable,
                ]);

                $locked->update([
                    'status'        => RescheduleRequest::STATUS_APPROVED,
                    'reviewed_by'   => $staff->id,
                    'reviewed_at'   => now(),
                    'decision_note' => $validated['decision_note'] ?: null,
                ]);

                $difference = round($newPayable - $paidBefore, 2);

                AuditLogger::log(
                    'booking_rescheduled',
                    $booking,
                    $oldDates,
                    ['check_in' => $booking->check_in, 'check_out' => $booking->check_out],
                    "Staff {$staff->name} approved reschedule request #{$locked->id}: booking #{$booking->id} moved from "
                        . "{$oldDates['check_in']} – {$oldDates['check_out']} to "
                        . "{$locked->requested_check_in->toDateString()} – {$locked->requested_check_out->toDateString()}"
                        // Stated in the log because it is the one consequence
                        // this system does not act on: money is settled at the
                        // desk, so the only record that a balance exists is
                        // whatever is written down here. A shortfall is never
                        // one of them — see the max() above.
                        . ($difference > 0
                            ? ' — guest now owes ₱' . number_format($difference, 2) . ' more, to settle at the desk'
                            : ($newTotal < $paidBefore
                                ? ' — shorter stay, no refund (house policy); amount paid stands'
                                : ' (no change to the amount)'))
                );

                return $booking;
            });
        } catch (RoomUnavailable $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($booking === null) {
            return back()->with('error', 'That request was already decided by another staff member.');
        }

        // The stay now covers different nights, so both the booking boards and
        // the room map are looking at stale inventory.
        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());

        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(BookingStatusChanged::for($booking->refresh()));
        }

        if (GuestBookingUpdated::shouldEmitFor($booking)) {
            Realtime::emit(GuestBookingUpdated::rescheduleDecided($booking->refresh(), true));
        }

        GuestNotice::rescheduleDecided($booking->refresh(), $reschedule->refresh());

        return back()->with('success', "Booking #{$booking->id} moved to {$reschedule->requested_check_in->format('M d')} – {$reschedule->requested_check_out->format('M d')}. The guest has been emailed.");
    }

    /**
     * Say no, with a reason.
     *
     * The reason is required. A decline leaves the guest holding a paid booking
     * for dates they have already said they cannot make, hours or days from
     * losing it to the no-show sweep — "declined" on its own tells them nothing
     * about what to do next.
     */
    public function decline(Request $request, RescheduleRequest $reschedule)
    {
        $validated = $request->validate([
            'decision_note' => ['required', 'string', 'max:500'],
        ], [
            'decision_note.required' => 'Give the guest a reason — they still have a booking to decide about.',
        ]);

        $staff = Auth::guard('staff')->user();

        $decided = DB::transaction(function () use ($reschedule, $staff, $validated) {
            $locked = RescheduleRequest::whereKey($reschedule->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isPending()) {
                return null;
            }

            $locked->update([
                'status'        => RescheduleRequest::STATUS_DECLINED,
                'reviewed_by'   => $staff->id,
                'reviewed_at'   => now(),
                'decision_note' => $validated['decision_note'],
            ]);

            AuditLogger::log(
                'reschedule_request_declined',
                $locked,
                ['status' => RescheduleRequest::STATUS_PENDING],
                ['status' => RescheduleRequest::STATUS_DECLINED],
                "Staff {$staff->name} declined reschedule request #{$locked->id} for booking #{$locked->booking_id}: {$validated['decision_note']}"
            );

            return $locked;
        });

        if ($decided === null) {
            return back()->with('error', 'That request was already decided by another staff member.');
        }

        Realtime::emit(new BookingChanged());

        $booking = $decided->booking()->first();

        if (GuestBookingUpdated::shouldEmitFor($booking)) {
            Realtime::emit(GuestBookingUpdated::rescheduleDecided($booking, false));
        }

        GuestNotice::rescheduleDecided($booking, $decided);

        return back()->with('success', "Request declined. The guest has been emailed the reason and booking #{$decided->booking_id} keeps its original dates.");
    }
}
