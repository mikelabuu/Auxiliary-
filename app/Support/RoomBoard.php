<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Room;
use App\Support\RoomHold;
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
        //
        // This used to look only at 'paid'. A room held by a booking that had
        // not been paid for yet therefore showed as AVAILABLE on the map while
        // the booking engine was already refusing to sell it — the desk could
        // promise a room the system would not give them. Pending holds now
        // show too, in their own state, so the map matches reality.
        $upcoming = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
            ->where('bookings.check_in', '>', Carbon::now('Asia/Manila'))
            ->get(['booking_room.room_id', 'bookings.status']);

        // A settled hold outranks a pending one on the same room, so a paid
        // arrival is never downgraded to "awaiting payment" by a stale claim.
        $upcomingByRoom = [];
        foreach ($upcoming as $row) {
            $roomId = $row->room_id;
            if (! isset($upcomingByRoom[$roomId]) || ! RoomHold::isPending($row->status)) {
                $upcomingByRoom[$roomId] = $row->status;
            }
        }

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

        return Room::all()->map(function ($room) use ($upcomingByRoom, $currentByRoom, $nextByRoom) {
            $upcomingStatus = $upcomingByRoom[$room->id] ?? null;

            if ($room->status === 'maintenance') {
                $displayStatus = 'maintenance';
            } elseif ($room->status === 'cleaning') {
                $displayStatus = 'cleaning';
            } elseif ($room->status === 'occupied') {
                $displayStatus = 'occupied';
            } elseif ($upcomingStatus !== null) {
                // 'reserved' = paid and arriving. 'pending' = claimed but the
                // money has not been verified, so it can still lapse.
                $displayStatus = RoomHold::isPending($upcomingStatus) ? 'pending' : 'reserved';
            } else {
                $displayStatus = 'available';
            }

            $current = $currentByRoom[$room->id] ?? null;
            $next = $nextByRoom[$room->id] ?? null;

            if ($displayStatus === 'occupied') {
                $occupant = $current ?: 'No guest on record';
            } elseif ($displayStatus === 'reserved' || $displayStatus === 'pending') {
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
                'updated_at' => $room->updated_at ? $room->updated_at->diffForHumans() : null,
            ];
        });
    }
}
