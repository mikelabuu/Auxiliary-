<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithSorting;
use App\Models\Booking;
use App\Models\Checkout;
use App\Models\CancellationLog;
use Carbon\Carbon;
use App\Services\AuditLogger;
use App\Events\RoomStatusChanged;
use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Support\Realtime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingsTable extends Component
{
    use WithPagination;
    use WithSorting;

    public $search = '';
    public $statusFilter = '';
    public $dateFilter = '';

    protected $paginationTheme = 'tailwind';

    // Allow deep links like /bookings?search=123 (used by the topbar global search)
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => '', 'as' => 'status'],
        'dateFilter' => ['except' => '', 'as' => 'date'],
    ];

    /**
     * `openBookingModal` used to live here, mapped to a selectBooking() that
     * parked a full Booking model (reservations.room + payments eager-loaded)
     * in a public property. Livewire re-serialises public properties on every
     * request, and this component polls every 15 seconds — so opening a
     * read-only dialog made the whole table, its status counts and that model
     * graph round-trip four times a minute for as long as it stayed open.
     *
     * The modal is fetched directly now (window.openBookingDetail, defined in
     * layouts/admin), from the same partial, without involving this component
     * at all. Only the two actions that genuinely mutate state remain.
     */
    protected $listeners = [
        'refreshBookingsTable' => '$refresh',
        'cancelBookingConfirmed' => 'cancelBooking',
        'checkoutBookingConfirmed' => 'checkoutBooking',
    ];

    // Clear every active filter at once (toolbar "Clear filters" button)
    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'dateFilter']);
        $this->resetPage();
    }

    // Status pills are radio-like: the "All" pill is the way back to unfiltered.
    public function setStatus($status)
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    // Quick filters have no "off" pill of their own, so clicking the active one
    // clears it — otherwise the only escape is wiping the search box too.
    public function toggleDate($date)
    {
        $this->dateFilter = $this->dateFilter === $date ? '' : $date;
        $this->resetPage();
    }

    public function toggleStatus($status)
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
        $this->resetPage();
    }

    // Reset pagination whenever filters or search change
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    public function updatingDateFilter()
    {
        $this->resetPage();
    }
    public function cancelBooking($bookingId)
    {   
        $staff = Auth::guard('staff')->user();
        $booking = Booking::find($bookingId);
        if ($booking && $booking->status == 'pending_payment') {
            $booking->status = 'cancelled';
            $booking->save();

            foreach ($booking->reservations as $reservation) {
                $reservation->room->status = 'available';
                $reservation->room->save();
            }

            CancellationLog::create([
                'booking_id' => $booking->id,
                'cancelled_at' => now(),
                'cancelled_by' => $staff->name,
                'reason' => 'cancelled by staff'
            ]);

            AuditLogger::log(
                'booking_cancelled',
                $booking,
                ['status' => 'pending_payment'],
                ['status' => 'cancelled'],
                "Booking #{$booking->id} was cancelled by {$staff->name}"
            );
            $this->dispatch('toast', message: "Booking #{$booking->id} cancelled.", type: 'success');
            $this->dispatch('refreshActiveBookings')->to(\App\Livewire\ActiveBookings::class);
            Realtime::emit(new RoomStatusChanged());
            Realtime::emit(new BookingChanged());
            // A staff cancellation is not something the guest initiated, so
            // push it to their booking page too.
            if (BookingStatusChanged::shouldEmitFor($booking)) {
                Realtime::emit(new BookingStatusChanged($booking->id, 'cancelled'));
            }
        }
    }

    public function checkoutBooking($bookingId)
    {

        $staff = auth('staff')->user();
        $booking = Booking::with('reservations.room')->find($bookingId);

        if (!$booking) {
            $this->dispatch('toast', message: 'Booking not found.', type: 'error');
            return;
        }

        
        DB::transaction(function () use ($booking, $staff) {

            $booking->update(['status' => 'completed']);

            foreach ($booking->reservations as $reservation) {
                $reservation->room->update(['status' => 'available']);
            }

            Checkout::create([
                'booking_id' => $booking->id,
                'checked_out_at' => Carbon::now(config('hostel.timezone')),
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

        $this->dispatch('toast', message: "Booking #{$booking->id} checked out.", type: 'success');
        $this->dispatch('refreshActiveBookings')->to(\App\Livewire\ActiveBookings::class);
        Realtime::emit(new RoomStatusChanged());
        Realtime::emit(new BookingChanged());
        if (BookingStatusChanged::shouldEmitFor($booking)) {
            Realtime::emit(new BookingStatusChanged($booking->id, 'completed'));
        }
    }

    // Search + date, but deliberately not the status pill — the pill counts are
    // built from this so each pill shows what clicking it would actually return.
    protected function baseQuery()
    {
        $query = Booking::where('status', '!=', 'completed');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('guest_name', 'like', "%{$this->search}%")
                    ->orWhere('id', $this->search)
                    ->orWhereHas('reservations', function ($qr) {
                        $qr->where('room_number', 'like', "%{$this->search}%");
                    });
            });
        }

        if (!empty($this->dateFilter)) {
            $today = Carbon::today(config('hostel.timezone'));
            $tomorrow = Carbon::tomorrow(config('hostel.timezone'));

            switch ($this->dateFilter) {
                case 'today_checkin':
                    $query->whereDate('check_in', $today);
                    break;

                case 'tomorrow_checkin':
                    $query->whereDate('check_in', $tomorrow);
                    break;

                case 'today_checkout':
                    $query->whereDate('check_out', $today);
                    break;

                case 'tomorrow_checkout':
                    $query->whereDate('check_out', $tomorrow);
                    break;
            }
        }

        return $query;
    }

    public function render()
    {
        $statusCounts = $this->baseQuery()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $query = $this->baseQuery()->with('reservations.room');

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $query = $this->applySort(
            $query,
            ['id', 'guest_name', 'check_in', 'check_out', 'status', 'created_at'],
            fn ($q) => $q->latest()
        );

        return view('livewire.bookings-table', [
            'bookings' => $query->paginate(15),
            'statusCounts' => $statusCounts,
        ]);
    }
}
