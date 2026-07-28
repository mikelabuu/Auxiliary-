<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 *
 * Payment does not use HasFactory — build via PaymentFactory::new() or
 * Make::payment().
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id'       => Booking::factory(),
            'user_id'          => null,
            'amount'           => 3600.00,
            'status'           => 'pending',
            'payment_type'     => 'online',
            'reference_no'     => strtoupper(Str::random(10)),
            'gateway'          => 'sandbox',
            'webhook_verified' => false,
        ];
    }

    public function forBooking(Booking $booking): static
    {
        return $this->state(fn () => [
            'booking_id' => $booking->id,
            'user_id'    => $booking->user_id,
            'amount'     => $booking->payable_amount ?? $booking->total_price,
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function success(): static
    {
        return $this->status('success');
    }

    public function failed(): static
    {
        return $this->status('failed');
    }
}
