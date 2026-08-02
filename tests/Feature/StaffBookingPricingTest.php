<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'region_code' => 'R3|Central Luzon',
            'province_code' => 'P49|Nueva Ecija',
            'city_code' => 'C123|Munoz',
            'barangay_code' => 'B456|Bantug',
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
}
