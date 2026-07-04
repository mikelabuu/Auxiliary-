<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class CompletedBookingsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'latest');

        $bookings = Booking::where('status', 'completed')
            ->when($search, fn($q) => $q->where('id', 'like', "%{$search}%"))
            ->orderBy('updated_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->paginate(10);

        return view('staff.completedbookings.index', compact('bookings', 'search', 'sort'));
    }

    public function verifyPassword(Request $request)
    {
        $staff = auth('staff')->user();

        if (!$staff || !\Hash::check($request->password, $staff->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid password. Please try again.']);
        }

        return response()->json(['success' => true]);
    }

    public function showDetails($id)
    {
        $booking = Booking::with(['reservations.room', 'payments'])->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

            $staff = Auth::guard('staff')->user();

            //  Log that the staff viewed a completed booking
            AuditLogger::log(
                'view_completed_booking_details',
                $booking,
                null,
                null,
                "Staff {$staff->name} viewed details for completed booking #{$booking->id}"
            );

        $html = view('staff.partials.booking-details', compact('booking'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}
