<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;

class PaymentLogsController extends Controller
{
    public function index()
    {
        return view('staff.paymentlogs.index');
    }
}
