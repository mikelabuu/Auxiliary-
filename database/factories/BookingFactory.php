<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn  = now('Asia/Manila')->addDay()->startOfDay();
        $checkOut = (clone $checkIn)->addDays(2);

        return [
            'user_id'         => User::factory(),
            'guest_name'      => fake()->lastName() . ', ' . fake()->firstName(),
            'guest_address'   => fake()->city(),
            'guest_phone'     => '09' . fake()->numerify('#########'),
            'check_in'        => $checkIn->toDateString(),
            'check_out'       => $checkOut->toDateString(),
            'expected_guests' => 2,
            'num_seniors'     => 0,
            'discount'        => 0,
            'total_price'     => 3600.00,
            'payable_amount'  => null,
            'wants_discount'  => false,
            'status'          => 'pending_payment',
            'payment_mode'    => 'system',
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function paid(): static
    {
        return $this->status('paid');
    }

    public function active(): static
    {
        return $this->status('active');
    }

    public function pendingPayment(): static
    {
        return $this->status('pending_payment');
    }

    public function pendingDiscount(): static
    {
        return $this->state(fn () => [
            'status'         => 'pending_discount',
            'wants_discount' => true,
            'num_seniors'    => 1,
        ]);
    }

    public function cancelled(): static
    {
        return $this->status('cancelled');
    }

    /**
     * Pin the stay window. Accepts anything Carbon can parse.
     */
    public function dates(string $checkIn, string $checkOut): static
    {
        return $this->state(fn () => [
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]);
    }

    /** Named `owner` rather than `for` — Factory::for() is already taken. */
    public function owner(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /**
     * A booking left unpaid since $minutes ago — the shape `bookings:expire`
     * is meant to sweep up.
     */
    public function stalePayment(int $minutes = 60): static
    {
        return $this->state(fn () => [
            'status'                => 'pending_payment',
            'pending_payment_since' => now()->subMinutes($minutes),
        ]);
    }
}
