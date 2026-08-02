<?php

namespace App\Http\Controllers\Staff\frontdesk;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\CreatesStaffBooking;

/**
 * Front desk "Walk-In": a guest standing at the counter. Identical flow to the
 * admin manual booking screen — see {@see CreatesStaffBooking} — differing only
 * in which view it renders and where it lands afterwards.
 */
class WalkInBookingController extends Controller
{
    use CreatesStaffBooking;

    protected function bookingRoutes(): array
    {
        return [
            'form'     => 'staff.frontdesk.walkin.create',
            'show'     => 'staff.frontdesk.walkin.show',
            'redirect' => 'frontdesk.walkin.show',
        ];
    }
}
