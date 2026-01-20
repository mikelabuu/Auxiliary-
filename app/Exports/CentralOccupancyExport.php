<?php

namespace App\Exports;

use App\Models\Room;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CentralOccupancyExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Room::with(['reservations.booking']);

        $rooms = $query->get();

        return $rooms->map(function($room) {
            // Filter reservations for this room_number
            $roomReservations = $room->reservations->filter(fn($r) => $r->room_number == $room->room_number);

            // Filter bookings based on request date range (when booking was made)
            if ($this->request?->from_date && $this->request?->to_date) {
                $roomReservations = $roomReservations->filter(fn($r) => 
                    $r->booking?->created_at >= $this->request->from_date &&
                    $r->booking?->created_at <= $this->request->to_date
                );
            }

            // Count only bookings with completed status
            $occupancy = $roomReservations->pluck('booking')
                ->filter(fn($b) => $b?->status === 'completed')
                ->count();

            return [
                'Room Number' => $room->room_number,
                'Room Type'   => $room->room_type,
                'Wing'        => $room->wing,
                'Occupancy' => $occupancy,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Room Number',
            'Room Type',
            'Wing',
            'Occupancy',
        ];
    }
}
