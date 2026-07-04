<?php

namespace App\Http\Controllers\Staff\frontdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;
use App\Models\User;
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

        $totalRooms = Room::count();
        $totalUsers = User::count();
        $totalBookings = Booking::count();

        $totalRevenue = Payment::where('status', 'success')->sum('amount');

        //dd(Auth::guard('staff')->user());
        return view('staff.frontdesk.dashboard.index', compact(
            'totalRooms', 
            'totalUsers', 
            'totalBookings',
            'labels',
            'values',
            'totalRevenue',
        ));
    }
}
