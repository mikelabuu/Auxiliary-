<?php
namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericReportExport;

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
        $data = $query->get();

        $format = $params['format'] ?? 'xlsx';

        $filename = $this->generateFilename($params) . '.' . $format;
        return Excel::download(
            new GenericReportExport($data),
            $filename
        );
    }
}
