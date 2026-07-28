<?php

namespace App\Http\Controllers\Staff\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Services\ReportService;

class MainReportsController extends Controller
{
    public function index()
    {
        return view('staff.reports.index');
    }

    /**
     * `$request->validated()` rather than `all()`: the query builder reads
     * report_type, date_range.type and column_set unguarded, so an unvalidated
     * body used to reach it as an undefined-key error and surface as a 500.
     */
    public function generate(ReportRequest $request, ReportService $service)
    {
        return response()->json($service->generate($request->validated()));
    }

    public function export(ReportRequest $request, ReportService $service)
    {
        return $service->export($request->validated());
    }
}
