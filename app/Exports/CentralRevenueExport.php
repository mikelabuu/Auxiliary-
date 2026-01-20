<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class CentralRevenueExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Booking::whereHas('payments', function($q) {
            $q->where('status', 'success');

            // Filter by gateway if provided
            if (!empty($this->request?->payment_method)) {
                $q->where('gateway', $this->request->payment_method);
            }

            // Filter by payment date if provided
            if (!empty($this->request?->from_date) && !empty($this->request?->to_date)) {
                $q->whereBetween('created_at', [
                    $this->request->from_date . ' 00:00:00',
                    $this->request->to_date . ' 23:59:59'
                ]);
            }
        });

        $bookings = $query->with('payments')->get();

        $bookingsCollection = $bookings->map(function($b) {
            $payment = $b->payments;

            return [
                'Booking ID' => $b->id,
                'Guest Name' => $b->guest_name,
                'Check-in' => Carbon::parse($b->check_in)->setTimezone('Asia/Manila')->format('Y-m-d'),
                'Check-out' => Carbon::parse($b->check_out)->setTimezone('Asia/Manila')->format('Y-m-d'),
                'Payable Amount' => $payment ? $payment->amount : ($b->payable_amount ?? $b->total_price),
                'Payment Method' => $payment ? ucfirst($payment->gateway) : '',
            ];
        });

        // Calculate total revenue
        $totalRevenue = $bookings->sum(fn($b) => $b->payments ? $b->payments->amount : ($b->payable_amount ?? $b->total_price));

        // Append total row
        $bookingsCollection->push([
            'Booking ID' => '',
            'Guest Name' => '',
            'Check-in' => '',
            'Check-out' => '',
            'Payable Amount' => $totalRevenue,
            'Payment Method' => 'TOTAL REVENUE',
        ]);

        return $bookingsCollection;
    }

    public function headings(): array
    {
        return [
            'Booking ID',
            'Guest Name',
            'Check-in',
            'Check-out',
            'Payable Amount',
            'Payment Method',
        ];
    }
}
