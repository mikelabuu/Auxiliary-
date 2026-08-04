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

            // Sorting is whitelisted again downstream against the selected
            // column set (ReportColumnMapper::getSortable), because which
            // aliases are valid depends on that set — 'gateway' is sortable on
            // a financial report and meaningless on a booking one. This rule
            // only keeps obvious junk out of the service.
            'sort'             => ['nullable', 'string', 'max:40'],
            'direction'        => ['nullable', Rule::in(['asc', 'desc'])],

            'per_page'         => ['nullable', Rule::in(\App\Services\ReportService::PAGE_SIZES)],

            // Was read by ReportExportService as $params['format'] but never
            // validated, so validate() stripped it and every export was xlsx
            // regardless of what was asked for.
            'format'           => ['nullable', Rule::in(['xlsx', 'pdf'])],

            'date_range'       => ['required', 'array'],
            'date_range.type'  => ['required', Rule::in(['monthly', 'yearly', 'weekly', 'range'])],
            'date_range.value' => ['required'],

            // Unknown filter fields are skipped by ReportQueryBuilder; this
            // only guarantees the shape it expects (field => list of values).
            'filters'          => ['nullable', 'array'],
            'filters.*'        => ['array'],
            'filters.*.*'      => ['string'],
        ], [
            // These reach the user now. The page renders a 422's messages into
            // the results panel instead of a flat "please try again", so the
            // defaults ("The date range.value field is required") would be
            // read by staff rather than by a developer.
            'date_range.value.required' => 'Choose a month, a year, or both ends of a custom date range.',
            'date_range.type.required'  => 'Choose a timeframe for the report.',
            'report_type.required'      => 'Choose a report category.',
        ]);
    }
}
