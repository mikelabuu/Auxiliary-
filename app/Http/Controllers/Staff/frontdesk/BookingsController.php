<?php 

namespace App\Http\Controllers\Staff\frontdesk;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Checkout;
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
        $status = $request->input('status', 'all'); // status tab
        $perPage = 12; // pagination

        // Tab counts reflect the whole book, not the current search
        $statusCounts = Booking::select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        // Base query with reservations eager loaded
        $query = Booking::with('reservations');

        if ($search) {
            $query->where('id', $search);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
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

        return view('staff.frontdesk.bookings.index', compact('bookings', 'search', 'sort', 'status', 'statusCounts'));
    }
    
    public function checkout(Booking $booking)
    {
        $staff = auth('staff')->user();

        DB::transaction(function () use ($booking, $staff) {

            $booking->update(['status' => 'completed']);

            foreach ($booking->reservations as $reservation) {
                $reservation->room->update(['status' => 'available']);
            }

            CheckOut::create([
                'booking_id' => $booking->id,
                'checked_out_at' => now('Asia/Manila'),
                'method' => 'manual',
                'processed_by' => $staff->id,
            ]);

            AuditLogger::log(
                'booking_checked_out',
                $booking,
                ['status' => 'active'],
                ['status' => 'completed'],
                "Booking #{$booking->id} checked out by {$staff->name}"
            );
        });

        return back()->with('success', "Booking #{$booking->id} checked out.");
    }
}