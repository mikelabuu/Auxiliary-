<?php

namespace App\Http\Controllers\Staff\frontdesk;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class FrontDeskDashboardController extends Controller
{
    public function index()
    {
        $year = now()->year;

        $bookingsPerMonth = Payment::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
        ->where('status', 'success')
        ->whereYear('created_at', $year)
        ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
        ->orderBy('month')
        ->get()
        ->keyBy('month');

        // format for chart
        $labels = [];
        $values = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = date('F', mktime(0, 0, 0, $i, 1)); // month name
            $values[] = $bookingsPerMonth[$i]->total ?? 0;   // 0 if no bookings
        }

        // Desk-day KPIs: the desk lives in Manila time while the app clock
        // may be UTC, so "today" is pinned to Asia/Manila.
        $manilaToday = now('Asia/Manila')->toDateString();

        $arrivalsToday = Booking::whereDate('check_in', $manilaToday)
            ->whereIn('status', Booking::BLOCKING_STATUSES)
            ->count();

        $departuresToday = Booking::whereDate('check_out', $manilaToday)
            ->where('status', 'active')
            ->count();

        $inHouse = Booking::where('status', 'active')->count();

        $totalRooms = Room::count();
        $availableTonight = Room::where('status', 'available')->count();

        // Payments recorded during Manila's today (created_at is stored in
        // the app timezone, so convert the Manila day bounds before querying)
        $dayStart = now('Asia/Manila')->startOfDay()->setTimezone(config('app.timezone'));
        $dayEnd = now('Asia/Manila')->endOfDay()->setTimezone(config('app.timezone'));
        $collectedToday = Payment::where('status', 'success')
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->sum('amount');

        return view('staff.frontdesk.dashboard.index', compact(
            'labels',
            'values',
            'arrivalsToday',
            'departuresToday',
            'inHouse',
            'totalRooms',
            'availableTonight',
            'collectedToday',
        ));
    }
}
