<?php

namespace App\Http\Controllers\Staff\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReportService;

class MainReportsController extends Controller
{
    public function index()
    {
        return view('staff.reports.index');
    }

    public function generate(Request $request, ReportService $service)
    {
        $data = $service->generate($request->all());

        return response()->json($data);
    }

    public function export(Request $request, ReportService $service)
    {
        return $service->export($request->all());
    }
}
