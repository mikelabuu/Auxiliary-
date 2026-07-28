<?php

namespace Tests\Support;

/**
 * Fluent builder for the two staff-side booking endpoints:
 *
 *   POST /front-desk/store          (WalkInBookingController)
 *   POST /staff/manual-booking/store (ManualBookingController)
 *
 * Both take the same shape, which differs from the public payload: a single
 * `guest_name` rather than split name parts, one room per reservation block
 * rather than a CSV, and a `price_per_night` the client is expected to supply.
 */
class StaffBookingPayload
{
    /** @var array<string, mixed> */
    protected array $data;

    /** @var array<int, array<string, mixed>> */
    protected array $blocks = [];

    protected function __construct()
    {
        $this->data = [
            'guest_name'      => 'Dela Cruz, Juan',
            'guest_phone'     => '09171234567',
            'check_in'        => now('Asia/Manila')->addDay()->toDateString(),
            'check_out'       => now('Asia/Manila')->addDays(3)->toDateString(),
            'expected_guests' => 2,
            'discount_amount' => 0,
            'region_code'     => '04|CALABARZON',
            'province_code'   => '0421|Laguna',
            'city_code'       => '042108|Calamba',
            'barangay_code'   => '042108001|Bagong Kalsada',
        ];
    }

    public static function make(): static
    {
        return new static();
    }

    public function dates(string $checkIn, string $checkOut): static
    {
        $this->data['check_in']  = $checkIn;
        $this->data['check_out'] = $checkOut;

        return $this;
    }

    public function guests(int $count): static
    {
        $this->data['expected_guests'] = $count;

        return $this;
    }

    public function discount(float $amount): static
    {
        $this->data['discount_amount'] = $amount;

        return $this;
    }

    /**
     * One room per block. `pricePerNight` is what the client posts — the
     * interesting question is whether the backend believes it.
     */
    public function room(
        string $roomNumber,
        string $roomType = 'double',
        int $guests = 2,
        int $seniors = 0,
        float $pricePerNight = 1800.00,
    ): static {
        $this->blocks[] = [
            'room_type'       => $roomType,
            'room_number'     => $roomNumber,
            'num_guests'      => $guests,
            'num_seniors'     => $seniors,
            'price_per_night' => $pricePerNight,
        ];

        return $this;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $blocks = $this->blocks ?: [[
            'room_type'       => 'double',
            'room_number'     => '101',
            'num_guests'      => 2,
            'num_seniors'     => 0,
            'price_per_night' => 1800.00,
        ]];

        return $this->data + ['reservations' => $blocks];
    }
}
