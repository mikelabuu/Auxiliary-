<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffDashboardController extends Controller
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

        // Monthly revenue (successful payments) for the same year
        $revenuePerMonth = Payment::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(amount) as total')
        )
        ->where('status', 'success')
        ->whereYear('created_at', $year)
        ->groupBy(DB::raw('MONTH(created_at)'))
        ->orderBy('month')
        ->get()
        ->keyBy('month');

        // format for chart
        $labels = [];
        $values = [];
        $revenueValues = [];
        $peakMonthName = 'None';
        $peakMonthCount = 0;

        for ($i = 1; $i <= 12; $i++) {
            $monthName = date('M', mktime(0, 0, 0, $i, 1));
            $count = $bookingsPerMonth[$i]->total ?? 0;
            $labels[] = $monthName;
            $values[] = $count;
            $revenueValues[] = (float) ($revenuePerMonth[$i]->total ?? 0);

            if ($count > $peakMonthCount) {
                $peakMonthCount = $count;
                $peakMonthName = date('F', mktime(0, 0, 0, $i, 1));
            }
        }

        // Stat cards + secondary strip moved to the live Livewire component
        // (App\Livewire\Dashboard\StatCards); only values the rest of this
        // page still renders are computed here.
        $totalRooms = Room::count();

        // Dates with at least one active/upcoming stay, for the calendar's
        // "has bookings" dots (window: 2 months back to 10 months ahead).
        $calendarStart = Carbon::now()->startOfMonth()->subMonths(2);
        $calendarEnd = Carbon::now()->startOfMonth()->addMonths(10);
        $bookedDates = Booking::whereIn('status', array_merge(Booking::BLOCKING_STATUSES, ['completed']))
            ->where('check_in', '<', $calendarEnd)
            ->where('check_out', '>', $calendarStart)
            ->get(['check_in', 'check_out'])
            ->flatMap(function ($b) use ($calendarStart, $calendarEnd) {
                $start = $b->check_in->max($calendarStart);
                $end = $b->check_out->min($calendarEnd);
                $dates = [];
                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    $dates[] = $d->toDateString();
                }
                return $dates;
            })
            ->unique()
            ->values();

        // Room Status Map Data — shared with the real-time roomMapFeed() endpoint.
        $rooms = $this->roomMapState();

        // Group rooms for the room map layout
        $dormBeds = $rooms->filter(function($r) {
            return in_array($r['room_type'], ['dormitory1', 'dormitory2']);
        })->sortBy('room_number')->values();

        $standardRooms = $rooms->filter(function($r) {
            return in_array($r['room_type'], ['double', 'triple', 'quadruple']);
        })->sortBy('room_number')->values();

        $deluxeRooms = $rooms->filter(function($r) {
            return $r['room_type'] === 'deluxe';
        })->sortBy('room_number')->values();

        // Global status counts
        $availableCount = $rooms->where('display_status', 'available')->count();
        $occupiedCount = $rooms->where('display_status', 'occupied')->count();
        $reservedCount = $rooms->where('display_status', 'reserved')->count();
        $cleaningCount = $rooms->where('display_status', 'cleaning')->count();
        $maintenanceCount = $rooms->where('display_status', 'maintenance')->count();

        // Recent activity moved to the live App\Livewire\Dashboard\RecentActivity component.

        return view('staff.dashboard.index', compact(
            'totalRooms',
            'labels',
            'values',
            'revenueValues',
            'peakMonthName',
            'peakMonthCount',
            'bookedDates',

            // Room Status Map variables
            'dormBeds',
            'standardRooms',
            'deluxeRooms',
            'availableCount',
            'occupiedCount',
            'reservedCount',
            'cleaningCount',
            'maintenanceCount'
        ));
    }

    /**
     * Per-room display status for the dashboard Room Status Map. Logic lives
     * in App\Support\RoomBoard so the page render, the roomMapFeed() endpoint,
     * and the live stat cards all share one definition.
     */
    private function roomMapState()
    {
        return \App\Support\RoomBoard::state();
    }

    /**
     * Lightweight JSON snapshot of the Room Status Map, pushed/polled to keep
     * the dashboard map live without a full page reload.
     */
    public function roomMapFeed()
    {
        $rooms = $this->roomMapState();

        return response()->json([
            'success' => true,
            'rooms'   => $rooms->map(fn ($r) => [
                'id' => $r['id'],
                'display_status' => $r['display_status'],
                'occupant' => $r['occupant'],
            ])->values(),
            'counts'  => [
                'available'   => $rooms->where('display_status', 'available')->count(),
                'occupied'    => $rooms->where('display_status', 'occupied')->count(),
                'reserved'    => $rooms->where('display_status', 'reserved')->count(),
                'cleaning'    => $rooms->where('display_status', 'cleaning')->count(),
                'maintenance' => $rooms->where('display_status', 'maintenance')->count(),
            ],
        ]);
    }
}
