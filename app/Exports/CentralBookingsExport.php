<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class CentralBookingsExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Booking::with('reservations', 'payments');

        // Filter by date range
        if (!empty($this->request?->from_date) && !empty($this->request?->to_date)) {
            $query->whereBetween('created_at', [
                $this->request->from_date . ' 00:00:00',
                $this->request->to_date . ' 23:59:59'
            ]);
        }

        // Filter by booking status
        if (!empty($this->request?->status)) {
            $query->where('status', $this->request->status);
        }


        $bookings = $query->get();

        return $bookings->map(function($b) {
            return [
                'Booking ID' => $b->id,
                'Guest Name' => $b->guest_name,
                'Check-in' => Carbon::parse($b->check_in)->setTimezone('Asia/Manila')->format('Y-m-d'),
                'Check-out' => Carbon::parse($b->check_out)->setTimezone('Asia/Manila')->format('Y-m-d'),
                'Status' => ucfirst($b->status),
                'Total Guests' => $b->expected_guests,
                'Amount' => $b->payable_amount ?? $b->total_price,
                'Room Numbers' => $b->reservations ? implode(', ', $b->reservations->pluck('room_number')->toArray()) : '',
                'Payment ID' => $b->payments ? ucfirst($b->payments->id) : '',
                'Payment Method' => $b->payments ? ucfirst($b->payments->gateway) : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Booking ID',
            'Guest Name',
            'Check-in',
            'Check-out',
            'Status',
            'Total Guests',
            'Amount',
            'Room Numbers',
            'Payment ID',
            'Payment Methods',
        ];
    }
}
