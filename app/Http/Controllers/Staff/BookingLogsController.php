<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;

class BookingLogsController extends Controller
{
    public function index()
    {
        return view('staff.bookinglogs.index');
    }
}
