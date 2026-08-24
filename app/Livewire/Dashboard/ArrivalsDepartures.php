<?php

namespace App\Livewire\Dashboard;

use App\Support\RoomCatalog;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Models\Booking;
use App\Models\Checkin;
use App\Models\Checkout;
use App\Models\NoShowLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Services\AuditLogger;
use App\Events\RoomStatusChanged;
use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Support\Realtime;

class ArrivalsDepartures extends Component
{
    use WithPagination;

    public $date;
    public $pollInterval = 60; // seconds
    public $sortField = 'guest_name';
    public $sortDirection = 'asc';
    public $perPage = 5;
    public $filterType = 'all';

    protected $paginationTheme = 'tailwind'; // optional, improves pagination look
    protected $listeners = [
        'arrivalsPasswordConfirmed' => 'handlePasswordConfirmed',
        'refreshArrivalsDepartures' => '$refresh',
        'refresh' => '$refresh',
    ];

    public function mount($date = null)
    {
        $this->date = $date ?? Carbon::today(config('hostel.timezone'))->toDateString();
    }

    public function updatingSortField()
    {
        $this->resetPage();
    }

    public function updatingSortDirection()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // ── Day navigation ───────────────────────────────────────────────────────
    // The panel is date-driven ($this->date). Actions stay limited to the real
    // "today" (see $isToday in render()) so staff can browse other days safely.
    public function previousDay()
    {
        $this->date = Carbon::parse($this->date)->subDay()->toDateString();
        $this->resetPage();
    }

    public function nextDay()
    {
        $this->date = Carbon::parse($this->date)->addDay()->toDateString();
        $this->resetPage();
    }

    public function goToday()
    {
        $this->date = Carbon::today(config('hostel.timezone'))->toDateString();
        $this->resetPage();
    }

    //handler
    public function handlePasswordConfirmed($payload)
    {
        $bookingId = $payload['bookingId'] ?? null;
        $action = $payload['action'] ?? null;

        if ($action === 'checkin') {
            $this->confirmCheckIn($bookingId);
        } elseif ($action === 'checkout') {
            $this->confirmCheckOut($bookingId);
        } elseif ($action === 'emergency') {
            $this->confirmEmergencyCheckOut($bookingId, $payload['reason'] ?? null);
        } elseif ($action === 'noshow'){
            $this->confirmNoShow($bookingId);
        }
    }

    /**
     * Has this booking reached its check-out date?
     *
     * The line every check-out in this component is drawn on: true means the
     * ordinary check-out applies (today, or overdue — the same window the
     * auto-checkout command sweeps), false means the stay still has nights
     * left on it and only an emergency check-out can end it.
     */
    protected function isDueOut(Booking $booking, ?string $asOf = null): bool
    {
        $asOf = $asOf
            ? Carbon::parse($asOf)->startOfDay()
            : Carbon::today(config('hostel.timezone'));

        return Carbon::parse($booking->check_out)
            ->timezone(config('hostel.timezone'))
            ->startOfDay()
            ->lte($asOf);
    }

    /**
     * Why this booking cannot be checked in — or null when it can.
     *
     * The same rules confirmCheckIn() enforces, said in the desk's own words.
     * They used to live only inside the handler, so the panel showed a green
     * Check In button on a booking whose payment had not been verified yet and
     * answered the press with "Booking not eligible for check-in." That is
     * true, and it tells the person at the counter — with a guest standing in
     * front of them — nothing about what to do next. One method now decides
     * both what the row offers and what the handler refuses, so the button on
     * screen and the rule behind it cannot drift apart.
     */
    protected function checkInBlocker(Booking $booking): ?string
    {
        if ($booking->status === Booking::STATUS_ACTIVE) {
            return 'Already checked in';
        }

        if ($booking->status !== Booking::STATUS_PAID) {
            return match ($booking->status) {
                'pending', 'pending_payment' => 'Payment not settled',
                'cancelled' => 'Booking cancelled',
                'no_show'   => 'Marked no-show',
                'completed' => 'Stay already closed',
                'expired'   => 'Booking expired',
                default     => 'Not ready for check-in',
            };
        }

        if (($booking->payments->status ?? null) !== 'success') {
            return 'Payment not verified';
        }

        if (! Carbon::parse($booking->check_in)->timezone(config('hostel.timezone'))->isToday()) {
            return 'Arrives ' . Carbon::parse($booking->check_in)->format('M d');
        }

        return null;
    }
    public function render()
    {
        $today = $this->date;

        // Is the panel showing the real "today"? Actions are only offered then,
        // and the check-out rules below are all measured against it rather than
        // against whichever day is being browsed.
        $actualToday = Carbon::today(config('hostel.timezone'))->toDateString();
        $isToday = $this->date === $actualToday;

        // get arrivals & departures. `payments` is eager-loaded because
        // checkInBlocker() reads it for every arrival row; without it the panel
        // fires one extra query per guest on screen.
        $bookings = Booking::with(['reservations', 'payments'])
            ->where(function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->where('check_in', $today)
                        ->where('status', Booking::STATUS_PAID);
                })
                ->orWhere(function ($q3) use ($today) {
                    $q3->where('check_out', $today)
                        ->where('status', Booking::STATUS_ACTIVE);
                })
                // In-house: checked in on or before this date, not due out
                // until after it. These used to be absent from the panel
                // entirely — a guest who booked, paid and checked in the same
                // morning dropped out of the arrivals branch the moment they
                // were checked in and did not reappear until their departure
                // day, so there was nothing on screen to act on if they had to
                // leave early. They are the rows the emergency check-out is for.
                ->orWhere(function ($q4) use ($today) {
                    $q4->where('check_in', '<=', $today)
                        ->where('check_out', '>', $today)
                        ->where('status', Booking::STATUS_ACTIVE);
                });
            })
            ->get();

        $list = $bookings->map(function ($b) use ($today, $actualToday) {
            $isArrival = $b->check_in && Carbon::parse($b->check_in)->isSameDay(Carbon::parse($today));
            $isDeparture = $b->check_out && Carbon::parse($b->check_out)->isSameDay(Carbon::parse($today));

            $type = null;
            if ($isArrival && $b->status === Booking::STATUS_PAID) $type = 'arrival';
            if ($isDeparture && $b->status === Booking::STATUS_ACTIVE) $type = $type ? 'both' : 'departure';
            if (!$type && $b->status === Booking::STATUS_ACTIVE) $type = 'staying';

            return (object) [
                'id' => $b->id,
                'guest_name' => $b->guest_name,
                'check_in' => $b->check_in,
                'check_out' => $b->check_out,
                'nights' => max(1, Carbon::parse($b->check_in)->diffInDays(Carbon::parse($b->check_out))),
                'room_numbers_str' => $b->reservations->pluck('room_number')->unique()->implode(', ') ?: '—',
                'status' => $b->status,
                'type' => $type,
                // Which check-out the row may offer. Decided here, off the same
                // date the handler checks, so the button on screen and the rule
                // that runs it can never disagree: due out today (or overdue)
                // is an ordinary check-out, anything still ahead of its date is
                // the emergency one.
                'due_out' => $this->isDueOut($b, $actualToday),
                // Null when the desk may check this guest in. Anything else is
                // the sentence the row shows instead of the button.
                'checkin_block' => $this->checkInBlocker($b),
                'detail_url' => route('staff.bookings.index'),
            ];
        });

        // Summary counts for the header strip — taken before the tab filter so
        // they reflect the whole day, not the current view.
        $arrivalsCount = $list->whereIn('type', ['arrival', 'both'])->count();
        $departuresCount = $list->whereIn('type', ['departure', 'both'])->count();

        // Apply filter. 'inhouse' is everyone actually in a room on this date,
        // so a guest leaving today belongs in it as much as one staying on —
        // which keeps the tab and the "in-house" chip above it reporting the
        // same number.
        if ($this->filterType === 'inhouse') {
            $list = $list->whereIn('type', ['staying', 'departure', 'both']);
        } elseif ($this->filterType !== 'all') {
            $list = $list->where('type', $this->filterType);
        }

        // sort collection
        $sorted = $list->sortBy([
            [$this->sortField, $this->sortDirection === 'asc' ? SORT_ASC : SORT_DESC],
        ]);

        // manually paginate the sorted collection
        $currentPage = $this->page ?? 1;
        $items = $sorted->slice(($currentPage - 1) * $this->perPage, $this->perPage)->values();
        $paginated = new LengthAwarePaginator(
            $items,
            $sorted->count(),
            $this->perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Fetch upcoming arrivals & departures for the week (next 7 days, excluding today)
        $upcomingBookings = Booking::with('reservations.room')
            ->where(function ($q) use ($today) {
                $q->whereBetween('check_in', [Carbon::parse($today)->addDay()->startOfDay(), Carbon::parse($today)->addDays(7)->endOfDay()])
                  ->where('status', Booking::STATUS_PAID);
            })
            ->orWhere(function ($q) use ($today) {
                $q->whereBetween('check_out', [Carbon::parse($today)->addDay()->startOfDay(), Carbon::parse($today)->addDays(7)->endOfDay()])
                  ->where('status', Booking::STATUS_ACTIVE);
            })
            ->orderBy('check_in')
            ->limit(5)
            ->get()
            ->map(function ($b) use ($today) {
                $isArrival = $b->check_in && Carbon::parse($b->check_in)->isAfter(Carbon::parse($today)->endOfDay());
                $type = $isArrival ? 'arrival' : 'departure';
                
                $roomType = $b->reservations->first()?->room?->room_type ?? 'Room';
                $roomType = RoomCatalog::label($roomType);
                
                $dateStr = $isArrival 
                    ? 'Check-in ' . Carbon::parse($b->check_in)->format('M d')
                    : 'Check-out ' . Carbon::parse($b->check_out)->format('M d');

                $parts = explode(' ', trim($b->guest_name));
                $initials = '';
                if (count($parts) >= 2) {
                    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
                } else {
                    $initials = strtoupper(substr($b->guest_name, 0, 2));
                }

                return [
                    'guest_name' => $b->guest_name,
                    'initials' => $initials ?: 'G',
                    'type' => $type,
                    'details' => $roomType . ' · ' . $dateStr,
                    'status' => strtoupper($b->status),
                ];
            });

        // In-house on the viewed date: active stays spanning it.
        $inHouseCount = Booking::where('status', Booking::STATUS_ACTIVE)
            ->where('check_in', '<=', $this->date)
            ->where('check_out', '>=', $this->date)
            ->count();

        // Upcoming-this-week count (next 7 days after the viewed date).
        $weekStart = Carbon::parse($this->date)->addDay()->startOfDay();
        $weekEnd = Carbon::parse($this->date)->addDays(7)->endOfDay();
        $upcomingCount = Booking::where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('check_in', [$weekStart, $weekEnd])->where('status', Booking::STATUS_PAID);
            })
            ->orWhere(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('check_out', [$weekStart, $weekEnd])->where('status', Booking::STATUS_ACTIVE);
            })
            ->count();

        // ── Needs attention (relative to the real today, any viewed date) ─────
        $mapAttention = fn ($b, $kind) => (object) [
            'id' => $b->id,
            'guest_name' => $b->guest_name,
            'room_numbers_str' => $b->reservations->pluck('room_number')->unique()->implode(', ') ?: '—',
            'date' => $kind === 'overdue_checkout' ? $b->check_out : $b->check_in,
            'kind' => $kind,
        ];

        // Overdue check-outs: still active past their check-out date.
        $overdueCheckouts = Booking::with('reservations')
            ->where('status', Booking::STATUS_ACTIVE)
            ->where('check_out', '<', $actualToday)
            ->orderBy('check_out')
            ->limit(10)
            ->get()
            ->map(fn ($b) => $mapAttention($b, 'overdue_checkout'));

        // Missed arrivals: paid, check-in date passed, never checked in.
        $missedArrivals = Booking::with('reservations')
            ->where('status', Booking::STATUS_PAID)
            ->where('check_in', '<', $actualToday)
            ->orderBy('check_in')
            ->limit(10)
            ->get()
            ->map(fn ($b) => $mapAttention($b, 'missed_arrival'));

        return view('livewire.dashboard.arrivals-departures', [
            'arrivalsDepartures' => $paginated,
            'total' => $list->count(),
            'upcomingBookings' => $upcomingBookings,
            'isToday' => $isToday,
            'viewLabel' => Carbon::parse($this->date)->isSameDay(Carbon::today(config('hostel.timezone')))
                ? 'Today'
                : Carbon::parse($this->date)->format('M d, Y'),
            'arrivalsCount' => $arrivalsCount,
            'departuresCount' => $departuresCount,
            'inHouseCount' => $inHouseCount,
            'upcomingCount' => $upcomingCount,
            'overdueCheckouts' => $overdueCheckouts,
            'missedArrivals' => $missedArrivals,
        ]);
    }

    public function confirmCheckIn($bookingId)
    {   
        $staff = auth('staff')->user();
        $booking = Booking::with(['reservations.room', 'payments'])->find($bookingId);

        if (!$booking) {
            $this->dispatch('toast', type: 'error', message: 'Booking not found.');
            return;
        }

        // Eligibility. The reason is the message: a refusal the desk cannot act
        // on is the same as no answer at all.
        if ($blocker = $this->checkInBlocker($booking)) {
            $this->dispatch('toast', type: 'error', message: "Can't check in {$booking->guest_name} — " . lcfirst($blocker) . '.');
            return;
        }

        // Capture old values before update
        $oldValues = $booking->getOriginal();

        // Update booking status
        $booking->update(['status' => Booking::STATUS_ACTIVE]);

        // Update rooms
        foreach ($booking->reservations as $reservation) {
            $reservation->room->update(['status' => 'occupied']);
        }

        // Log check-in
        Checkin::create([
            'booking_id' => $booking->id,
            'checked_in_at' => Carbon::now(config('hostel.timezone')),
            'processed_by' => auth('staff')->id(),
        ]);

        // Log the check-in action
        AuditLogger::log(
            'booking_checked_in',
            $booking,
            ['status' => Booking::STATUS_PAID],  
            ['status' => Booking::STATUS_ACTIVE],
            "Booking #{$booking->id} checked in by {$staff->name}"
        );

        $roomList = $booking->reservations->pluck('room_number')->unique()->implode(', ');
        $this->dispatch(
            'toast',
            type: 'success',
            message: $roomList
                ? "{$booking->guest_name} checked in — room {$roomList} is now occupied."
                : "{$booking->guest_name} checked in."
        );
        $this->dispatch('refreshActiveBookings')->to(\App\Livewire\ActiveBookings::class);
        $this->dispatch('refreshBookingsTable')->to(\App\Livewire\BookingsTable::class);
        Realtime::emit(new RoomStatusChanged());
        Realtime::emit(new BookingChanged());
    }


    public function confirmCheckOut($bookingId)
    {   
        $staff = auth('staff')->user();
        //\Log::info('check out method received');
        $booking = Booking::with('reservations.room')->findOrFail($bookingId);

        // Allow today OR overdue (checkout date already passed) — the same
        // window the auto-checkout command uses. A stay that still has nights
        // left is not refused outright any more; it is the emergency
        // check-out's to end, and the panel offers that button instead.
        if ($booking->status !== Booking::STATUS_ACTIVE || !$this->isDueOut($booking)) {
            $this->dispatch('toast', type: 'error', message: 'Booking not eligible for check-out.');
            return;
        }

        $oldValues = $booking->getOriginal();

        // Update booking status
        $booking->update(['status' => Booking::STATUS_COMPLETED]);

        // Free rooms
        foreach ($booking->reservations as $reservation) {
            $reservation->room->update(['status' => 'available']);
        }

        // Log checkout
        Checkout::create([
            'booking_id' => $booking->id,
            'checked_out_at' => Carbon::now(config('hostel.timezone')),
            'method' => 'manual',
            'processed_by' => auth('staff')->id(),
        ]);

        // Log the check-in action
        AuditLogger::log(
            'booking_checked_out',
            $booking,
            ['status' => Booking::STATUS_ACTIVE],
            ['status' => Booking::STATUS_COMPLETED],
            "Booking #{$booking->id} checked out by {$staff->name}"
        );

        $roomList = $booking->reservations->pluck('room_number')->unique()->implode(', ');
        $this->dispatch(
            'toast',
            type: 'success',
            message: $roomList
                ? "{$booking->guest_name} checked out — room {$roomList} is free."
                : "{$booking->guest_name} checked out."
        );
        $this->dispatch('refreshActiveBookings')->to(\App\Livewire\ActiveBookings::class);
        $this->dispatch('refreshBookingsTable')->to(\App\Livewire\BookingsTable::class);
        Realtime::emit(new RoomStatusChanged());
        Realtime::emit(new BookingChanged());
    }

    /**
     * End a stay before its check-out date.
     *
     * The ordinary check-out deliberately refuses this: a guest who booked,
     * paid and checked in this morning for three nights is not due out, and
     * letting the desk close their stay by pressing the same button people
     * press all day would make an accident indistinguishable from a decision.
     * Emergencies happen anyway — someone is taken ill, a family member calls
     * — so the way out is a separate action that says what it is, records who
     * did it and why, and leaves the reason in the booking's timeline.
     *
     * It does NOT touch money. The nights the guest paid for and did not use
     * are a refund decision, and that belongs to whoever handles refunds.
     */
    public function confirmEmergencyCheckOut($bookingId, $reason = null)
    {
        $staff = auth('staff')->user();
        $booking = Booking::with('reservations.room')->find($bookingId);

        if (!$booking) {
            $this->dispatch('toast', type: 'error', message: 'Booking not found.');
            return;
        }

        // Only a stay actually under way, and only one that is not due out.
        // Once the check-out date arrives the ordinary check-out covers it and
        // there is no exception left to record.
        if ($booking->status !== Booking::STATUS_ACTIVE) {
            $this->dispatch('toast', type: 'error', message: 'Only a checked-in guest can be checked out early.');
            return;
        }

        if ($this->isDueOut($booking)) {
            $this->dispatch('toast', type: 'error', message: 'This stay is already due out — use the normal check-out.');
            return;
        }

        // The reason is the whole point of routing this away from the ordinary
        // check-out, so it is required here too and not only in the dialog.
        $reason = trim((string) $reason);
        if ($reason === '') {
            $this->dispatch('toast', type: 'error', message: 'An emergency check-out needs a reason.');
            return;
        }
        $reason = mb_substr($reason, 0, 255);

        // Read off the booking before it is closed, for the toast below.
        $dueOutOn = Carbon::parse($booking->check_out)->format('M d');

        DB::transaction(function () use ($booking, $reason) {
            $booking->update(['status' => Booking::STATUS_COMPLETED]);

            // Free rooms. The stay is over as far as the board is concerned,
            // so the remaining nights go back on sale.
            foreach ($booking->reservations as $reservation) {
                $reservation->room->update(['status' => 'available']);
            }

            Checkout::create([
                'booking_id' => $booking->id,
                'checked_out_at' => Carbon::now(config('hostel.timezone')),
                'method' => 'emergency',
                'reason' => $reason,
                'processed_by' => auth('staff')->id(),
            ]);
        });

        AuditLogger::log(
            'booking_checked_out_early',
            $booking,
            ['status' => Booking::STATUS_ACTIVE],
            ['status' => Booking::STATUS_COMPLETED],
            "Booking #{$booking->id} checked out early by {$staff->name} — {$reason}"
        );

        // Says what was skipped and, plainly, what was not done about it —
        // the desk should not walk away assuming the money sorted itself out.
        $this->dispatch(
            'toast',
            type: 'success',
            message: "Checked out early — was due out {$dueOutOn}. No refund has been made."
        );
        $this->dispatch('refreshActiveBookings')->to(\App\Livewire\ActiveBookings::class);
        $this->dispatch('refreshBookingsTable')->to(\App\Livewire\BookingsTable::class);
        Realtime::emit(new RoomStatusChanged());
        Realtime::emit(new BookingChanged());
        // The guest is being sent home ahead of their own booking, so their
        // booking page should not keep telling them the stay is under way.
        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(new BookingStatusChanged($booking->id, 'completed'));
        }
    }

    public function confirmNoShow($bookingId)
    {
        $staff = auth('staff')->user();
        $booking = Booking::with(['reservations.room', 'payments'])->find($bookingId);

        if (!$booking) {
            $this->dispatch('toast', type: 'error', message: 'Booking not found.');
            return;
        }

        // Eligibility checks. No-show is allowed for today OR a past check-in
        // (a missed arrival the scheduler hasn't swept yet); only a future
        // check-in is barred.
        $checkInInFuture = Carbon::parse($booking->check_in)->timezone(config('hostel.timezone'))->startOfDay()->gt(Carbon::today(config('hostel.timezone')));
        $paymentExists = $booking->payments !== null;
        $paymentStatus = $booking->payments->status ?? null;

        if ($booking->status === Booking::STATUS_ACTIVE) {
            $this->dispatch('toast', type: 'error', message: 'This guest has already checked in.');
            return;
        }

        if ($booking->status !== Booking::STATUS_PAID || !$paymentExists || $paymentStatus !== 'success') {
            $this->dispatch('toast', type: 'error', message: 'Only a paid, verified booking can be marked a no-show.');
            return;
        }

        if ($checkInInFuture) {
            $arrival = Carbon::parse($booking->check_in)->format('M d');
            $this->dispatch('toast', type: 'error', message: "Not due until {$arrival} — a guest cannot miss an arrival that has not happened yet.");
            return;
        }

        // Capture before the update, or the log records 'no_show' as its own
        // previous status.
        $previousStatus = $booking->status;
        $now = Carbon::now(config('hostel.timezone'));

        // Update booking status
        $booking->update(['status' => Booking::STATUS_NO_SHOW]);

        // The guest never arrived, so the rooms go back on the board.
        foreach ($booking->reservations as $reservation) {
            $reservation->room->update(['status' => 'available']);
        }

        // Log No Show
        NoShowLog::create([
            'booking_id' => $booking->id,
            'previous_status' => $previousStatus,
            'new_status' => Booking::STATUS_NO_SHOW,
            'reason' => 'Guest did not check in. Marked by Staff',
            'marked_at' => $now,
            'processed_by' => auth('staff')->id(),
        ]);

        AuditLogger::log(
            'booking_no_show',
            $booking,
            ['status' => $previousStatus],
            ['status' => Booking::STATUS_NO_SHOW],
            "Booking #{$booking->id} marked as no-show by {$staff->name}"
        );

        $this->dispatch(
            'toast',
            type: 'success',
            message: "{$booking->guest_name} marked as a no-show — the room is back on the board."
        );
        $this->dispatch('refreshBookingsTable')->to(\App\Livewire\BookingsTable::class);
        Realtime::emit(new RoomStatusChanged());
        Realtime::emit(new BookingChanged());
    }
}
