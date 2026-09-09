<?php

namespace App\Support;

final class BookingCharges
{
    public const MATTRESS_PRICE = 500;

    public static function seniorPwdDiscount(float $roomTotal, int $capacity, int $eligibleGuests): float
    {
        return round(($roomTotal / max(1, $capacity)) * 0.20 * $eligibleGuests, 2);
    }
}
