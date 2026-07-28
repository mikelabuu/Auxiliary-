<?php

namespace Tests\Support;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use Database\Factories\PaymentFactory;
use Database\Factories\ReservationFactory;
use Database\Factories\RoomFactory;
use Database\Factories\RoomTypeFactory;
use Database\Factories\StaffFactory;

/**
 * Domain builders for the test suite.
 *
 * Several models (Room, Staff, Payment, Reservation, RoomType) do not use the
 * HasFactory trait, so `Model::factory()` is unavailable on them. These helpers
 * go through the factory classes directly, which keeps the tests readable and
 * avoids modifying application models purely to enable testing.
 */
class Make
{
    /**
     * Seed the room_types table so RoomCatalog reports real prices.
     *
     * RoomCatalog overlays the DB on top of config/room_types.php and the DB
     * always wins, so any test asserting on price must call this first —
     * otherwise the config defaults are used and the expected totals drift.
     *
     * Mirrors the slugs shipped in config/room_types.php.
     */
    public static function catalog(): void
    {
        $types = [
            ['double',     1800.00, 2],
            ['triple',     2400.00, 3],
            ['quadruple',  2800.00, 4],
            ['deluxe',     3000.00, 2],
            ['dormitory1', 2500.00, 5],
            ['dormitory2', 3000.00, 6],
        ];

        foreach ($types as [$slug, $price, $capacity]) {
            RoomTypeFactory::new()->slug($slug)->price($price)->capacity($capacity)->create();
        }
    }

    public static function roomType(string $slug, float $price, int $capacity): RoomType
    {
        return RoomTypeFactory::new()->slug($slug)->price($price)->capacity($capacity)->create();
    }

    public static function room(string $number, string $type = 'double', string $status = 'available'): Room
    {
        return RoomFactory::new()->number($number)->type($type)->status($status)->create();
    }

    /**
     * Create several rooms of one type at once.
     *
     * @param  array<int, string>  $numbers
     * @return array<int, Room>
     */
    public static function rooms(array $numbers, string $type = 'double'): array
    {
        return array_map(fn ($n) => static::room($n, $type), $numbers);
    }

    public static function user(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    public static function unverifiedUser(): User
    {
        return User::factory()->unverified()->create();
    }

    public static function staff(string $role = 'admin', array $attributes = []): Staff
    {
        return StaffFactory::new()->role($role)->create($attributes);
    }

    public static function booking(array $attributes = []): Booking
    {
        return Booking::factory()->create($attributes);
    }

    /**
     * A booking that actually holds rooms — i.e. one with reservation rows,
     * which is what the availability endpoints read.
     *
     * @param  array<int, string>  $roomNumbers
     */
    public static function bookingHolding(
        array $roomNumbers,
        string $status = 'paid',
        ?string $checkIn = null,
        ?string $checkOut = null,
        string $roomType = 'double',
        array $attributes = [],
    ): Booking {
        $checkIn  ??= now('Asia/Manila')->addDay()->toDateString();
        $checkOut ??= now('Asia/Manila')->addDays(3)->toDateString();

        $booking = Booking::factory()->create($attributes + [
            'status'    => $status,
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]);

        foreach ($roomNumbers as $number) {
            ReservationFactory::new()->forBooking($booking)->room($number, $roomType)->create();
        }

        // Mirror what BookingController::store() does after committing: attach
        // the booking_room pivot rows the overlap guard reads.
        $roomIds = Room::whereIn('room_number', $roomNumbers)->pluck('id')->all();
        if ($roomIds) {
            $booking->rooms()->attach($roomIds);
        }

        return $booking;
    }

    public static function reservation(Booking $booking, string $roomNumber, string $type = 'double'): Reservation
    {
        return ReservationFactory::new()->forBooking($booking)->room($roomNumber, $type)->create();
    }

    public static function payment(Booking $booking, string $status = 'pending'): Payment
    {
        return PaymentFactory::new()->forBooking($booking)->status($status)->create();
    }
}
