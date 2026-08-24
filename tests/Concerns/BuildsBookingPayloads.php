<?php

namespace Tests\Concerns;

/**
 * The guest booking form's POST body, in one place.
 *
 * Four test files used to hand-roll this payload, and all four drifted from
 * the controller at different rates: when checkout gained the `referred_by*`
 * fields and PsgcCode started demanding real nine-digit codes, one file still
 * posted 'R03' with no pipe at all, two posted 'R03|Central Luzon', and the
 * fourth had the codes right but none of the referrer fields. Every test that
 * expected a booking to succeed was instead asserting against a validation
 * failure — a green-looking suite that had stopped exercising store() at all.
 *
 * A required field added to BookingController::store() should break these
 * tests loudly and in one place, which is what this trait is for. Anything a
 * test genuinely wants to vary goes through $overrides; nothing should copy
 * the base array back out into a test file.
 */
trait BuildsBookingPayloads
{
    /**
     * A real PSGC chain — Bangkal, Abucay, Bataan, Central Luzon.
     *
     * These are checked against the committed gazetteer, not just for shape:
     * App\Rules\PsgcCode resolves the barangay against the city posted with
     * it, so an invented code fails even when it is nine digits long.
     */
    protected const PSGC_REGION   = '030000000|Central Luzon';
    protected const PSGC_PROVINCE = '030800000|Bataan';
    protected const PSGC_CITY     = '030801000|Abucay';
    protected const PSGC_BARANGAY = '030801001|Bangkal';

    /**
     * @param  array  $overrides  Replaces top-level keys outright — pass a
     *                            whole 'reservations' array to change rooms.
     */
    protected function bookingPayload(array $overrides = []): array
    {
        return array_replace([
            'first_name'          => 'Ana',
            'middle_name'         => 'Cruz',
            'last_name'           => 'Reyes',
            'guest_phone'         => '09171234567',
            // Required by the form since the checkout rework; the columns stay
            // nullable for walk-ins and for bookings made before they existed.
            'referred_by'         => 'Self',
            'referred_by_phone'   => '09171234567',
            'referred_by_purpose' => 'Personal visit',
            'check_in'            => now()->addDays(3)->toDateString(),
            'check_out'           => now()->addDays(5)->toDateString(),
            'expected_guests'     => 2,
            'accept_terms'        => 1,
            'region_code'         => self::PSGC_REGION,
            'province_code'       => self::PSGC_PROVINCE,
            'city_code'           => self::PSGC_CITY,
            'barangay_code'       => self::PSGC_BARANGAY,
            'reservations'        => [$this->bookingReservation()],
        ], $overrides);
    }

    /**
     * One reservation block. `num_guests` is required and must be >= 1 —
     * omitting it used to read as zero guests and pass every capacity check.
     */
    protected function bookingReservation(array $overrides = []): array
    {
        return array_replace([
            'room_type'   => 'double',
            'num_guests'  => 2,
            'num_seniors' => 0,
        ], $overrides);
    }
}
