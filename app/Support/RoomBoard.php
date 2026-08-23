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
        //
        // Current and upcoming stays come from one pass. They used to be two
        // queries with a gap between them: "upcoming" meant check_in after
        // *now*, and "current" compared a UTC-midnight date against midnight
        // in Manila. A stay starting today satisfied neither, so on the one
        // morning it mattered most — arrival day — the room read AVAILABLE.
        $today = Carbon::today(config('hostel.timezone'));
        $todayKey = $today->toDateString();

        $stays = DB::table('booking_room')
            ->join('bookings', 'booking_room.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
            ->where('bookings.check_out', '>=', $todayKey)
            ->orderBy('bookings.check_in')
            ->get([
                'booking_room.room_id',
                'bookings.guest_name',
                'bookings.check_in',
                'bookings.check_out',
                'bookings.status',
            ]);

        // Who is actually in each room — 'occupied' with no booking behind it
        // gets an explicit "No guest on record" instead of implying a guest.
        $currentByRoom = [];
        $nextByRoom = [];
        $holdStatusByRoom = [];
        foreach ($stays as $stay) {
            $checkIn = Carbon::parse($stay->check_in);
            $checkOut = Carbon::parse($stay->check_out);
            $inKey = $checkIn->toDateString();
            $outKey = $checkOut->toDateString();

            if ($inKey <= $todayKey && $outKey >= $todayKey) {
                if (! isset($currentByRoom[$stay->room_id])) {
                    $currentByRoom[$stay->room_id] = $stay->guest_name . ' · until ' . $checkOut->format('M d');
                    // A stay in the room now always speaks for the tile; a
                    // later arrival cannot make tonight's room available.
                    $holdStatusByRoom[$stay->room_id] = $stay->status;
                }
            } elseif ($inKey > $todayKey && ! isset($nextByRoom[$stay->room_id])) {
                $nextByRoom[$stay->room_id] = $stay->guest_name . ' · arrives ' . $checkIn->format('M d');
                $holdStatusByRoom[$stay->room_id] ??= $stay->status;
            }
        }

        return Room::all()->map(function ($room) use ($currentByRoom, $nextByRoom, $holdStatusByRoom) {
            // 'reserved' = settled and holding the room. 'pending' = claimed
            // but the money has not been verified, so it can still lapse.
            $displayStatus = RoomHold::displayStatus(
                $room->status,
                $holdStatusByRoom[$room->id] ?? null
            );

            $current = $currentByRoom[$room->id] ?? null;
            $next = $nextByRoom[$room->id] ?? null;

            // 'reserved'/'pending' now also cover a stay that starts today but
            // has not been checked in, so the occupant is whoever holds the
            // room now, falling back to the next arrival.
            $occupant = $displayStatus === 'occupied'
                ? ($current ?: 'No guest on record')
                : ($current ?: $next);

            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->room_type,
                // The map lays itself out by wing (Room::groupByWing).
                'wing' => $room->wing,
                'status' => $room->status,
                'display_status' => $displayStatus,
                'occupant' => $occupant,
                'updated_at' => $room->updated_at ? $room->updated_at->diffForHumans() : null,
            ];
        });
    }
}
