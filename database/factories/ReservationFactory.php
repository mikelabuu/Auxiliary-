<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 *
 * Reservations are the authoritative per-room record — both availability
 * endpoints read room occupancy from here, not from the booking_room pivot.
 * A test that seeds a "blocking" booking must create reservation rows or the
 * room will still read as free.
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'booking_id'  => Booking::factory(),
            'room_number' => (string) fake()->numberBetween(100, 999),
            'room_type'   => 'double',
            'capacity'    => 2,
            'num_seniors' => 0,
            'num_guests'  => 2,
            'price'       => 1800.00,
            'meal'        => ['breakfast' => 2],
        ];
    }

    public function room(string $number, string $type = 'double'): static
    {
        return $this->state(fn () => [
            'room_number' => $number,
            'room_type'   => $type,
        ]);
    }

    public function forBooking(Booking $booking): static
    {
        return $this->state(fn () => ['booking_id' => $booking->id]);
    }
}
