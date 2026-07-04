<?php

namespace App\Services;

class ReportColumnMapper
{
    protected $columnSets = [

        'booking_summary' => [
            'bookings.id',
            'bookings.guest_name',
            'bookings.check_in',
            'bookings.check_out',
            'bookings.expected_guests',
            'bookings.status'
        ],

        'financial' => [
            'bookings.id',
            'bookings.payable_amount',
            'payments.status as payment_status',
            'payments.gateway',
            'payments.created_at as paid_at'
        ],

        'combined' => [
            'bookings.id',
            'bookings.guest_name',
            'bookings.status',
            'bookings.payable_amount',
            'payments.status as payment_status',
            'payments.gateway'
        ],
    ];

    public function getColumns($set)
    {
        return $this->columnSets[$set] ?? ['bookings.*'];
    }
}
