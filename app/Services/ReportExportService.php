<?php

namespace App\Services;

use App\Exports\GenericReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportService
{
    protected function generateFilename($params)
    {
        $type = $params['report_type'] ?? 'report';
        $date = $params['date_range'] ?? [];

        $typeLabel = match ($type) {
            'booking' => 'booking',
            'payment' => 'payment',
            'combined' => 'combined',
            default => 'report'
        };

        $dateLabel = 'all_dates';

        if (!empty($date)) {
            if ($date['type'] === 'monthly') {
                $dateLabel = $date['value']; // YYYY-MM
            }

            if ($date['type'] === 'yearly') {
                $dateLabel = $date['value']; // YYYY
            }

            if ($date['type'] === 'range') {
                $from = $date['value']['from'] ?? 'start';
                $to   = $date['value']['to'] ?? 'end';

                $dateLabel = "{$from}_to_{$to}";
            }
        }

        $timestamp = now()->format('Ymd_His');

        return "{$typeLabel}_report_{$dateLabel}_{$timestamp}";
    }

    public function export($query, $params)
    {
        $format = $params['format'] ?? 'xlsx';

        return $format === 'pdf'
            ? $this->exportPdf($query, $params)
            : $this->exportExcel($query, $params);
    }

    private function exportExcel($query, $params)
    {
        return Excel::download(
            new GenericReportExport($query->get()),
            $this->generateFilename($params) . '.xlsx'
        );
    }

    /**
     * The handover format: something to print, file, or send to someone who is
     * not going to open a spreadsheet.
     *
     * Row-capped on purpose. dompdf lays out every cell in PHP, and a few
     * thousand rows turns a click into a request that runs past the timeout
     * and returns a broken download with no explanation. The cap is visible in
     * the document itself rather than silent, so a truncated report can never
     * be mistaken for a complete one — if it trips, the answer is a narrower
     * date range, or Excel, which streams and has no such limit.
     */
    private function exportPdf($query, $params)
    {
        $limit = 2000;

        // Before the limit goes on. The totals describe everything that
        // matched, not just the page of it that fitted — a report capped at
        // 2,000 rows whose header claimed the revenue of those 2,000 would be
        // wrong in the most quotable way possible.
        $summary = app(ReportSummarizer::class)->summarize($query, $params);

        $rows = $query->limit($limit + 1)->get();

        $truncated = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $headings = $rows->isEmpty()
            ? []
            : collect(array_keys((array) $rows->first()))
                ->map(fn ($key) => Str::of($key)->replace('_', ' ')->title()->toString())
                ->all();

        $pdf = Pdf::loadView('pdf.report', [
            'rows'      => $rows,
            'headings'  => $headings,
            'summary'   => $summary,
            'meta'      => $this->describe($params),
            'truncated' => $truncated,
            'limit'     => $limit,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->generateFilename($params) . '.pdf');
    }

    /**
     * The header block: what this document is, so a printed page found on a
     * desk later still says which period and filters produced it.
     *
     * @return array<string, string>
     */
    private function describe(array $params): array
    {
        $date = $params['date_range'] ?? [];

        $period = match ($date['type'] ?? null) {
            'monthly' => \Carbon\Carbon::parse($date['value'] . '-01')->format('F Y'),
            'yearly'  => (string) $date['value'],
            'range'   => ($date['value']['from'] ?? '?') . '  to  ' . ($date['value']['to'] ?? '?'),
            'weekly'  => is_array($date['value']) ? implode('  to  ', $date['value']) : (string) $date['value'],
            default   => 'All dates',
        };

        $filters = collect($params['filters'] ?? [])
            ->map(fn ($values, $field) => Str::of($field)->replace('_', ' ')->title()
                . ': ' . collect($values)->map(fn ($v) => Str::of($v)->replace('_', ' ')->title())->implode(', '))
            ->values()
            ->all();

        return [
            'title'   => match ($params['report_type']) {
                'booking'  => 'Booking Report',
                'payment'  => 'Financial Report',
                'combined' => 'Combined Overview',
                default    => 'Report',
            },
            'period'  => $period,
            'filters' => $filters ? implode('   ·   ', $filters) : 'No status filters applied',
        ];
    }
}
