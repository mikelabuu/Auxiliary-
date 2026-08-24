<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class BookingsExport implements FromCollection, WithHeadings
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
        $query = Booking::with(['reservations', 'payments']);

        // Filters from request (optional)
        if ($this->request?->has('from_date') && $this->request?->has('to_date')) {
            $query->whereBetween('check_in', [$this->request->from_date, $this->request->to_date]);
        }

        switch($this->type) {
            case 'paid':
                $query->whereHas('payments', function($q) {
                    $q->where('status', 'success');
                });
                break;
            case 'completed':
                $query->where('status', Booking::STATUS_COMPLETED);
                break;
        }

        $bookings = $query->get();

        return $bookings->map(function($booking) {

            return [
                'Booking ID' => $booking->id,
                'Guest Name' => $booking->guest_name,
                'User ID' => $booking->user_id,
                'Check-in' => $booking->check_in ? Carbon::parse($booking->check_in)->setTimezone(config('hostel.timezone'))->format('Y-m-d') : '',
                'Check-out' => $booking->check_out ? Carbon::parse($booking->check_out)->setTimezone(config('hostel.timezone'))->format('Y-m-d') : '',
                'Status' => $booking->status,
                'Room Numbers' => $booking->reservations->pluck('room_number')->implode(', '),
                'Total Price' => $booking->total_price,
                // Falls back the same way every payment path does. Bookings
                // created before payable_amount was set at creation carry NULL,
                // and a blank money column in an export reads as zero owed.
                'Payable Amount' => $booking->payable_amount ?? $booking->total_price,
                'Payment ID' => $booking->payments?->id ?? '',
                'Reference No' => $booking->payments?->reference_no ?? '',
                'Booking Created At' => $booking->created_at ? Carbon::parse($booking->created_at)->setTimezone(config('hostel.timezone'))->format('Y-m-d H:i:s') : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Booking ID',
            'Guest Name',
            'User ID',
            'Check-in',
            'Check-out',
            'Status',
            'Room Numbers',
            'Total Price',
            'Payable Amount',
            'Payment ID',
            'Reference No',
            'Booking Created At',
        ];
    }
}
