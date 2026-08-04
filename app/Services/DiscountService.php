<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Room;
use App\Support\RoomCatalog;

class DiscountService
{
    /**
     * Calculate total discount amount based on approved files.
     *
     * @param  \App\Models\Discount  $discount
     * @return float
     */
    public function calculate(Discount $discount): float
    {
        $booking = $discount->booking;
        $nights = $booking->check_in->diffInDays($booking->check_out);

        $discountAmount = 0;

        foreach ($booking->reservations as $reservation) {
            $room = Room::where('room_number', $reservation->room_number)->first();

            if (!$room) {
                continue;
            }

            // Room capacity, from the catalog rather than a local match.
            //
            // This divides into the room price to get the per-head rate a
            // senior discount is calculated against, so the number is money.
            // Capacity is admin-editable under Room Types & Pricing, and the
            // hardcoded map that used to live here could not see those edits:
            // raising a room from 4 beds to 5 left every senior on it being
            // discounted against the old, higher per-head price indefinitely,
            // with nothing to indicate the figure had gone stale.
            $capacity = RoomCatalog::capacityFor($room->room_type, 1);

            // Per-head price for this room
            $perHead = ($room->price / $capacity) * $nights;

            // Count approved seniors tied to this reservation
            $approvedSeniors = $discount->files()
                ->where('reservation_id', $reservation->id)
                ->where('status', 'approved')
                ->count();

            // Clamp to max seniors assigned in this reservation
            $maxAllowed = min($approvedSeniors, $reservation->num_guests);

            // Apply discount per approved senior
            $discountAmount += $perHead * 0.20 * $maxAllowed;
        }

        return $discountAmount;
    }
}
