<?php

namespace App\Services;

class ReportColumnMapper
{
    /**
     * Each set maps the alias the row is delivered under to the real column
     * behind it.
     *
     * It used to be a flat list of select expressions, half of them aliased
     * ('payments.status as payment_status') and half not ('bookings.id'). That
     * was enough while the only thing anyone did with a column was display it.
     * Sorting needs the other direction — given 'payment_status' off a clicked
     * header, which column does ORDER BY name? — and deriving that from a list
     * of select strings means re-parsing ' as ' at runtime and guessing about
     * the ones that have no alias.
     *
     * Keeping alias => column means the select list and the sortable whitelist
     * are the same fact stated once. A column that cannot be selected cannot be
     * sorted by, automatically, which is also what keeps ORDER BY away from
     * arbitrary user input.
     */
    protected $columnSets = [

        'booking_summary' => [
            'id'              => 'bookings.id',
            'guest_name'      => 'bookings.guest_name',
            'check_in'        => 'bookings.check_in',
            'check_out'       => 'bookings.check_out',
            'expected_guests' => 'bookings.expected_guests',
            'status'          => 'bookings.status',
        ],

        'financial' => [
            'id'             => 'bookings.id',
            'payable_amount' => 'bookings.payable_amount',
            'payment_status' => 'payments.status',
            'gateway'        => 'payments.gateway',
            'paid_at'        => 'payments.created_at',
        ],

        'combined' => [
            'id'             => 'bookings.id',
            'guest_name'     => 'bookings.guest_name',
            'status'         => 'bookings.status',
            'payable_amount' => 'bookings.payable_amount',
            'payment_status' => 'payments.status',
            'gateway'        => 'payments.gateway',
        ],
    ];

    /** Fallback when no set matches, mirroring the previous behaviour. */
    private const FALLBACK = ['bookings.*'];

    /**
     * Select expressions, aliased so the JSON keys the table and the export
     * read are stable no matter which table a column came from.
     *
     * @return array<int, string>
     */
    public function getColumns($set): array
    {
        $map = $this->columnSets[$set] ?? null;

        if (! $map) {
            return self::FALLBACK;
        }

        return collect($map)
            ->map(fn ($column, $alias) => $column . ' as ' . $alias)
            ->values()
            ->all();
    }

    /**
     * alias => column, for ORDER BY. An alias absent from here is not
     * sortable — which is the whitelist, not merely a lookup miss.
     *
     * @return array<string, string>
     */
    public function getSortable($set): array
    {
        return $this->columnSets[$set] ?? [];
    }
}
