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
        //\Log::info('[ArrivalsDepartures] Poll triggered at ' . now());

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
                'room_numbers_str' => $b->reservations->pluck('room_number')->unique()->implode(', '),
                'status' => $b->status,
                'type' => $type,
                'detail_url' => route('staff.bookings.index'),
            ];
        });

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

        return view('livewire.dashboard.arrivals-departures', [
            'arrivalsDepartures' => $paginated,
            'total' => $list->count(),
        ]);
    }

    public function confirmCheckIn($bookingId)
    {   
        $staff = auth('staff')->user();
        $booking = Booking::with(['reservations.room', 'payments'])->find($bookingId);

        if (!$booking) {
            $this->dispatch('alert', type: 'error', message: 'Booking not found.');
            return;
        }

        // Eligibility checks
        $checkInToday = Carbon::parse($booking->check_in)->timezone('Asia/Manila')->isToday();
        $paymentExists = $booking->payments !== null;
        $paymentStatus = $booking->payments->status ?? null;

        if ($booking->status !== 'paid' || !$checkInToday || !$paymentExists || $paymentStatus !== 'success') {
            $this->dispatch('alert', type: 'error', message: 'Booking not eligible for check-in.');
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

        $this->dispatch('alert', type: 'success', message: 'Check-in successful!');
        $this->dispatch('refreshActiveBookings')->to(\App\Livewire\ActiveBookings::class);
    }


    public function confirmCheckOut($bookingId)
    {   
        $staff = auth('staff')->user();
        //\Log::info('check out method received');
        $booking = Booking::with('reservations.room')->findOrFail($bookingId);

        if ($booking->status !== 'active' || !Carbon::parse($booking->check_out)->timezone('Asia/Manila')->isToday()) {
            $this->dispatch('alert', type: 'error', message: 'Booking not eligible for check-out.');
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
            ['status' => 'paid'],
            ['status' => 'active'],
            "Booking #{$booking->id} checked out by {$staff->name}"
        );

        $this->dispatch('alert', type: 'success', message: 'Check-out successful!');
        $this->dispatch('refreshActiveBookings')->to(\App\Livewire\ActiveBookings::class);
    }

    public function confirmNoShow($bookingId)
    {   
        $staff = auth('staff')->user();
        $booking = Booking::with(['reservations.room', 'payments'])->find($bookingId);

        if (!$booking) {
            $this->dispatch('alert', type: 'error', message: 'Booking not found.');
            return;
        }

        // Eligibility checks
        $checkInToday = Carbon::parse($booking->check_in)->timezone('Asia/Manila')->isToday();
        $paymentExists = $booking->payments !== null;
        $paymentStatus = $booking->payments->status ?? null;

        if ($booking->status !== 'paid' || !$checkInToday || !$paymentExists || $paymentStatus !== 'success') {
            $this->dispatch('alert', type: 'error', message: 'Booking not eligible for No Show.');
            return;
        }

        // Update booking status
        $booking->update(['status' => 'no_show']);

        // Update rooms
        foreach ($booking->reservations as $reservation) {
            $reservation->room->update(['status' => 'occupied']);
        }

        $previousStatus = $booking->status;
        $now = Carbon::now('Asia/Manila');

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
            'booking_checked_out',
            $booking,
            ['status' => 'paid'],
            ['status' => 'no_show'],
            "Booking #{$booking->id} marked by {$staff->name}"
        );

        $this->dispatch('alert', type: 'success', message: 'No Show successful!');
    }
}
