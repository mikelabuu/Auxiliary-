<?php

namespace App\Http\Controllers\Staff\Reports;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use App\Exports\DiscountsExport;
use Maatwebsite\Excel\Facades\Excel;

class DiscountReportController extends Controller
{
    public function exportAll(Request $request)
    {
        return Excel::download(new DiscountsExport('all', $request), 'discounts_all.xlsx');
    }

    public function exportPending(Request $request)
    {
        return Excel::download(new DiscountsExport('pending', $request), 'discounts_pending.xlsx');
    }

    public function exportApproved(Request $request)
    {
        return Excel::download(new DiscountsExport('approved', $request), 'discounts_approved.xlsx');
    }

    public function exportRejected(Request $request)
    {
        return Excel::download(new DiscountsExport('rejected', $request), 'discounts_rejected.xlsx');
    }
}
