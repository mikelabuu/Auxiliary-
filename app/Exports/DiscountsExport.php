<?php

namespace App\Exports;

use App\Models\Discount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class DiscountsExport implements FromCollection, WithHeadings
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
        $query = Discount::with(['booking', 'reviewer']);

        // Optional date range filter (request date)
        if ($this->request?->has('from_date') && $this->request?->has('to_date')) {
            $query->whereBetween('created_at', [$this->request->from_date, $this->request->to_date]);
        }

        // Filter by type/status
        switch ($this->type) {
            case 'pending':
                $query->where('status', 'pending');
                break;
            case 'approved':
                $query->where('status', 'approved');
                break;
            case 'rejected':
                $query->where('status', 'rejected');
                break;
        }

        $discounts = $query->get();

        return $discounts->map(function($discount) {
            return [
                'Discount ID' => $discount->id,
                'Booking ID' => $discount->booking_id,
                'Guest Name' => $discount->booking?->guest_name ?? '',
                'Number of Seniors/PWD' => $discount->booking?->num_seniors ?? 0,
                'Status' => ucfirst($discount->status),
                'Approved Amount' => $discount->amount ?? 0,
                'Reviewed By' => $discount->reviewer?->name ?? '',
                'Reviewed At' => $discount->reviewed_at ? Carbon::parse($discount->reviewed_at)->setTimezone(config('hostel.timezone'))->format('Y-m-d H:i:s') : '',
                'Request Date' => Carbon::parse($discount->created_at)->setTimezone(config('hostel.timezone'))->format('Y-m-d H:i:s'),
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
