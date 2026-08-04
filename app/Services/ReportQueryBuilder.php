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
        $this->applySorting($query, $params, $schema);

        return $query;
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

    protected function applyFilters($query, $params, $schema)
    {
        if (empty($params['filters'])) return;

        $allowed = $schema['allowed_filters'];

        $matchType = strtoupper($params['match_type'] ?? 'AND');

        // The closure groups the whole filter set, so an OR between two
        // filters cannot escape and turn the date range into an alternative
        // as well — "(status = x OR gateway = y)" inside the period, never
        // "in the period OR gateway = y".
        $query->where(function ($q) use ($params, $allowed, $matchType) {

            foreach ($params['filters'] as $field => $values) {

                //  SKIP INVALID FILTERS (this is what prevents crashes)
                if (!in_array($field, $allowed)) {
                    continue;
                }

                $column = $this->mapFilterToColumn($field);

                if (!$column) continue;

                // $matchType was read and then never applied, so 'OR' produced
                // exactly the AND query — quietly, since a narrower result set
                // looks like a legitimate answer rather than a bug.
                if ($matchType === 'OR') {
                    $q->orWhereIn($column, $values);
                } else {
                    $q->whereIn($column, $values);
                }
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
            // The column is payment_mode. 'bookings.mode' does not exist, so
            // this mapping turned an allowed filter — ReportSchema lists it for
            // both the booking and combined reports — into a 500. Unreachable
            // today only because no chip on the page sends it.
            'mode' => 'bookings.payment_mode',
            default => null
        };
    }

    // -------------------------

    protected function applyColumns($query, $params)
    {
        $columns = app(ReportColumnMapper::class)
            ->getColumns($params['column_set'] ?? null);

        $query->select($columns);
    }

    // -------------------------

    /**
     * ORDER BY, resolved through the same alias => column map the select list
     * comes from, so only a column the report actually shows can be sorted by.
     * An unknown alias falls back to the default rather than reaching the
     * database — ORDER BY cannot be bound as a parameter, so an unvalidated
     * value here would be concatenated straight into SQL.
     *
     * The default is the base table's id descending: newest first, and a
     * deterministic order. Without one, a paginated report has no guaranteed
     * row order at all, and MySQL is free to return a row on both page 1 and
     * page 2 while another never appears — the kind of fault that reads as
     * missing data rather than missing sort.
     */
    protected function applySorting($query, $params, $schema)
    {
        $sortable = app(ReportColumnMapper::class)
            ->getSortable($params['column_set'] ?? null);

        $requested = $params['sort'] ?? null;
        $direction = strtolower($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $column = $sortable[$requested] ?? null;

        if (! $column) {
            $query->orderBy($schema['base'] . '.id', 'desc');

            return;
        }

        $query->orderBy($column, $direction);

        // A tie on the sorted column leaves the same page-straddling
        // ambiguity, so anything non-unique gets the id as a tiebreaker.
        if ($column !== $schema['base'] . '.id') {
            $query->orderBy($schema['base'] . '.id', 'desc');
        }
    }
}
