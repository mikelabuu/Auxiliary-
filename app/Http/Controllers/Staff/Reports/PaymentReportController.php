<?php

namespace App\Http\Controllers\Staff\Reports;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Exports\PaymentsExport;
use Maatwebsite\Excel\Facades\Excel;

class PaymentReportController extends Controller
{
    // Export all payments
    public function exportAll(Request $request)
    {
        return Excel::download(new PaymentsExport('all', $request), 'payments_all.xlsx');
    }

    // Export only cash payments
    public function exportCash(Request $request)
    {
        return Excel::download(new PaymentsExport('cash', $request), 'payments_cash.xlsx');
    }

}
