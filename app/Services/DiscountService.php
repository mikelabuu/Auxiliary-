<?php

namespace App\Services;

use App\Models\Discount;

class DiscountService
{
    /**
     * Statutory senior-citizen / PWD discount on a guest's share of the room.
     */
    private const RATE = 0.20;

    /**
     * Calculate the total discount from the approved ID files.
     *
     * Everything comes from the reservation rows, which record what the guest
     * was actually quoted: `price` is the nightly rate at the time of booking
     * and `capacity` the bed count it was priced against. Reading them here
     * means a later admin edit to rates or capacity cannot retroactively change
     * the discount on a booking that has already been sold.
     *
     * This previously read the live `rooms.price` alongside a capacity
     * hardcoded in a `match` expression, so the per-head figure drifted from
     * the price the guest was charged as soon as either was edited — and an
     * unrecognised room type fell through to a capacity of 1, inflating the
     * per-head rate to the entire room rate.
     */
    public function calculate(Discount $discount): float
    {
        $booking = $discount->booking;
        $nights  = max(1, $booking->check_in->diffInDays($booking->check_out));

        $discountAmount = 0.0;

        foreach ($booking->reservations as $reservation) {
            $capacity = max(1, (int) $reservation->capacity);
            $perHead  = ((float) $reservation->price / $capacity) * $nights;

            $approvedSeniors = $discount->files()
                ->where('reservation_id', $reservation->id)
                ->where('status', 'approved')
                ->count();

            // Never grant more discounted heads than the guest declared as
            // seniors for this room. The clamp used to be `num_guests`, which
            // left the declared senior count — validated at booking against
            // both room capacity and total guests — with no effect at all, so
            // a booking declaring one senior could collect two discounts.
            //
            // If the intended rule is the reverse (staff verify reality at the
            // desk and the declared figure is only an estimate), change
            // `num_seniors` to `num_guests` here: that one word is the whole
            // policy.
            $eligible = min($approvedSeniors, (int) $reservation->num_seniors);

            $discountAmount += $perHead * self::RATE * $eligible;
        }

        return $discountAmount;
    }
}
