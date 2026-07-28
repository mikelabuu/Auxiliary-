<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'booking_id'   => Booking::factory()->pendingDiscount(),
            'amount'       => 0.00,
            'status'       => 'pending',
            'submitted_at' => now(),
        ];
    }

    public function forBooking(Booking $booking): static
    {
        return $this->state(fn () => ['booking_id' => $booking->id]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status'      => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status'      => 'rejected',
            'reviewed_at' => now(),
        ]);
    }
}
