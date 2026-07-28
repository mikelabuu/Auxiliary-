<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * The two public availability endpoints:
 *   POST /rooms/available            — per-room status for one room type
 *   POST /rooms/availability-summary — open-room counts per type
 *
 * Both read occupancy from `reservations`, filtered by
 * Booking::BLOCKING_STATUSES. These are the numbers a guest sees before
 * committing to a booking, so a wrong answer here is either a lost sale or an
 * overbooking complaint at the front desk.
 */
class RoomAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
    }

    private function range(int $fromDays = 1, int $toDays = 3): array
    {
        return [
            'check_in'  => now('Asia/Manila')->addDays($fromDays)->toDateString(),
            'check_out' => now('Asia/Manila')->addDays($toDays)->toDateString(),
        ];
    }

    public function test_a_free_room_reports_as_available(): void
    {
        Make::room('101', 'double');

        $response = $this->postJson('/rooms/available', $this->range() + ['room_type' => 'double']);

        $response->assertOk()->assertJsonPath('rooms.0.status', 'available');
    }

    public function test_a_room_held_by_a_blocking_booking_reports_as_booked(): void
    {
        Make::room('101', 'double');
        $range = $this->range();

        Make::bookingHolding(['101'], 'paid', $range['check_in'], $range['check_out']);

        $response = $this->postJson('/rooms/available', $range + ['room_type' => 'double']);

        $response->assertOk()->assertJsonPath('rooms.0.status', 'booked');
    }

    /**
     * Every status in BLOCKING_STATUSES must hide the room. This is the guard
     * that stops a guest booking a room another guest is mid-checkout on.
     */
    public function test_every_blocking_status_makes_a_room_unavailable(): void
    {
        $range = $this->range();

        foreach (Booking::BLOCKING_STATUSES as $i => $status) {
            $number = (string) (200 + $i);
            Make::room($number, 'triple');
            Make::bookingHolding([$number], $status, $range['check_in'], $range['check_out'], 'triple');
        }

        $response = $this->postJson('/rooms/available', $range + ['room_type' => 'triple']);

        $response->assertOk();

        foreach ($response->json('rooms') as $room) {
            $this->assertSame(
                'booked',
                $room['status'],
                "Room {$room['room_number']} should be blocked but reported '{$room['status']}'.",
            );
        }
    }

    /**
     * The mirror of the above: a released booking must put the room back on
     * the market. A cancelled stay that keeps holding inventory is lost revenue.
     */
    public function test_non_blocking_statuses_release_the_room(): void
    {
        $range = $this->range();
        $released = array_diff(Booking::STATUSES, Booking::BLOCKING_STATUSES);

        foreach (array_values($released) as $i => $status) {
            $number = (string) (300 + $i);
            Make::room($number, 'quadruple');
            Make::bookingHolding([$number], $status, $range['check_in'], $range['check_out'], 'quadruple');
        }

        $response = $this->postJson('/rooms/available', $range + ['room_type' => 'quadruple']);

        $response->assertOk();

        foreach ($response->json('rooms') as $room) {
            $this->assertSame(
                'available',
                $room['status'],
                "Room {$room['room_number']} is held by a released booking but reported '{$room['status']}'.",
            );
        }
    }

    /**
     * Check-out day is a turnover day: the departing guest's check_out equals
     * the arriving guest's check_in, and both bookings are legal. The overlap
     * test is strict (`check_in < X.check_out AND check_out > X.check_in`), so
     * back-to-back stays must not collide.
     */
    public function test_a_stay_starting_on_another_stays_checkout_day_does_not_collide(): void
    {
        Make::room('101', 'double');

        $first  = now('Asia/Manila')->addDays(1)->toDateString();
        $middle = now('Asia/Manila')->addDays(3)->toDateString();
        $last   = now('Asia/Manila')->addDays(5)->toDateString();

        Make::bookingHolding(['101'], 'paid', $first, $middle);

        $response = $this->postJson('/rooms/available', [
            'room_type' => 'double',
            'check_in'  => $middle,
            'check_out' => $last,
        ]);

        $response->assertOk()->assertJsonPath('rooms.0.status', 'available');
    }

    public function test_a_stay_ending_on_another_stays_checkin_day_does_not_collide(): void
    {
        Make::room('101', 'double');

        $first  = now('Asia/Manila')->addDays(5)->toDateString();
        $last   = now('Asia/Manila')->addDays(7)->toDateString();

        Make::bookingHolding(['101'], 'paid', $first, $last);

        $response = $this->postJson('/rooms/available', [
            'room_type' => 'double',
            'check_in'  => now('Asia/Manila')->addDays(3)->toDateString(),
            'check_out' => $first,
        ]);

        $response->assertOk()->assertJsonPath('rooms.0.status', 'available');
    }

    /**
     * Housekeeping states are surfaced verbatim rather than collapsed into
     * "unavailable", because the staff-facing board reuses this endpoint.
     */
    public function test_housekeeping_status_is_reported_verbatim(): void
    {
        Make::room('101', 'double', 'maintenance');
        Make::room('102', 'double', 'cleaning');
        Make::room('103', 'double', 'occupied');

        $response = $this->postJson('/rooms/available', $this->range() + ['room_type' => 'double']);

        $response->assertOk()
            ->assertJsonPath('rooms.0.status', 'maintenance')
            ->assertJsonPath('rooms.1.status', 'cleaning')
            ->assertJsonPath('rooms.2.status', 'occupied');
    }

    public function test_availability_summary_counts_only_open_rooms(): void
    {
        Make::rooms(['101', '102', '103'], 'double');
        Make::room('104', 'double', 'maintenance');

        $range = $this->range();
        Make::bookingHolding(['101'], 'paid', $range['check_in'], $range['check_out']);

        $response = $this->postJson('/rooms/availability-summary', $range);

        $response->assertOk();

        $double = collect($response->json('summary'))->firstWhere('room_type', 'double');

        $this->assertSame(4, $double['total'], 'All four double rooms should be counted in the total.');
        $this->assertSame(2, $double['available'], 'One room is booked and one is in maintenance, leaving two.');
    }

    /**
     * RoomCatalog overlays room_types over config/room_types.php, and the DB
     * is meant to win — so an admin rate change reaches the public site
     * immediately and the frontend can never dictate a price.
     */
    public function test_summary_price_comes_from_the_database_not_the_config(): void
    {
        Make::room('101', 'double');

        $response = $this->postJson('/rooms/availability-summary', $this->range());

        $double = collect($response->json('summary'))->firstWhere('room_type', 'double');

        // config/room_types.php ships 1600 for 'double'; Make::catalog() seeds 1800.
        // Compared loosely because JSON renders 1800.0 as 1800.
        $this->assertEquals(1800, $double['price'], 'The room_types table must override the config price.');
        $this->assertNotEquals(1600, $double['price'], 'The config price must not leak through.');
    }

    public function test_summary_reports_the_correct_night_count(): void
    {
        Make::room('101', 'double');

        $response = $this->postJson('/rooms/availability-summary', [
            'check_in'  => now('Asia/Manila')->addDays(1)->toDateString(),
            'check_out' => now('Asia/Manila')->addDays(4)->toDateString(),
        ]);

        $response->assertOk()->assertJsonPath('nights', 3);
    }

    public function test_a_past_checkin_date_is_rejected(): void
    {
        $this->postJson('/rooms/available', [
            'room_type' => 'double',
            'check_in'  => now('Asia/Manila')->subDay()->toDateString(),
            'check_out' => now('Asia/Manila')->addDay()->toDateString(),
        ])->assertStatus(422);
    }

    public function test_a_checkout_on_or_before_checkin_is_rejected(): void
    {
        $date = now('Asia/Manila')->addDay()->toDateString();

        $this->postJson('/rooms/available', [
            'room_type' => 'double',
            'check_in'  => $date,
            'check_out' => $date,
        ])->assertStatus(422);
    }
}
