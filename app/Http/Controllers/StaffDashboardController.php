<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;
use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    public function index()
    {

        $bookingsPerMonth = Booking::select(
            DB::raw('MONTH(check_in) as month'),
            DB::raw('COUNT(*) as total')
        )
        ->where('status', 'paid')
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->keyBy('month'); // make it easy to access by month number

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
        return view('staff.dashboard', compact(
            'totalRooms', 
            'totalUsers', 
            'totalBookings',
            'labels',
            'values',
            'totalRevenue'
        ));
    }
}
