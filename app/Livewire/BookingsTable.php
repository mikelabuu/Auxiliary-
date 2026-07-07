<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Booking;
use App\Models\Checkout;
use App\Models\CancellationLog;
use Carbon\Carbon;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $dateFilter = '';
    public $selectedBooking = null;

    protected $paginationTheme = 'tailwind';

    // Allow deep links like /bookings?search=123 (used by the topbar global search)
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => '', 'as' => 'status'],
    ];

    protected $listeners = [
        'refreshBookingsTable' => '$refresh',
        'openBookingModal' => 'selectBooking',
        'cancelBookingConfirmed' => 'cancelBooking',
        'checkoutBookingConfirmed' => 'checkoutBooking',
    ];

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
    // Open modal with booking details
    public function selectBooking($bookingId)
    {
        $this->selectedBooking = Booking::with('reservations.room', 'payments')->find($bookingId);

        if ($this->selectedBooking) {
            $staff = auth('staff')->user();

            AuditLogger::log(
                'view_booking_modal', 
                $this->selectedBooking, 
                null, 
                null, 
                "Admin {$staff->name} viewed booking #{$this->selectedBooking->id} in modal"
            );
        }
    }
    // Close modal
    public function closeModal()
    {
        $this->selectedBooking = null;
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
            session()->flash('success', "Booking #{$booking->id} cancelled.");
        }
    }

    public function checkoutBooking($bookingId)
    {

        $staff = auth('staff')->user();
        $booking = Booking::with('reservations.room')->find($bookingId);

        if (!$booking) {
            session()->flash('error', 'Booking not found.');
            return;
        }

        
        DB::transaction(function () use ($booking, $staff) {

            $booking->update(['status' => 'completed']);

            foreach ($booking->reservations as $reservation) {
                $reservation->room->update(['status' => 'available']);
            }

            Checkout::create([
                'booking_id' => $booking->id,
                'checked_out_at' => Carbon::now('Asia/Manila'),
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

        session()->flash('success', "Booking #{$booking->id} checked out.");
    }

    public function render()
    {
        $query = Booking::with('reservations.room')
            ->where('status', '!=', 'completed');

        //  Search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('guest_name', 'like', "%{$this->search}%")
                    ->orWhere('id', $this->search)
                    ->orWhereHas('reservations', function ($qr) {
                        $qr->where('room_number', 'like', "%{$this->search}%");
                    });
            });
        }

        //  Status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        //  Date-based filter
        if (!empty($this->dateFilter)) {
            $today = Carbon::today('Asia/Manila');
            $tomorrow = Carbon::tomorrow('Asia/Manila');

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

        //  Paginate results
        $bookings = $query->latest()->paginate(15);

        return view('livewire.bookings-table', [
            'bookings' => $bookings,
        ]);
    }
}
