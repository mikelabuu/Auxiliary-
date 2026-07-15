<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;
use App\Models\User;
use App\Models\Booking;
use App\Models\Discount;
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

        // Sparkline for the revenue stat card (Jan → current month, 70x24 viewBox)
        $spark = array_slice($revenueValues, 0, max(now()->month, 2));
        $sparkMax = max(max($spark), 1);
        $sparkCount = count($spark);
        $revenueSparkline = collect($spark)->map(function ($v, $i) use ($sparkMax, $sparkCount) {
            $x = $sparkCount > 1 ? round($i * (70 / ($sparkCount - 1)), 1) : 0;
            $y = round(21 - ($v / $sparkMax) * 18, 1);
            return "{$x},{$y}";
        })->implode(' ');

        $totalRooms = Room::count();
        $totalUsers = User::count();
        $totalBookings = Booking::count();
        
        $totalRevenue = Payment::where('status', 'success')->sum('amount');

        // New Users This Week
        $newUsersThisWeek = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // Percent Changes (Bookings & Revenue)
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        $lastMonth = Carbon::now()->subMonth()->month;
        $lastMonthYear = Carbon::now()->subMonth()->year;

        $bookingsThisMonth = Booking::whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->count();
        $bookingsLastMonth = Booking::whereYear('created_at', $lastMonthYear)->whereMonth('created_at', $lastMonth)->count();
        
        $bookingPercentChange = 0;
        if ($bookingsLastMonth > 0) {
            $bookingPercentChange = (($bookingsThisMonth - $bookingsLastMonth) / $bookingsLastMonth) * 100;
        } elseif ($bookingsThisMonth > 0) {
            $bookingPercentChange = 100; // went from 0 to something
        }

        $revenueThisMonth = Payment::where('status', 'success')->whereYear('created_at', $currentYear)->whereMonth('created_at', $currentMonth)->sum('amount');
        $revenueLastMonth = Payment::where('status', 'success')->whereYear('created_at', $lastMonthYear)->whereMonth('created_at', $lastMonth)->sum('amount');

        $revenuePercentChange = 0;
        if ($revenueLastMonth > 0) {
            $revenuePercentChange = (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100;
        } elseif ($revenueThisMonth > 0) {
            $revenuePercentChange = 100;
        }

        // Secondary Metrics Strip
        $checkinsThisWeek = DB::table('checkins')->where('checked_in_at', '>=', Carbon::now()->subDays(7))->count();
        $checkoutsThisWeek = DB::table('checkouts')->where('checked_out_at', '>=', Carbon::now()->subDays(7))->count();
        $roomsUnderMaintenance = Room::where('status', 'maintenance')->count();
        $pendingDiscounts = Discount::where('status', 'pending')->count();

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

        // Occupancy Breakdown percentages
        $dormTotal = Room::whereIn('room_type', ['dormitory1', 'dormitory2'])->count();
        $dormOccupied = Room::whereIn('room_type', ['dormitory1', 'dormitory2'])->where('status', 'occupied')->count();
        $dormPercent = $dormTotal > 0 ? round(($dormOccupied / $dormTotal) * 100) : 0;

        $standardTotal = Room::whereIn('room_type', ['double', 'triple', 'quadruple'])->count();
        $standardOccupied = Room::whereIn('room_type', ['double', 'triple', 'quadruple'])->where('status', 'occupied')->count();
        $standardPercent = $standardTotal > 0 ? round(($standardOccupied / $standardTotal) * 100) : 0;

        $deluxeTotal = Room::where('room_type', 'deluxe')->count();
        $deluxeOccupied = Room::where('room_type', 'deluxe')->where('status', 'occupied')->count();
        $deluxePercent = $deluxeTotal > 0 ? round(($deluxeOccupied / $deluxeTotal) * 100) : 0;

        // Occupancy circle calculations
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $occupancyPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        // Recent activity query from audit logs
        $recentActivities = DB::table('audit_logs')
            ->leftJoin('staff', 'audit_logs.staff_id', '=', 'staff.id')
            ->select('audit_logs.*', 'staff.name as staff_name')
            ->orderBy('audit_logs.created_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($log) {
                $icon = 'check_circle';
                $colorClass = 'bg-clsu-50 text-clsu-700';
                
                $action = strtolower($log->action);
                $target = strtolower($log->target_type);
                
                if (str_contains($action, 'booking') || $target === 'booking' || $target === 'reservation') {
                    $icon = 'receipt';
                    $colorClass = 'bg-clsu-50 text-clsu-700';
                } elseif (str_contains($action, 'user') || str_contains($action, 'staff') || $target === 'user' || $target === 'staff') {
                    $icon = 'person';
                    $colorClass = 'bg-palay-50 text-palay-700';
                } elseif (str_contains($action, 'room') || $target === 'room') {
                    $icon = 'bed';
                    $colorClass = 'bg-stone-100 text-stone-500';
                }
                
                return [
                    'description' => $log->description ?: "Action '{$log->action}' executed",
                    'created_at' => Carbon::parse($log->created_at)->diffForHumans(),
                    'icon' => $icon,
                    'color_class' => $colorClass,
                ];
            });

        // Fallback for demo/empty state
        if ($recentActivities->isEmpty()) {
            $recentActivities = collect([
                [
                    'description' => 'System initialized · 22 rooms added',
                    'created_at' => '1 week ago',
                    'icon' => 'settings',
                    'color_class' => 'bg-stone-100 text-stone-500',
                ]
            ]);
        }

        return view('staff.dashboard.index', compact(
            'totalRooms', 
            'totalUsers', 
            'totalBookings',
            'labels',
            'values',
            'revenueValues',
            'revenueSparkline',
            'totalRevenue',
            'peakMonthName',
            'peakMonthCount',
            'newUsersThisWeek',
            'bookingPercentChange',
            'revenuePercentChange',
            
            // New Dashboard variables
            'checkinsThisWeek',
            'checkoutsThisWeek',
            'roomsUnderMaintenance',
            'pendingDiscounts',
            'bookedDates',
            
            // Room Status Map variables
            'dormBeds',
            'standardRooms',
            'deluxeRooms',
            'availableCount',
            'occupiedCount',
            'reservedCount',
            'cleaningCount',
            'maintenanceCount',
            
            // Occupancy variables
            'occupancyPercent',
            'occupiedRooms',
            'dormTotal',
            'dormOccupied',
            'dormPercent',
            'standardTotal',
            'standardOccupied',
            'standardPercent',
            'deluxeTotal',
            'deluxeOccupied',
            'deluxePercent',
            
            // Recent activity
            'recentActivities'
        ));
    }

    /**
     * Per-room display status for the dashboard Room Status Map. Extracted so
     * the initial page render and the real-time roomMapFeed() endpoint stay in
     * lock-step. 'reserved' = a paid/confirmed booking with a future check-in.
     */
    private function roomMapState()
    {
        $reservedRoomIds = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', ['paid', 'confirmed'])
            ->where('bookings.check_in', '>', Carbon::now())
            ->pluck('booking_room.room_id')
            ->toArray();

        // Who is actually in each room. The map used to show status with no way
        // to tell WHO a room was occupied by; this also exposes any room flagged
        // occupied with no booking behind it.
        $today = Carbon::today();

        $stays = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
            ->whereDate('bookings.check_out', '>=', $today)
            ->orderBy('bookings.check_in')
            ->get(['booking_room.room_id', 'bookings.guest_name', 'bookings.check_in', 'bookings.check_out']);

        $currentByRoom = [];
        $nextByRoom = [];
        foreach ($stays as $stay) {
            $checkIn = Carbon::parse($stay->check_in);
            $checkOut = Carbon::parse($stay->check_out);

            if ($checkIn->lte($today) && $checkOut->gte($today)) {
                $currentByRoom[$stay->room_id] ??= $stay->guest_name . ' · until ' . $checkOut->format('M d');
            } elseif ($checkIn->gt($today)) {
                $nextByRoom[$stay->room_id] ??= $stay->guest_name . ' · arrives ' . $checkIn->format('M d');
            }
        }

        return Room::all()->map(function ($room) use ($reservedRoomIds, $currentByRoom, $nextByRoom) {
            if ($room->status === 'maintenance') {
                $displayStatus = 'maintenance';
            } elseif ($room->status === 'cleaning') {
                // Without this branch a room being cleaned fell through to the
                // else and showed as Available — green and bookable-looking.
                $displayStatus = 'cleaning';
            } elseif ($room->status === 'occupied') {
                $displayStatus = 'occupied';
            } elseif (in_array($room->id, $reservedRoomIds)) {
                $displayStatus = 'reserved';
            } else {
                $displayStatus = 'available';
            }

            $current = $currentByRoom[$room->id] ?? null;
            $next = $nextByRoom[$room->id] ?? null;

            if ($displayStatus === 'occupied') {
                // No booking behind an occupied room means it was flagged by
                // hand — say so rather than imply a guest exists.
                $occupant = $current ?: 'No guest on record';
            } elseif ($displayStatus === 'reserved') {
                $occupant = $next;
            } else {
                $occupant = $current ?: $next;
            }

            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->room_type,
                'status' => $room->status,
                'display_status' => $displayStatus,
                'occupant' => $occupant,
            ];
        });
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
