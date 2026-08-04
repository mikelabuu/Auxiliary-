<?php

namespace App\Http\Controllers\Staff\frontdesk;

use App\Http\Controllers\Controller;
use App\Models\Room;

class FrontDeskRoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();

        $totalRooms = $rooms->count();
        $occupiedRooms = $rooms->where('status', 'occupied')->count();
        $availableRooms = $rooms->where('status', 'available')->count();
        $maintenanceRooms = $rooms->where('status', 'maintenance')->count();
        $cleaningRooms = $rooms->where('status', 'cleaning')->count();

        $prices = Room::select('room_type', \DB::raw('MAX(price) as price')) ->groupBy('room_type') ->pluck('price', 'room_type');

        return view('staff.frontdesk.rooms.index', compact(
            'rooms',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'maintenanceRooms',
            'cleaningRooms',
            'prices'
        ));
    }

    public function occupancyForRoom(Room $room)
    {
        $today = now(config('hostel.timezone'))->toDateString();

        $bookings = \App\Models\Booking::query()
            ->select(
                'bookings.id as booking_id',
                'bookings.guest_name',
                'bookings.status',
                'bookings.check_in',
                'bookings.check_out'
            )
            ->join('reservations', 'reservations.booking_id', '=', 'bookings.id')
            ->where('reservations.room_number', $room->room_number)
            ->whereDate('bookings.check_in', '<=', $today)
            ->whereDate('bookings.check_out', '>=', $today)
            ->where('bookings.status', 'active')
            ->distinct()
            ->get();

        $bookings = $bookings->map(function ($b) {
            return [
                'id' => $b->booking_id,
                'guest_name' => $b->guest_name,
                'status' => ucfirst($b->status),
                'check_in_formatted' => \Carbon\Carbon::parse($b->check_in)->format('M d, Y'),
                'check_out_formatted' => \Carbon\Carbon::parse($b->check_out)->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'bookings' => $bookings,
        ]);
    }

}
