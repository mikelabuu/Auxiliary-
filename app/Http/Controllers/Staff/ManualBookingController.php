<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\CreatesStaffBooking;

/**
 * Admin-side "Manual Booking": a booking taken on a guest's behalf over the
 * phone or any offline channel. Shares its entire body with the front desk
 * walk-in screen — see {@see CreatesStaffBooking}.
 */
class ManualBookingController extends Controller
{
    use CreatesStaffBooking;

    protected function bookingRoutes(): array
    {
        return [
            'form'     => 'staff.manualbooking.index',
            'show'     => 'staff.manualbooking.show',
            'redirect' => 'staff.manualbooking.show',
        ];
    }
}
