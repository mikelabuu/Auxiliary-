<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Room;

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

            // Room capacity
            $capacity = match (strtolower(trim($room->room_type))) {
                'double' => 2,
                'triple' => 3,
                'quadruple' => 4,
                'deluxe' => 2,
                'dormitory1' => 5,
                'dormitory2' => 6,
                default => 1,
            };

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
