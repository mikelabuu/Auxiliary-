<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingPayloads;
use Tests\TestCase;

/**
 * The staff booking screens price a stay from the rooms table, never from the
 * form.
 *
 * The admin "Manual Booking" and front desk "Walk-In" screens are the same
 * flow behind two doors. They were two copies, and the copies drifted: the
 * admin side was hardened to recompute the nightly rate server-side, verify
 * the posted room actually exists as the posted type, and reject rooms the
 * desk had just closed. The walk-in copy kept none of that — it wrote
 * `price_per_night` straight from the request into the reservation, so a
 * tampered form could book a ₱3,000 room for ₱1.
 *
 * Both now share App\Http\Controllers\Staff\Concerns\CreatesStaffBooking.
 * These tests run against *both* routes so the two can never diverge again.
 */
class StaffBookingPricingTest extends TestCase
{
    use BuildsBookingPayloads;
    use RefreshDatabase;

    /** Both doors into the same flow: [store route, redirect route]. */
    public static function bookingRoutes(): array
    {
        return [
            'admin manual booking' => ['staff.manualbooking.store'],
            'front desk walk-in'   => ['frontdesk.walkin.store'],
        ];
    }

    private function actingAsStaff(): Staff
    {
        $staff = Staff::create([
            'name' => 'Desk Clerk',
            'email' => 'desk@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'master_admin',
            'is_suspended' => false,
        ]);

        $this->actingAs($staff, 'staff');

        return $staff;
    }

    private function room(string $number = '201', string $type = 'deluxe', float $price = 3000, string $status = 'available'): Room
    {
        RoomType::firstOrCreate(
            ['slug' => $type],
            ['name' => ucfirst($type), 'base_price' => $price, 'capacity' => 4]
        );

        return Room::create([
            'room_number' => $number,
            'room_type' => $type,
            'wing' => 'rooster',
            'status' => $status,
            'price' => $price,
        ]);
    }

    /** A valid payload, with the reservation block overridable per test. */
    private function payload(array $reservationOverrides = [], array $overrides = []): array
    {
        return array_merge([
            'guest_name' => 'Juan Dela Cruz',
            'guest_phone' => '09171234567',
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'expected_guests' => 2,
            // Real nine-digit codes: App\Rules\PsgcCode checks the shape and
            // resolves each against the committed gazetteer, so the invented
            // 'R3|…' placeholders these used to carry failed validation and no
            // booking was ever created — every assertion below then died on a
            // missing Reservation rather than on what it meant to test.
            'region_code' => self::PSGC_REGION,
            'province_code' => self::PSGC_PROVINCE,
            'city_code' => self::PSGC_CITY,
            'barangay_code' => self::PSGC_BARANGAY,
            'reservations' => [
                array_merge([
                    'room_type' => 'deluxe',
                    'room_number' => '201',
                    'price_per_night' => 3000,
                    'num_guests' => 2,
                    'num_seniors' => 0,
                ], $reservationOverrides),
            ],
        ], $overrides);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bookingRoutes')]
    public function test_posted_price_is_ignored_in_favour_of_the_rooms_table(string $route): void
    {
        $this->actingAsStaff();
        $this->room(price: 3000);

        // Two nights at ₱3,000. The form claims ₱1.
        $this->post(route($route), $this->payload(['price_per_night' => 1]));

        $reservation = Reservation::firstOrFail();
        $booking = Booking::firstOrFail();

        $this->assertEquals(3000, (float) $reservation->price, 'Reservation kept the tampered nightly rate.');
        $this->assertEquals(6000, (float) $booking->total_price, 'Booking total was built from the tampered rate.');
        $this->assertEquals(6000, (float) $booking->payable_amount);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bookingRoutes')]
    public function test_room_type_must_match_the_room_record(string $route): void
    {
        $this->actingAsStaff();
        $this->room('201', 'deluxe', 3000);
        // A cheap type exists, but room 201 is not one of them.
        RoomType::firstOrCreate(['slug' => 'double'], ['name' => 'Double', 'base_price' => 500, 'capacity' => 2]);

        $response = $this->post(route($route), $this->payload(['room_type' => 'double']));

        $response->assertSessionHasErrors('reservations');
        $this->assertSame(0, Booking::count(), 'A mismatched room type still created a booking.');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bookingRoutes')]
    public function test_rooms_under_maintenance_are_rejected(string $route): void
    {
        $this->actingAsStaff();
        $this->room('201', 'deluxe', 3000, status: 'maintenance');

        $response = $this->post(route($route), $this->payload());

        $response->assertSessionHasErrors('reservations');
        $this->assertSame(0, Booking::count(), 'A room under maintenance was still booked.');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bookingRoutes')]
    public function test_unknown_room_number_is_rejected(string $route): void
    {
        $this->actingAsStaff();
        $this->room('201', 'deluxe', 3000);

        $response = $this->post(route($route), $this->payload(['room_number' => '999']));

        $response->assertSessionHasErrors('reservations');
        $this->assertSame(0, Booking::count());
    }

    /**
     * The nightly rate comes from the catalog the public site quotes, not from
     * the `rooms.price` column.
     *
     * These were two separate stores of the same number with nothing keeping
     * them in step: the guest checkout priced from `room_types.base_price` via
     * RoomCatalog, the desk priced from `rooms.price`, and only the former is
     * editable from Room Types & Pricing. They happened to agree, so the split
     * was invisible until the first rate change — at which point the website
     * and the front desk would quote different money for the same night.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bookingRoutes')]
    public function test_nightly_rate_follows_the_room_type_not_the_rooms_column(string $route): void
    {
        $this->actingAsStaff();
        $this->room('201', 'deluxe', 3000);

        // An admin raises the published rate. `rooms.price` is the stale copy
        // that no screen updates.
        RoomType::where('slug', 'deluxe')->update(['base_price' => 3500]);

        $this->post(route($route), $this->payload());

        $reservation = Reservation::firstOrFail();
        $booking = Booking::firstOrFail();

        $this->assertEquals(3500, (float) $reservation->price, 'The desk sold at the stale rooms.price rate.');
        $this->assertEquals(7000, (float) $booking->total_price, 'Two nights should follow the published rate.');
    }

    /**
     * A discount may not exceed what the stay costs.
     *
     * `discount_amount` was validated as `min:0` with no ceiling, and
     * payable_amount is `total - discount`. A mistyped figure produced a
     * booking marked paid, worth negative money, with a matching successful
     * payment row — and nothing in the flow would have said so.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bookingRoutes')]
    public function test_a_discount_larger_than_the_total_is_rejected(string $route): void
    {
        $this->actingAsStaff();
        $this->room('201', 'deluxe', 3000);

        // Two nights at ₱3,000 is ₱6,000. A slipped keystroke offers ₱99,999.
        $response = $this->post(route($route), $this->payload(overrides: ['discount_amount' => 99999]));

        $response->assertSessionHasErrors('discount_amount');
        $this->assertSame(0, Booking::count(), 'A discount beyond the stay total still created a booking.');
    }

    /** The boundary is allowed: a stay may be comped in full, just not beyond. */
    #[\PHPUnit\Framework\Attributes\DataProvider('bookingRoutes')]
    public function test_a_discount_equal_to_the_total_is_allowed(string $route): void
    {
        $this->actingAsStaff();
        $this->room('201', 'deluxe', 3000);

        $this->post(route($route), $this->payload(overrides: ['discount_amount' => 6000]));

        $booking = Booking::firstOrFail();

        $this->assertEquals(0, (float) $booking->payable_amount);
    }

    /**
     * A room already held over the same nights cannot be sold again at the desk.
     *
     * The overlap check and the room-status check used to run *before* the
     * transaction opened, with no row lock — so two staff assigning the same
     * room at once both passed, and a walk-in could not serialise against a
     * guest booking online at all (that path locks `rooms`; this one never
     * touched them). Both guards now run inside the transaction behind
     * `lockForUpdate`, matching BookingController::store.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bookingRoutes')]
    public function test_a_room_held_over_the_same_nights_is_rejected(string $route): void
    {
        $this->actingAsStaff();
        $this->room('201', 'deluxe', 3000);

        $held = Booking::create([
            'user_id' => null,
            'guest_name' => 'Earlier Guest',
            'guest_address' => 'Bantug, Munoz, Nueva Ecija',
            'guest_phone' => '09171112222',
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'expected_guests' => 1,
            'total_price' => 6000,
            'num_seniors' => 0,
            'status' => 'paid',
        ]);

        Reservation::create([
            'booking_id' => $held->id,
            'room_number' => '201',
            'room_type' => 'deluxe',
            'capacity' => 4,
            'price' => 3000,
            'num_guests' => 1,
            'num_seniors' => 0,
        ]);

        $response = $this->post(route($route), $this->payload());

        $response->assertSessionHasErrors('reservations');
        $this->assertSame(1, Booking::count(), 'The room was sold twice over the same nights.');
    }
}
