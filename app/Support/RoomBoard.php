<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Per-room display state for the dashboard Room Status Map and any card that
 * counts "available right now". Lived as a private method on the dashboard
 * controller; extracted so Livewire components can share the exact same
 * definition instead of re-deriving their own (which is how the Occupancy
 * card came to disagree with the map).
 */
class RoomBoard
{
    public static function state(): Collection
    {
        // Business dates are Manila; the app timezone is UTC, so bare
        // now()/today() are a day behind between midnight and 8 AM Manila.
        $reservedRoomIds = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->where('bookings.status', 'paid')
            ->where('bookings.check_in', '>', Carbon::now('Asia/Manila'))
            ->pluck('booking_room.room_id')
            ->toArray();

        // Who is actually in each room — 'occupied' with no booking behind it
        // gets an explicit "No guest on record" instead of implying a guest.
        $today = Carbon::today('Asia/Manila');

        $stays = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
            ->whereDate('bookings.check_out', '>=', $today)
            ->orderBy('bookings.check_in')
            ->get(['booking_room.room_id', 'bookings.guest_name', 'bookings.check_in', 'bookings.check_out']);

        $currentByRoom = [];
        $nextByRoom = [];
        foreach ($stays as $stay) {
            $checkIn = Carbon::parse($stay->check_in);
            $checkOut = Carbon::parse($stay->check_out);

            if ($checkIn->lte($today) && $checkOut->gte($today)) {
                $currentByRoom[$stay->room_id] ??= $stay->guest_name . ' · until ' . $checkOut->format('M d');
            } elseif ($checkIn->gt($today)) {
                $nextByRoom[$stay->room_id] ??= $stay->guest_name . ' · arrives ' . $checkIn->format('M d');
            }
        }

        return Room::all()->map(function ($room) use ($reservedRoomIds, $currentByRoom, $nextByRoom) {
            if ($room->status === 'maintenance') {
                $displayStatus = 'maintenance';
            } elseif ($room->status === 'cleaning') {
                $displayStatus = 'cleaning';
            } elseif ($room->status === 'occupied') {
                $displayStatus = 'occupied';
            } elseif (in_array($room->id, $reservedRoomIds)) {
                $displayStatus = 'reserved';
            } else {
                $displayStatus = 'available';
            }

            $current = $currentByRoom[$room->id] ?? null;
            $next = $nextByRoom[$room->id] ?? null;

            if ($displayStatus === 'occupied') {
                $occupant = $current ?: 'No guest on record';
            } elseif ($displayStatus === 'reserved') {
                $occupant = $next;
            } else {
                $occupant = $current ?: $next;
            }

            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->room_type,
                'status' => $room->status,
                'display_status' => $displayStatus,
                'occupant' => $occupant,
            ];
        });
    }
}
