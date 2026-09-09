<?php

namespace App\Http\Controllers;

use App\Events\BookingChanged;
use App\Events\StaffNotification;
use App\Models\Booking;
use App\Models\RescheduleRequest;
use App\Support\Realtime;
use App\Support\StaffAlert;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The guest half of moving a paid stay.
 *
 * A paid booking cannot be cancelled — the room was taken off sale on the
 * strength of the payment and the money is not coming back. What a guest whose
 * plans change can do is ask to move the dates, and they have to give at least
 * 24 hours' notice before their check-in. That notice period is the desk's, not
 * a formality: it is the time they need to resell a room they are about to
 * lose. Miss it and the booking is theirs to lose — bookings:mark-no-show
 * forfeits it, with no refund.
 *
 * Nothing here changes the booking. Approving does that, and approving is a
 * staff action — see App\Http\Controllers\Staff\RescheduleAdminController.
 * The dates a guest asks for may be sold out, may cost more or fewer nights
 * than they paid for, and may need a conversation; a self-service date swap
 * would silently make all three of those the guest's problem to discover on
 * arrival.
 */
class RescheduleController extends Controller
{
    public function create(Booking $booking)
    {
        $this->authorizeBooking($booking);

        // An approval is the permanent stop. Check it before showing any old
        // pending anomaly so the guest is never invited back into a workflow
        // whose single allowance has already been used.
        if (RescheduleRequest::hasApprovedFor($booking)) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'This booking has already used its one allowed reschedule. The approved dates are now final; please contact our front desk if you need help.');
        }

        // An existing request is the page the guest actually wants: they came
        // back to see what happened to it, not to file a second one.
        if ($open = RescheduleRequest::openFor($booking)) {
            return view('public.booking.reschedule', [
                'booking'  => $booking,
                'existing' => $open,
                'deadline' => RescheduleRequest::deadlineFor($booking),
                'horizon'  => RescheduleRequest::latestCheckInFor($booking),
            ]);
        }

        if ($redirect = $this->rejectIfClosed($booking)) {
            return $redirect;
        }

        return view('public.booking.reschedule', [
            'booking'  => $booking,
            'existing' => null,
            'deadline' => RescheduleRequest::deadlineFor($booking),
            'horizon'  => RescheduleRequest::latestCheckInFor($booking),
        ]);
    }

    public function store(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($redirect = $this->rejectIfClosed($booking)) {
            return $redirect;
        }

        // The replacement arrival may be up to one calendar year after the
        // original arrival; the existing maximum stay length still applies.
        $horizon = RescheduleRequest::latestCheckInFor($booking);
        $maxStay = Carbon::parse($request->input('requested_check_in', 'today'))
            ->addDays(BookingController::MAX_STAY_NIGHTS);

        $validated = $request->validate([
            'requested_check_in'  => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $horizon->toDateString()],
            'requested_check_out' => ['required', 'date', 'after:requested_check_in', 'before_or_equal:' . $maxStay->toDateString()],
            'reason'              => ['required', 'string', 'max:1000'],
        ], [
            'requested_check_in.after_or_equal'  => 'Pick a new arrival date from today onwards.',
            'requested_check_in.before_or_equal' => 'Choose a new check-in on or before ' . $horizon->format('F d, Y') . ', within one year of your original check-in.',
            'requested_check_out.after'          => 'The new departure date has to be after the new arrival date.',
            'requested_check_out.before_or_equal' => 'A single stay can run at most ' . BookingController::MAX_STAY_NIGHTS . ' nights. Please contact us for longer stays.',
            'reason.required'                    => 'Tell us why you need to move the stay — the front desk decides on it.',
        ]);

        // Serialize every request mutation on the booking row. Without this,
        // two fast submissions could both observe "no pending request" and
        // create two queue entries, or a submission could slip in while staff
        // is approving the one move this booking is allowed.
        $outcome = DB::transaction(function () use ($booking, $validated) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($closed = $this->closedReason($lockedBooking)) {
                return ['closed' => $closed];
            }

            if (Carbon::parse($validated['requested_check_in'])->gt(RescheduleRequest::latestCheckInFor($lockedBooking))) {
                throw ValidationException::withMessages(['requested_check_in' => 'Your new check-in must be within one year of your original check-in.']);
            }

            // A reschedule moves a stay; it does not resize one. Recompute from
            // the locked booking rather than trusting the model loaded before
            // validation, because an approval in another request may have just
            // changed these dates.
            $originalNights = max(1, (int) $lockedBooking->check_in->diffInDays($lockedBooking->check_out));
            $requestedNights = max(1, (int) Carbon::parse($validated['requested_check_in'])
                ->diffInDays(Carbon::parse($validated['requested_check_out'])));

            if ($requestedNights !== $originalNights) {
                throw ValidationException::withMessages([
                    'requested_check_out' => 'A reschedule keeps the same length of stay — '
                        . $originalNights . ' ' . ($originalNights === 1 ? 'night' : 'nights')
                        . '. Pick a new arrival date and the whole stay moves with it.',
                ]);
            }

            $sameDates = Carbon::parse($validated['requested_check_in'])->isSameDay($lockedBooking->check_in)
                && Carbon::parse($validated['requested_check_out'])->isSameDay($lockedBooking->check_out);

            if ($sameDates) {
                throw ValidationException::withMessages([
                    'requested_check_in' => 'Those are the dates you already have. Pick the dates you would like to move to.',
                ]);
            }

            return ['reschedule' => RescheduleRequest::create([
                'booking_id'          => $lockedBooking->id,
                'user_id'             => Auth::id(),
                'status'              => RescheduleRequest::STATUS_PENDING,
                // Copied, not referenced: approving the request is the act
                // that overwrites the booking's dates, so without a snapshot
                // the record of what changed would erase itself.
                'original_check_in'   => $lockedBooking->check_in,
                'original_check_out'  => $lockedBooking->check_out,
                'requested_check_in'  => $validated['requested_check_in'],
                'requested_check_out' => $validated['requested_check_out'],
                'reason'              => trim($validated['reason']),
                'submitted_at'        => now(),
            ])];
        });

        if (isset($outcome['closed'])) {
            return redirect()->route('booking.show', $booking->id)
                ->with($outcome['closed']['level'], $outcome['closed']['message']);
        }

        /** @var RescheduleRequest $reschedule */
        $reschedule = $outcome['reschedule'];
        $booking = $reschedule->booking()->with('reservations')->firstOrFail();

        // The desk works this queue live, and the deadline on the booking
        // underneath it may be hours away.
        Realtime::emit(new BookingChanged);
        Realtime::emit(StaffNotification::rescheduleRequested($reschedule->load('booking')));

        // …and by mail, for whoever is not sitting in front of the console.
        StaffAlert::rescheduleRequested($booking, $reschedule);

        return redirect()->route('booking.show', $booking->id)
            ->with('success', 'Reschedule request sent. Our front desk will review your new dates and email you the decision.');
    }

    /**
     * Withdraw a request that has not been decided yet.
     *
     * Cheap to offer and it prevents the obvious support call: a guest who
     * typed the wrong month cannot file a corrected request while the first
     * one is still open (RescheduleRequest::isOpenFor refuses a second), so
     * without this the only way out is to ring the desk.
     */
    public function withdraw(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $withdrawn = DB::transaction(function () use ($booking) {
            // Match request creation and staff decisions: booking first, then
            // request. A stale browser can no longer overwrite an approval
            // with "withdrawn" after staff has already moved the dates.
            Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $open = RescheduleRequest::where('booking_id', $booking->id)
                ->pending()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $open) {
                return null;
            }

            $open->update([
                'status'      => RescheduleRequest::STATUS_WITHDRAWN,
                'reviewed_at' => now(),
            ]);

            return $open;
        });

        if (! $withdrawn) {
            return redirect()->route('booking.show', $booking->id)
                ->with('error', 'There is no reschedule request to withdraw.');
        }

        // Withdrawn requests must leave the staff queue as promptly as they
        // entered it.
        Realtime::emit(new BookingChanged);

        return redirect()->route('booking.show', $booking->id)
            ->with('success', 'Reschedule request withdrawn. Your original dates still stand.');
    }

    private function authorizeBooking(Booking $booking): void
    {
        abort_unless(Auth::check() && $booking->user_id === Auth::id(), 403);
    }

    /**
     * The one place the policy is enforced for the guest.
     *
     * Split by reason rather than answered with a single "you cannot do that",
     * because the three cases need three different things from the guest: pay
     * attention to the status, ring the desk, or wait for the request already
     * in the queue.
     */
    private function rejectIfClosed(Booking $booking)
    {
        if ($closed = $this->closedReason($booking)) {
            return redirect()->route('booking.show', $booking->id)
                ->with($closed['level'], $closed['message']);
        }

        return null;
    }

    /** @return array{level: string, message: string}|null */
    private function closedReason(Booking $booking): ?array
    {
        if (! in_array($booking->status, RescheduleRequest::RESCHEDULABLE_STATUSES, true)) {
            return [
                'level' => 'error',
                'message' => $booking->status === Booking::STATUS_PENDING_PAYMENT || $booking->status === Booking::STATUS_PENDING_DISCOUNT
                    ? 'This booking has not been paid yet, so there is nothing to move — cancel it and book the dates you want instead.'
                    : 'Only a paid booking that has not started yet can be moved. Please contact our front desk.',
            ];
        }

        if (RescheduleRequest::hasApprovedFor($booking)) {
            return [
                'level' => 'info',
                'message' => 'This booking has already used its one allowed reschedule. The approved dates are now final; please contact our front desk if you need help.',
            ];
        }

        if (RescheduleRequest::deadlineFor($booking)->isPast()) {
            return [
                'level' => 'error',
                'message' => 'The deadline to move this stay has passed — we need at least 24 hours of notice before your check-in. Please contact our front desk.',
            ];
        }

        if (RescheduleRequest::where('booking_id', $booking->id)->pending()->exists()) {
            return [
                'level' => 'info',
                'message' => 'You already have a reschedule request waiting on our front desk.',
            ];
        }

        return null;
    }
}
