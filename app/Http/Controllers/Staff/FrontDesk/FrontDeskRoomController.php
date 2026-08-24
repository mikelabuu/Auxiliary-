<?php

namespace App\Http\Controllers\Staff\FrontDesk;

use App\Models\Booking;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Support\RoomStays;
use Carbon\Carbon;

class FrontDeskRoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();

        // Same derivation as the admin board: a room a guest has paid for and
        // is due into tonight is not one the desk can hand to a walk-in, so it
        // must not read Available here either.
        $displayStatuses = RoomStays::displayStatuses(
            $rooms,
            RoomStays::context(Carbon::today(config('hostel.timezone')))
        );

        $counts = collect($displayStatuses)->countBy();

        $totalRooms = $rooms->count();
        $occupiedRooms = $counts->get('occupied', 0);
        $availableRooms = $counts->get('available', 0);
        $reservedRooms = $counts->get('reserved', 0) + $counts->get('pending', 0);
        $maintenanceRooms = $counts->get('maintenance', 0);
        $cleaningRooms = $counts->get('cleaning', 0);

        $prices = Room::select('room_type', \DB::raw('MAX(price) as price')) ->groupBy('room_type') ->pluck('price', 'room_type');

        return view('staff.frontdesk.rooms.index', compact(
            'rooms',
            'displayStatuses',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'reservedRooms',
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
            ->where('bookings.check_in', '<=', $today)
            ->where('bookings.check_out', '>=', $today)
            ->where('bookings.status', Booking::STATUS_ACTIVE)
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
