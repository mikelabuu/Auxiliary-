<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class CentralPaymentsExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Payment::with('booking')->where('status', 'success');

        // Filter by creation date (when payment was made)
        if (!empty($this->request->from_date) && !empty($this->request->to_date)) {
            $query->whereBetween('created_at', [$this->request->from_date, $this->request->to_date]);
        }

        // Filter by payment gateway if set
        if (!empty($this->request->payment_method)) {
            $query->where('gateway', $this->request->payment_method);
        }

        $payments = $query->get();

        return $payments->map(function($p) {
            return [
                'Payment ID' => $p->id,
                'Booking ID' => $p->booking_id,
                'Guest Name' => $p->booking?->guest_name ?? '',
                'Amount' => $p->amount,
                'Payment Method' => ucfirst($p->gateway),
                'Payment Date' => Carbon::parse($p->created_at)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Payment ID',
            'Booking ID',
            'Guest Name',
            'Amount',
            'Payment Method',
            'Payment Date',
        ];
    }
}
