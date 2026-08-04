<?php

namespace App\Services;

class ReportService
{
    /** Page sizes the selector offers. Anything else falls back to the first. */
    public const PAGE_SIZES = [10, 25, 50, 100];

    /**
     * The response is now { rows: <paginator>, summary: [...] } rather than the
     * paginator alone. The table needs the page; the cards above it need
     * figures over the *whole* filtered set, which a page of ten cannot give.
     */
    public function generate(array $params)
    {
        $query = app(ReportQueryBuilder::class)->build($params);

        $perPage = in_array((int) ($params['per_page'] ?? 0), self::PAGE_SIZES, true)
            ? (int) $params['per_page']
            : self::PAGE_SIZES[0];

        // Summarise before paginating: paginate() runs its own count and
        // rewrites the builder's select, so the aggregates have to be taken
        // off an untouched clone first.
        $summary = app(ReportSummarizer::class)->summarize($query, $params);

        return [
            'rows'    => $query->paginate($perPage),
            'summary' => $summary,
        ];
    }

    public function export(array $params)
    {
        $query = app(ReportQueryBuilder::class)->build($params);

        return app(ReportExportService::class)->export($query, $params);
    }
}
