<?php

namespace Tests\Support;

/**
 * Fluent builder for the POST /booking payload.
 *
 * BookingController::store() takes fifteen top-level fields plus a nested
 * `reservations` array, and validation rejects the request long before any
 * interesting logic runs if one is missing. Rather than repeat that blob in
 * every test, start from a known-valid baseline and mutate the one field
 * under test:
 *
 *     BookingPayload::make()->block('double', ['101'], guests: 2)->toArray()
 *     BookingPayload::make()->guests(5)->toArray()   // now mismatched on purpose
 *
 * Location fields are posted as "CODE|Name" pairs because store() rebuilds the
 * guest address with explode('|', $value)[1].
 */
class BookingPayload
{
    /** @var array<string, mixed> */
    protected array $data;

    /** @var array<int, array<string, mixed>> */
    protected array $blocks = [];

    protected function __construct()
    {
        $checkIn  = now('Asia/Manila')->addDay()->toDateString();
        $checkOut = now('Asia/Manila')->addDays(3)->toDateString();

        $this->data = [
            'first_name'      => 'Juan',
            'middle_name'     => 'Cruz',        // column caps at 10 chars
            'last_name'       => 'Dela Cruz',
            'suffix'          => null,
            'guest_phone'     => '09171234567',
            'check_in'        => $checkIn,
            'check_out'       => $checkOut,
            'expected_guests' => 2,
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

    /**
     * Set expected_guests independently of what the blocks add up to, so a test
     * can drive the "guests assigned must equal expected guests" guard.
     */
    public function guests(int $count): static
    {
        $this->data['expected_guests'] = $count;

        return $this;
    }

    public function name(string $first, string $middle = 'Cruz', string $last = 'Dela Cruz'): static
    {
        $this->data['first_name']  = $first;
        $this->data['middle_name'] = $middle;
        $this->data['last_name']   = $last;

        return $this;
    }

    public function phone(string $phone): static
    {
        $this->data['guest_phone'] = $phone;

        return $this;
    }

    public function requestDiscount(bool $want = true): static
    {
        $this->data['request_discount'] = $want ? '1' : '0';

        return $this;
    }

    /**
     * Add one reservation block. Room numbers are posted as a CSV string,
     * matching what the checkout page submits.
     *
     * `meals` defaults to a single breakfast per guest because store() rejects
     * any block whose meal counts do not sum to its guest count.
     *
     * `pricePerNight` and `beds` are accepted so a test can post tampered
     * values — the backend is supposed to ignore both and use RoomCatalog.
     */
    public function block(
        string $roomType,
        array $roomNumbers,
        int $guests = 2,
        int $seniors = 0,
        ?array $meals = null,
        ?float $pricePerNight = null,
        ?int $beds = null,
    ): static {
        $this->blocks[] = [
            'room_type'       => $roomType,
            'room_number'     => implode(',', $roomNumbers),
            'num_guests'      => $guests,
            'num_seniors'     => $seniors,
            'meal'            => $meals ?? ['breakfast' => $guests],
            'price_per_night' => $pricePerNight,
            'beds'            => $beds,
        ];

        return $this;
    }

    /** Overwrite or add a single top-level field. */
    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    /** Remove a top-level field, to exercise `required` rules. */
    public function without(string $key): static
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // A block is only added explicitly; default to one double room so the
        // happy path is a single ->toArray() call.
        $blocks = $this->blocks ?: [[
            'room_type'       => 'double',
            'room_number'     => '101',
            'num_guests'      => 2,
            'num_seniors'     => 0,
            'meal'            => ['breakfast' => 2],
            'price_per_night' => null,
            'beds'            => null,
        ]];

        return $this->data + ['reservations' => $blocks];
    }
}
