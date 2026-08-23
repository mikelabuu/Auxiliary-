<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Reservation;
use Carbon\Carbon;

/**
 * Which stay is in a room today, and which one arrives next — keyed by room
 * number, from `reservations` (the authoritative record of which rooms a
 * booking holds).
 *
 * Lived as a private method on RoomController; extracted so the front desk
 * board reads the same answer as the admin board instead of re-deriving it.
 */
class RoomStays
{
    /**
     * @param  Carbon  $today  the business date (Manila), from the caller
     * @return array<string, array{current?: array{guest:string,until:string,status:string}, next?: array{guest:string,from:string,status:string}}>
     */
    public static function context(Carbon $today): array
    {
        $stays = Reservation::query()
            ->join('bookings', 'bookings.id', '=', 'reservations.booking_id')
            ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
            ->where('bookings.check_out', '>=', $today->toDateString())
            ->orderBy('bookings.check_in')
            ->get([
                'reservations.room_number',
                'bookings.guest_name',
                'bookings.check_in',
                'bookings.check_out',
                // Carried so the card can say whether the hold is paid for —
                // see App\Support\RoomHold. Aliased because `status` would
                // otherwise collide with the room's own housekeeping status.
                'bookings.status as booking_status',
            ]);

        // Compared as business dates, never as instants. check_in/check_out are
        // dates stored at midnight and read back as UTC, while $today is
        // midnight in Manila — eight hours earlier. A stay starting *today*
        // therefore came out "greater than today" and was filed as a future
        // arrival, so the room it was sitting in reported itself free.
        $todayKey = $today->toDateString();

        $stayContext = [];
        foreach ($stays as $stay) {
            $roomNumber = trim($stay->room_number);
            $checkIn  = Carbon::parse($stay->check_in);
            $checkOut = Carbon::parse($stay->check_out);
            $inKey    = $checkIn->toDateString();
            $outKey   = $checkOut->toDateString();

            if ($inKey <= $todayKey && $outKey >= $todayKey) {
                $stayContext[$roomNumber]['current'] ??= [
                    'guest'  => $stay->guest_name,
                    'until'  => $checkOut->format('M d'),
                    'status' => $stay->booking_status,
                ];
            } elseif (!isset($stayContext[$roomNumber]['next']) && $inKey > $todayKey) {
                $stayContext[$roomNumber]['next'] = [
                    'guest'  => $stay->guest_name,
                    'from'   => $checkIn->format('M d'),
                    'status' => $stay->booking_status,
                ];
            }
        }

        return $stayContext;
    }

    /**
     * The board status for each room id, from a stay context and the rooms'
     * housekeeping column.
     *
     * `$reach` decides which hold the badge answers for. 'today' is "can I
     * give this room out tonight" — the question both room boards are there
     * to answer. 'upcoming' also lets a future arrival colour the tile, which
     * is what the dashboard's status map does.
     *
     * @param  iterable<\App\Models\Room>  $rooms
     * @return array<int, string> room id => one of RoomHold::DISPLAY_STATUSES
     */
    public static function displayStatuses(iterable $rooms, array $stayContext, string $reach = 'today'): array
    {
        $statuses = [];

        foreach ($rooms as $room) {
            $ctx = $stayContext[trim($room->room_number)] ?? [];
            $hold = $ctx['current'] ?? ($reach === 'upcoming' ? ($ctx['next'] ?? null) : null);

            $statuses[$room->id] = RoomHold::displayStatus($room->status, $hold['status'] ?? null);
        }

        return $statuses;
    }
}
