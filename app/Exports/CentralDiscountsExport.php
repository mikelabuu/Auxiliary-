<?php

namespace App\Exports;

use App\Models\Discount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class CentralDiscountsExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Discount::with(['booking', 'reviewer']);

        if (!empty($this->request?->from_date) && !empty($this->request?->to_date)) {
            $query->whereBetween('created_at', [$this->request->from_date, $this->request->to_date]);
        }

        if (!empty($this->request?->status)) {
            $query->where('status', $this->request->status);
        }
        $discounts = $query->get();

        return $discounts->map(function($d) {
            return [
                'Discount ID' => $d->id,
                'Booking ID' => $d->booking_id,
                'Guest Name' => $d->booking?->guest_name ?? '',
                'Number of Seniors/PWD' => $d->booking?->num_seniors ?? 0,
                'Status' => ucfirst($d->status),
                'Approved Amount' => $d->amount ?? 0,
                'Reviewed By' => $d->reviewer?->name ?? '',
                'Reviewed At' => $d->reviewed_at ? Carbon::parse($d->reviewed_at)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : '',
                'Request Date' => Carbon::parse($d->created_at)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Discount ID',
            'Booking ID',
            'Guest Name',
            'Number of Seniors/PWD',
            'Status',
            'Approved Amount',
            'Reviewed By',
            'Reviewed At',
            'Request Date',
        ];
    }
}
