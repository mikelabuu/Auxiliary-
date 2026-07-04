<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;
use App\Models\User;
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

        // format for chart
        $labels = [];
        $values = [];
        $peakMonthName = 'None';
        $peakMonthCount = 0;
        
        for ($i = 1; $i <= 12; $i++) {
            $monthName = date('M', mktime(0, 0, 0, $i, 1));
            $count = $bookingsPerMonth[$i]->total ?? 0;
            $labels[] = $monthName;
            $values[] = $count;
            
            if ($count > $peakMonthCount) {
                $peakMonthCount = $count;
                $peakMonthName = date('F', mktime(0, 0, 0, $i, 1));
            }
        }

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

        // Room Status Map Data
        $reservedRoomIds = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', ['paid', 'confirmed'])
            ->where('bookings.check_in', '>', Carbon::now())
            ->pluck('booking_room.room_id')
            ->toArray();

        $rooms = Room::all()->map(function ($room) use ($reservedRoomIds) {
            if ($room->status === 'maintenance') {
                $displayStatus = 'maintenance';
            } elseif ($room->status === 'occupied') {
                $displayStatus = 'occupied';
            } elseif (in_array($room->id, $reservedRoomIds)) {
                $displayStatus = 'reserved';
            } else {
                $displayStatus = 'available';
            }

            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->room_type,
                'status' => $room->status,
                'display_status' => $displayStatus,
            ];
        });

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
            
            // Room Status Map variables
            'dormBeds',
            'standardRooms',
            'deluxeRooms',
            'availableCount',
            'occupiedCount',
            'reservedCount',
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
}
