<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Models\Booking;
use App\Models\Checkin;
use App\Models\Checkout;
use App\Models\NoShowLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Services\AuditLogger;
use App\Events\RoomStatusChanged;
use App\Events\BookingChanged;
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
        $this->date = $date ?? Carbon::today('Asia/Manila')->toDateString();
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
        $this->date = Carbon::today('Asia/Manila')->toDateString();
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
        } elseif ($action === 'noshow'){
            $this->confirmNoShow($bookingId);
        }
    }
    public function render()
    {
        $today = $this->date;

        // get arrivals & departures
        $bookings = Booking::with('reservations')
            ->where(function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->whereDate('check_in', $today)
                        ->where('status', 'paid');
                })
                ->orWhere(function ($q3) use ($today) {
                    $q3->whereDate('check_out', $today)
                        ->where('status', 'active');
                });
            })
            ->get();

        $list = $bookings->map(function ($b) use ($today) {
            $isArrival = $b->check_in && Carbon::parse($b->check_in)->isSameDay(Carbon::parse($today));
            $isDeparture = $b->check_out && Carbon::parse($b->check_out)->isSameDay(Carbon::parse($today));

            $type = null;
            if ($isArrival && $b->status === 'paid') $type = 'arrival';
            if ($isDeparture && $b->status === 'active') $type = $type ? 'both' : 'departure';

            return (object) [
                'id' => $b->id,
                'guest_name' => $b->guest_name,
                'check_in' => $b->check_in,
                'check_out' => $b->check_out,
                'nights' => max(1, Carbon::parse($b->check_in)->diffInDays(Carbon::parse($b->check_out))),
                'room_numbers_str' => $b->reservations->pluck('room_number')->unique()->implode(', ') ?: '—',
                'status' => $b->status,
                'type' => $type,
                'detail_url' => route('staff.bookings.index'),
            ];
        });

        // Summary counts for the header strip — taken before the tab filter so
        // they reflect the whole day, not the current view.
        $arrivalsCount = $list->whereIn('type', ['arrival', 'both'])->count();
        $departuresCount = $list->whereIn('type', ['departure', 'both'])->count();

        // Apply filter
        if ($this->filterType !== 'all') {
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
                  ->where('status', 'paid');
            })
            ->orWhere(function ($q) use ($today) {
                $q->whereBetween('check_out', [Carbon::parse($today)->addDay()->startOfDay(), Carbon::parse($today)->addDays(7)->endOfDay()])
                  ->where('status', 'active');
            })
            ->orderBy('check_in')
            ->limit(5)
            ->get()
            ->map(function ($b) use ($today) {
                $isArrival = $b->check_in && Carbon::parse($b->check_in)->isAfter(Carbon::parse($today)->endOfDay());
                $type = $isArrival ? 'arrival' : 'departure';
                
                $roomType = $b->reservations->first()?->room?->room_type ?? 'Room';
                $roomType = ucfirst(str_replace(['dormitory1', 'dormitory2'], 'Dormitory', $roomType));
                
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

        // Is the panel showing the real "today"? Actions are only offered then.
        $actualToday = Carbon::today('Asia/Manila')->toDateString();
        $isToday = $this->date === $actualToday;

        // In-house on the viewed date: active stays spanning it.
        $inHouseCount = Booking::where('status', 'active')
            ->whereDate('check_in', '<=', $this->date)
            ->whereDate('check_out', '>=', $this->date)
            ->count();

        // Upcoming-this-week count (next 7 days after the viewed date).
        $weekStart = Carbon::parse($this->date)->addDay()->startOfDay();
        $weekEnd = Carbon::parse($this->date)->addDays(7)->endOfDay();
        $upcomingCount = Booking::where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('check_in', [$weekStart, $weekEnd])->where('status', 'paid');
            })
            ->orWhere(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('check_out', [$weekStart, $weekEnd])->where('status', 'active');
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
            ->where('status', 'active')
            ->whereDate('check_out', '<', $actualToday)
            ->orderBy('check_out')
            ->limit(10)
            ->get()
            ->map(fn ($b) => $mapAttention($b, 'overdue_checkout'));

        // Missed arrivals: paid, check-in date passed, never checked in.
        $missedArrivals = Booking::with('reservations')
            ->where('status', 'paid')
            ->whereDate('check_in', '<', $actualToday)
            ->orderBy('check_in')
            ->limit(10)
            ->get()
            ->map(fn ($b) => $mapAttention($b, 'missed_arrival'));

        return view('livewire.dashboard.arrivals-departures', [
            'arrivalsDepartures' => $paginated,
            'total' => $list->count(),
            'upcomingBookings' => $upcomingBookings,
            'isToday' => $isToday,
            'viewLabel' => Carbon::parse($this->date)->isSameDay(Carbon::today('Asia/Manila'))
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

        // Eligibility checks
        $checkInToday = Carbon::parse($booking->check_in)->timezone('Asia/Manila')->isToday();
        $paymentExists = $booking->payments !== null;
        $paymentStatus = $booking->payments->status ?? null;

        if ($booking->status !== 'paid' || !$checkInToday || !$paymentExists || $paymentStatus !== 'success') {
            $this->dispatch('toast', type: 'error', message: 'Booking not eligible for check-in.');
            return;
        }

        // Capture old values before update
        $oldValues = $booking->getOriginal();

        // Update booking status
        $booking->update(['status' => 'active']);

        // Update rooms
        foreach ($booking->reservations as $reservation) {
            $reservation->room->update(['status' => 'occupied']);
        }

        // Log check-in
        Checkin::create([
            'booking_id' => $booking->id,
            'checked_in_at' => Carbon::now('Asia/Manila'),
            'processed_by' => auth('staff')->id(),
        ]);

        // Log the check-in action
        AuditLogger::log(
            'booking_checked_in',
            $booking,
            ['status' => 'paid'],  
            ['status' => 'active'],
            "Booking #{$booking->id} checked in by {$staff->name}"
        );

        $this->dispatch('toast', type: 'success', message: 'Check-in successful!');
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
        // window the auto-checkout command uses. Only future checkouts are barred.
        $checkOutInFuture = Carbon::parse($booking->check_out)->timezone('Asia/Manila')->startOfDay()->gt(Carbon::today('Asia/Manila'));
        if ($booking->status !== 'active' || $checkOutInFuture) {
            $this->dispatch('toast', type: 'error', message: 'Booking not eligible for check-out.');
            return;
        }

        $oldValues = $booking->getOriginal();

        // Update booking status
        $booking->update(['status' => 'completed']);

        // Free rooms
        foreach ($booking->reservations as $reservation) {
            $reservation->room->update(['status' => 'available']);
        }

        // Log checkout
        Checkout::create([
            'booking_id' => $booking->id,
            'checked_out_at' => Carbon::now('Asia/Manila'),
            'method' => 'manual',
            'processed_by' => auth('staff')->id(),
        ]);

        // Log the check-in action
        AuditLogger::log(
            'booking_checked_out',
            $booking,
            ['status' => 'active'],
            ['status' => 'completed'],
            "Booking #{$booking->id} checked out by {$staff->name}"
        );

        $this->dispatch('toast', type: 'success', message: 'Check-out successful!');
        $this->dispatch('refreshActiveBookings')->to(\App\Livewire\ActiveBookings::class);
        $this->dispatch('refreshBookingsTable')->to(\App\Livewire\BookingsTable::class);
        Realtime::emit(new RoomStatusChanged());
        Realtime::emit(new BookingChanged());
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
        $checkInInFuture = Carbon::parse($booking->check_in)->timezone('Asia/Manila')->startOfDay()->gt(Carbon::today('Asia/Manila'));
        $paymentExists = $booking->payments !== null;
        $paymentStatus = $booking->payments->status ?? null;

        if ($booking->status !== 'paid' || $checkInInFuture || !$paymentExists || $paymentStatus !== 'success') {
            $this->dispatch('toast', type: 'error', message: 'Booking not eligible for No Show.');
            return;
        }

        // Capture before the update, or the log records 'no_show' as its own
        // previous status.
        $previousStatus = $booking->status;
        $now = Carbon::now('Asia/Manila');

        // Update booking status
        $booking->update(['status' => 'no_show']);

        // The guest never arrived, so the rooms go back on the board.
        foreach ($booking->reservations as $reservation) {
            $reservation->room->update(['status' => 'available']);
        }

        // Log No Show
        NoShowLog::create([
            'booking_id' => $booking->id,
            'previous_status' => $previousStatus,
            'new_status' => 'no_show',
            'reason' => 'Guest did not check in. Marked by Staff',
            'marked_at' => $now,
            'processed_by' => auth('staff')->id(),
        ]);

        AuditLogger::log(
            'booking_no_show',
            $booking,
            ['status' => $previousStatus],
            ['status' => 'no_show'],
            "Booking #{$booking->id} marked as no-show by {$staff->name}"
        );

        $this->dispatch('toast', type: 'success', message: 'No Show successful!');
        $this->dispatch('refreshBookingsTable')->to(\App\Livewire\BookingsTable::class);
        Realtime::emit(new RoomStatusChanged());
        Realtime::emit(new BookingChanged());
    }
}
