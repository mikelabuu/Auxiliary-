<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentsExport implements FromCollection, WithHeadings
{
    protected $type;
    protected $request;

    public function __construct($type = 'all', $request = null)
    {
        $this->type = $type;
        $this->request = $request;
    }

    public function collection()
    {
        $query = Payment::with('booking');

        // Optional date range filters
        if ($this->request?->has('from_date') && $this->request?->has('to_date')) {
            $query->whereBetween('created_at', [$this->request->from_date, $this->request->to_date]);
        }

        // Filter by type
        switch ($this->type) {
            case 'cash':
                $query->where('gateway', 'cash');
                break;
        }

        $payments = $query->get();

        return $payments->map(function($payment) {
            return [
                'Payment ID' => $payment->id,
                'Booking ID' => $payment->booking_id,
                'Guest Name' => $payment->booking?->guest_name ?? '',
                'Reference No' => $payment->reference_no,
                'Amount' => $payment->amount,
                'Status' => $payment->status,
                'Payment Method' => ucfirst($payment->gateway),
                'Payment Date' => $payment->created_at->setTimezone('Asia/Manila')->format('Y-m-d'),
                'Staff ID' => $payment->staff_id ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Payment ID',
            'Booking ID',
            'Guest Name',
            'Reference No',
            'Amount',
            'Status',
            'Payment Method',
            'Payment Date',
            'Staff ID',
        ];
    }
}
