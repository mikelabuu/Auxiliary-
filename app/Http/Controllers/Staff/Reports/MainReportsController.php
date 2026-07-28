<?php

namespace App\Http\Controllers\Staff\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ReportService;

class MainReportsController extends Controller
{
    public function index()
    {
        return view('staff.reports.index');
    }

    public function generate(Request $request, ReportService $service)
    {
        $data = $service->generate($this->validated($request));

        return response()->json($data);
    }

    public function export(Request $request, ReportService $service)
    {
        return $service->export($this->validated($request));
    }

    /**
     * Both endpoints used to hand $request->all() straight to the report
     * service. Filters and columns are whitelisted downstream, so this was
     * never injectable — but a missing key threw an undefined-index 500
     * instead of a validation error, and an unknown report_type surfaced as
     * a raw exception.
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'report_type'      => ['required', Rule::in(['booking', 'payment', 'combined'])],
            'column_set'       => ['nullable', Rule::in(['booking_summary', 'financial', 'combined'])],
            'match_type'       => ['nullable', Rule::in(['AND', 'OR'])],

            'date_range'       => ['required', 'array'],
            'date_range.type'  => ['required', Rule::in(['monthly', 'yearly', 'weekly', 'range'])],
            'date_range.value' => ['required'],

            // Unknown filter fields are skipped by ReportQueryBuilder; this
            // only guarantees the shape it expects (field => list of values).
            'filters'          => ['nullable', 'array'],
            'filters.*'        => ['array'],
            'filters.*.*'      => ['string'],
        ]);
    }
}
