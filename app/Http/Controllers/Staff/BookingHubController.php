<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class BookingHubController extends Controller
{
    public function index()
    {
        return view('staff.bookings.index'); // renders the Blade wrapper
    }

    /**
     * The full booking detail modal (details + timeline + guest history),
     * opened from a guest name anywhere in the console. Same modal as the
     * View button — one surface, not a separate "guest history" dialog.
     * The click is password-gated in the layout handler, mirroring View.
     */
    public function guestHistory(Booking $booking)
    {
        $staff = Auth::guard('staff')->user();

        // Both the row's View button and the guest name open this modal now,
        // so the description no longer names one of them. (The View button
        // used to log through Livewire's selectBooking(), which is gone.)
        AuditLogger::log(
            'view_booking_modal',
            $booking,
            null,
            null,
            "Staff {$staff->name} viewed booking #{$booking->id} (\"{$booking->guest_name}\")"
        );

        $html = view('staff.partials.booking-details', [
            'booking' => $booking->load(['reservations.room', 'payments']),
            'modalId' => 'guestBookingModal',
        ])->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
    public function verifyPassword(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $staff = Auth::guard('staff')->user();

        if (! $staff || ! Hash::check($request->password, $staff->password)) {
            return response()->json(['success' => false, 'message' => 'Incorrect password'], 422);
        }

        return response()->json(['success' => true]);
    }
}

