<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Booking;

/**
 * The stat pills in the Bookings Hub header. These used to be plain PHP in the
 * page body, so they were frozen at page load while the tables under them kept
 * updating. As a component they poll and follow the same BookingChanged push
 * the tables listen to.
 */
class BookingOpsStats extends Component
{
    protected $listeners = ['refreshBookingOpsStats' => '$refresh'];

    public function render()
    {
        $counts = Booking::selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $arrivingToday = Booking::where('check_in', Carbon::today(config('hostel.timezone'))->toDateString())
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show', 'completed'])
            ->count();

        return view('livewire.dashboard.booking-ops-stats', [
            'visibleNow' => $counts->sum() - ($counts['completed'] ?? 0),
            'inProcess' => ($counts['pending_payment'] ?? 0) + ($counts['pending_discount'] ?? 0),
            'activeStays' => $counts['active'] ?? 0,
            'arrivingToday' => $arrivingToday,
        ]);
    }
}
