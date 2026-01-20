<?php

namespace App\Http\Controllers\Staff\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\CentralBookingsExport;
use App\Exports\CentralRevenueExport;
use App\Exports\CentralOccupancyExport;
use App\Exports\CentralPaymentsExport;
use App\Exports\CentralDiscountsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Room;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class CentralHubController extends Controller
{
    public function index(Request $request)
    {   
        // --- Date filters (default last 1 year) ---
        $from = $request->from_date ?? now()->subYear()->format('Y-m-d');
        $to = $request->to_date ?? now()->format('Y-m-d');

        // --- 1. Bookings per month ---
        $bookingsPerMonth = \App\Models\Booking::where('status', 'paid')
            ->whereBetween('check_in', [$from, $to])
            ->selectRaw('MONTH(check_in) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // --- 2. Revenue per month ---
        $revenuePerMonth = \App\Models\Booking::where('status', 'paid')
            ->whereBetween('check_in', [$from, $to])
            ->selectRaw('MONTH(check_in) as month, SUM(payable_amount) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // --- 3. Payments by method ---
        $paymentsByMethod = \App\Models\Payment::whereBetween('created_at', [$from, $to])
            ->selectRaw('gateway, COUNT(*) as total')
            ->groupBy('gateway')
            ->get();

        // --- 4. Discounts summary ---
        $discountsSummary = \App\Models\Discount::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        // --- 5. Top booked room types ---
        $topRoomsChart = \App\Models\Room::join('reservations', 'reservations.room_number', '=', 'rooms.room_number')
            ->selectRaw('rooms.room_type, COUNT(*) as total')
            ->groupBy('rooms.room_type')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // --- 6. Discount approvals trend (monthly) ---
        $discountTrend = \DB::table('discounts')
            ->selectRaw('MONTH(reviewed_at) AS month, COUNT(*) AS total')
            ->where('status', 'approved')
            ->groupByRaw('MONTH(reviewed_at)')
            ->orderBy('month')
            ->get();
            
        // --- 7. Cancelled booking rate ---
        $cancelledRate = \App\Models\Booking::whereBetween('check_in', [$from, $to])
            ->selectRaw('SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END)/COUNT(*)*100 as rate')
            ->value('rate') ?? 0;

        // --- 8. Average occupancy rate ---
        $avgOccupancy = \DB::table('reservations')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('SUM(num_guests)/COUNT(id)*100 as rate')
            ->value('rate') ?? 0;

        // --- 9. Peak booking times (hourly) ---
        $peakBookingTimes = \App\Models\Booking::whereBetween('check_in', [$from, $to])
            ->selectRaw('HOUR(check_in) as hour, COUNT(*) as total')
            ->groupByRaw('HOUR(check_in)')
            ->orderBy('hour')
            ->get();

        // --- 10. Revenue contribution by room type ---
        $revenueByRoom = \App\Models\Room::join('reservations', 'reservations.room_number', '=', 'rooms.room_number')
            ->join('bookings', 'bookings.id', '=', 'reservations.booking_id')
            ->where('bookings.status', 'paid')
            ->whereBetween('bookings.check_in', [$from, $to])
            ->selectRaw('rooms.room_type, SUM(bookings.payable_amount) as revenue')
            ->groupBy('rooms.room_type')
            ->get();

        // --- 11. Total cancelled bookings count (for chart if needed) ---
        $cancelledBookings = \App\Models\Booking::whereBetween('check_in', [$from, $to])
            ->where('status', 'cancelled')
            ->count();

        // --- 12. Completed bookings count (for chart if needed) ---
        $completedBookings = \App\Models\Booking::whereBetween('check_in', [$from, $to])
            ->where('status', 'completed')
            ->count();

        // --- 13. Average occupancy per room type (optional) ---
        $occupancyByRoomType = \DB::table('reservations')
            ->join('rooms', 'reservations.room_number', '=', 'rooms.room_number')
            ->select('rooms.room_type')
            ->selectRaw('AVG(reservations.num_guests)*100 as occupancy_rate')
            ->groupBy('rooms.room_type')
            ->get();

        // --- 14. Peak booking hours (for line chart) ---
        $peakBookingHours = \App\Models\Booking::whereBetween('check_in', [$from, $to])
            ->selectRaw('HOUR(check_in) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // --- 15. Revenue contribution by room type already included above ---

        // --- Pass all data to blade ---
        return view('staff.reports.central', compact(
            'bookingsPerMonth',
            'revenuePerMonth',
            'paymentsByMethod',
            'discountsSummary',
            'topRoomsChart',
            'discountTrend',
            'cancelledRate',
            'avgOccupancy',
            'peakBookingTimes',
            'revenueByRoom',
            'cancelledBookings',
            'completedBookings',
            'occupancyByRoomType',
            'peakBookingHours',
            'from',
            'to'
        ));
    }

    // Export methods
    public function exportBookings(Request $request)
    {   
        $staff = Auth::guard('staff')->user();
        AuditLogger::log(
            'downloaded bookings report',
            'Report',
            null,
            null,
            "Staff {$staff->name} downloaded bookings report with filters: "
        );
        return Excel::download(new CentralBookingsExport($request), 'central_bookings.xlsx');
    }

    public function exportRevenue(Request $request)
    {   
        $staff = Auth::guard('staff')->user();

        AuditLogger::log(
            'downloaded revenue report',
            'Report',
            null,
            null,
            "Staff {$staff->name} downloaded revenue report with filters: " . json_encode($request->all())
        );

        return Excel::download(new CentralRevenueExport($request), 'central_revenue.xlsx');
    }

    public function exportOccupancy(Request $request)
    {

        $staff = Auth::guard('staff')->user();

        AuditLogger::log(
            'downloaded occupancy report',
            'Report',
            null,
            null,
            "Staff {$staff->name} downloaded occupancy report with filters: " . json_encode($request->all())
        );

        return Excel::download(new CentralOccupancyExport($request), 'central_occupancy.xlsx');
    }

    public function exportPayments(Request $request)
    {

        $staff = Auth::guard('staff')->user();

        AuditLogger::log(
            'downloaded payments report',
            'Report',
            null,
            null,
            "Staff {$staff->name} downloaded payments report with filters: " . json_encode($request->all())
        );

        return Excel::download(new CentralPaymentsExport($request), 'central_payments.xlsx');
    }

    public function exportDiscounts(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        AuditLogger::log(
            'downloaded discounts report',
            'Report',
            null,
            null,
            "Staff {$staff->name} downloaded discounts report with filters: " . json_encode($request->all())
        );

        return Excel::download(new CentralDiscountsExport($request), 'central_discounts.xlsx');
    }

}
