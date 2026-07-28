<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 *
 * The Room model does not use HasFactory, so build through the class:
 * RoomFactory::new()->create(). See Make::room().
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_number' => (string) fake()->unique()->numberBetween(100, 999),
            'room_type'   => 'double',
            'wing'        => 'Farmers Hostel',
            'price'       => 1800.00,
            'status'      => 'available',
            'notes'       => null,
        ];
    }

    public function number(string $number): static
    {
        return $this->state(fn () => ['room_number' => $number]);
    }

    public function type(string $type): static
    {
        return $this->state(fn () => ['room_type' => $type]);
    }

    /**
     * Housekeeping states. A room in any of these must never be bookable,
     * regardless of what a stale guest tab is showing.
     */
    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function maintenance(): static
    {
        return $this->status('maintenance');
    }

    public function cleaning(): static
    {
        return $this->status('cleaning');
    }

    public function occupied(): static
    {
        return $this->status('occupied');
    }
}
