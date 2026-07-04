<?php

namespace App\Services;

class ReportSchema
{
    public static function get($type)
    {
        return match ($type) {

            'booking' => [
                'base' => 'bookings',
                'joins' => [],
                'allowed_filters' => [
                    'booking_status',
                    'mode',
                ],
                'allowed_columns' => 'booking_summary',
                'date_column' => 'bookings.created_at',
            ],

            'payment' => [
                'base' => 'payments',
                'joins' => [
                    'bookings' => [
                        'type' => 'inner',
                        'on' => ['payments.booking_id', '=', 'bookings.id']
                    ],
                ],
                'allowed_filters' => [
                    'booking_status',
                    'payment_status',
                    'gateway',
                ],
                'allowed_columns' => 'financial',
                'date_column' => 'payments.created_at',
            ],

            'combined' => [
                'base' => 'bookings',
                'joins' => [
                    'payments' => [
                        'type' => 'left',
                        'on' => ['payments.booking_id', '=', 'bookings.id']
                    ],
                ],
                'allowed_filters' => [
                    'booking_status',
                    'payment_status',
                    'gateway',
                    'mode',
                ],
                'allowed_columns' => 'combined',
                'date_column' => 'bookings.created_at',
            ],

            default => throw new \Exception("Invalid report type"),
        };
    }
}
