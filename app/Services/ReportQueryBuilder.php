<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportQueryBuilder
{
    public function build(array $params)
    {
        $schema = ReportSchema::get($params['report_type']);

        $query = DB::table($schema['base']);

        $this->applyJoins($query, $schema);
        $this->applyDateFilter($query, $params, $schema);
        $this->applyFilters($query, $params, $schema);
        $this->applyColumns($query, $params);

        return $query;
    }

    // -------------------------

    protected function baseQuery($type)
    {
        return match ($type) {
            'booking' => DB::table('bookings'),

            'payment' => DB::table('payments')
                ->join('bookings', 'payments.booking_id', '=', 'bookings.id'),

            'combined' => DB::table('bookings'),

            default => throw new \Exception('Invalid report type')
        };
    }

    // -------------------------

    protected function applyJoins($query, $schema)
    {
        foreach ($schema['joins'] as $table => $join) {

            if ($join['type'] === 'inner') {
                $query->join($table, ...$join['on']);
            }

            if ($join['type'] === 'left') {
                $query->leftJoin($table, ...$join['on']);
            }
        }
    }

    // -------------------------

    protected function applyDateFilter($query, $params, $schema)
    {
        $dateColumn = $schema['date_column'];

        $type = $params['date_range']['type'];
        $value = $params['date_range']['value'];
        
        if ($type === 'monthly') {
            $parts = explode('-', $value);

            $year  = $parts[0] ?? null;
            $month = $parts[1] ?? null;

            if (!$year || !$month) {
                throw new \InvalidArgumentException("Invalid monthly format. Expected YYYY-MM, got: {$value}");
            }

            $query->whereYear($dateColumn, $year)
                ->whereMonth($dateColumn, $month);
        }

        if ($type === 'yearly') {
            $query->whereYear($dateColumn, $value);
        }

        if ($type === 'weekly') {
            $query->whereBetween($dateColumn, $value); 
            // value = [start_date, end_date]
        }

        if ($type === 'range') {
            $query->whereBetween($dateColumn, [
                $value['from'],
                $value['to']
            ]);
        }
    }

    // -------------------------

    protected function resolveDateColumn($type)
    {
        return match ($type) {
            'booking' => 'bookings.created_at',
            'payment' => 'payments.created_at',
            'combined' => DB::raw('COALESCE(payments.created_at, bookings.created_at)')
        };
    }

    // -------------------------

    protected function applyFilters($query, $params, $schema)
    {
        if (empty($params['filters'])) return;

        $allowed = $schema['allowed_filters'];

        $matchType = $params['match_type'] ?? 'AND';

        $query->where(function ($q) use ($params, $allowed, $matchType) {

            foreach ($params['filters'] as $field => $values) {

                //  SKIP INVALID FILTERS (this is what prevents crashes)
                if (!in_array($field, $allowed)) {
                    continue;
                }

                $column = $this->mapFilterToColumn($field);

                if (!$column) continue;

                $q->whereIn($column, $values);
            }

        });
    }


    // -------------------------

    protected function mapFilterToColumn($field)
    {
        return match ($field) {
            'booking_status' => 'bookings.status',
            'payment_status' => 'payments.status',
            'gateway' => 'payments.gateway',
            'mode' => 'bookings.mode',
            default => null
        };
    }

    // -------------------------

    protected function applyColumns($query, $params)
    {
        $columns = app(ReportColumnMapper::class)
            ->getColumns($params['column_set']);

        $query->select($columns);
    }
}
