<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * The figures above the report table.
 *
 * Why this is not three SUM()s in the controller
 * ----------------------------------------------
 * The three reports have different row grains, and a total that ignores that
 * is wrong rather than approximate.
 *
 *   · booking  — bookings, no join. One row per booking, so summing a booking
 *                column is exact.
 *   · payment  — payments INNER JOIN bookings. One row per *payment*, so
 *                summing bookings.payable_amount would count a booking once
 *                per payment attached to it.
 *   · combined — bookings LEFT JOIN payments. Same hazard in the other
 *                direction: a booking with two payment rows becomes two result
 *                rows, and any booking-level sum doubles.
 *
 * Booking::payments() is hasOne()->latestOfMany(), which makes that look
 * impossible — but latestOfMany is an Eloquent narrowing, not a database
 * constraint, and nothing stops a second payments row existing. The system
 * expects one: Payment::STATUS_REJECTED is documented as "the guest may
 * retry", and a retry is a second row. No booking has two today; the first one
 * that does must not silently inflate a revenue figure someone is reading off
 * a screen.
 *
 * So money is only ever summed from the table whose grain the report is
 * already at, and anything booking-level on a joined report is counted
 * DISTINCT.
 */
class ReportSummarizer
{
    /**
     * @return array<int, array{label: string, value: string, format: string}>
     */
    public function summarize($query, array $params): array
    {
        // clone: the caller still needs this query for its own pagination, and
        // aggregate calls mutate the builder's select/order state.
        $base = clone $query;

        return match ($params['report_type']) {
            'booking'  => $this->bookingSummary($base),
            'payment'  => $this->paymentSummary($base),
            'combined' => $this->combinedSummary($base),
            default    => [],
        };
    }

    private function bookingSummary($query): array
    {
        $row = $this->aggregate($query, [
            'records' => 'COUNT(*)',
            'payable' => 'COALESCE(SUM(bookings.payable_amount), 0)',
            'guests'  => 'COALESCE(SUM(bookings.expected_guests), 0)',
        ]);

        return [
            $this->card('Bookings', $row->records, 'number'),
            $this->card('Total Payable', $row->payable, 'money'),
            $this->card('Expected Guests', $row->guests, 'number'),
        ];
    }

    private function paymentSummary($query): array
    {
        // payments.amount, not bookings.payable_amount: this report is one row
        // per payment, and payable_amount belongs to the booking behind it.
        $row = $this->aggregate($query, [
            'records' => 'COUNT(*)',
            'total'   => 'COALESCE(SUM(payments.amount), 0)',
            'settled' => "COALESCE(SUM(CASE WHEN payments.status = 'success' THEN payments.amount ELSE 0 END), 0)",
        ]);

        return [
            $this->card('Payments', $row->records, 'number'),
            $this->card('Total Amount', $row->total, 'money'),
            $this->card('Settled', $row->settled, 'money'),
        ];
    }

    private function combinedSummary($query): array
    {
        // COUNT(DISTINCT bookings.id) rather than COUNT(*): on a left join,
        // COUNT(*) counts result rows, which is what the table shows but not
        // what "bookings" means to whoever is reading the card.
        $row = $this->aggregate($query, [
            'bookings'  => 'COUNT(DISTINCT bookings.id)',
            'collected' => "COALESCE(SUM(CASE WHEN payments.status = 'success' THEN payments.amount ELSE 0 END), 0)",
            'unpaid'    => 'COUNT(DISTINCT CASE WHEN payments.id IS NULL THEN bookings.id END)',
        ]);

        return [
            $this->card('Bookings', $row->bookings, 'number'),
            $this->card('Collected', $row->collected, 'money'),
            $this->card('No Payment Record', $row->unpaid, 'number'),
        ];
    }

    /**
     * Run the aggregates over the filtered query.
     *
     * The select list and ORDER BY are stripped first. The incoming query
     * selects display columns and orders by them; neither is legal alongside
     * an aggregate under MySQL's ONLY_FULL_GROUP_BY, and an ORDER BY on a
     * column that is no longer selected is a pointless sort of one row.
     */
    private function aggregate($query, array $expressions)
    {
        // Both have to be cleared, and selectRaw() will not do it: it appends
        // via addSelect, so leaving the display columns in place produces
        // "SELECT bookings.id, ..., COUNT(*)" — bare columns beside an
        // aggregate with no GROUP BY, which MySQL rejects outright.
        $query->columns = null;
        $query->orders = null;

        return $query
            ->selectRaw(collect($expressions)
                ->map(fn ($expr, $alias) => $expr . ' as ' . $alias)
                ->implode(', '))
            ->first() ?? (object) array_fill_keys(array_keys($expressions), 0);
    }

    private function card(string $label, $value, string $format): array
    {
        return [
            'label'  => $label,
            'value'  => $format === 'money' ? (float) $value : (int) $value,
            'format' => $format,
        ];
    }
}
