<?php 

namespace App\Services;

class ReportService
{
    public function generate(array $params)
    {
        $query = app(ReportQueryBuilder::class)->build($params);

        return $query->paginate(10);
    }

    public function export(array $params)
    {
        $query = app(ReportQueryBuilder::class)->build($params);

        return app(ReportExportService::class)->export($query, $params);
    }
}