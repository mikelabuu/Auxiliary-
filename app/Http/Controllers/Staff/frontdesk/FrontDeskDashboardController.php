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
        $bookingsPerMonth = Booking::select(
            DB::raw('MONTH(check_in) as month'),
            DB::raw('COUNT(*) as total')
        )
        ->where('status', 'paid')
        ->whereYear('check_in', $year)
        ->groupBy(DB::raw('YEAR(check_in)'), DB::raw('MONTH(check_in)'))
        ->groupBy('month')
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
        $staff = Auth::guard('staff')->user(); // get logged-in staff user
        return view('staff.frontdesk.dashboard', compact(
            'totalRooms', 
            'totalUsers', 
            'totalBookings',
            'labels',
            'values',
            'totalRevenue',
        ));
    }
}
