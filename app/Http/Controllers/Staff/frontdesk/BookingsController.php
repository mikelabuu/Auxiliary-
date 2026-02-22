<?php 

namespace App\Http\Controllers\Staff\frontdesk;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\AuditLogger;

class BookingsController extends Controller{

    public function viewBookings(Request $request)
    {
        $search = $request->input('search'); // search query
        $sort   = $request->input('sort', 'latest'); // sort option
        $perPage = 12; // pagination

        // Base query with reservations eager loaded
        $query = Booking::with('reservations');

        if ($search) {
            $query->where('id', $search);
        }

        // Sorting
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name':
                $query->orderBy('guest_name', 'asc');
                break;
            case 'check_in':
                $query->orderBy('check_in', 'asc');
                break;
            default: // latest
                $query->orderBy('created_at', 'desc');
        }

        // Paginate results
        $bookings = $query->paginate($perPage)->withQueryString();

        return view('staff.frontdesk.bookings', compact('bookings', 'search', 'sort'));
    }
}