<?php

namespace App\Http\Controllers\staff\Reports;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BookingsExport;

class BookingReportController extends Controller
{
    // Full Booking List
    public function exportFull(Request $request)
    {
        return Excel::download(new BookingsExport('all', $request), 'bookings_full.xlsx');
    }

    // Paid Bookings
    public function exportPaid(Request $request)
    {
        return Excel::download(new BookingsExport('paid', $request), 'bookings_paid.xlsx');
    }

    // Completed Bookings
    public function exportCompleted(Request $request)
    {
        return Excel::download(new BookingsExport('completed', $request), 'bookings_completed.xlsx');
    }
}
