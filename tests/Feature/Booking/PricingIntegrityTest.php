<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BookingPayload;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * Price is computed server-side from RoomCatalog, never from the submitted
 * form. The checkout page posts `price_per_night` and `beds` for display
 * continuity, and store() is expected to discard both.
 *
 * These are the tests that prove a guest cannot pay ₱1 for a suite.
 */
class PricingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::rooms(['101', '102', '103'], 'double');   // 1800/night, sleeps 2
        Make::rooms(['201', '202'], 'triple');          // 2400/night, sleeps 3
    }

    /** Two nights: tomorrow → tomorrow + 2. */
    private function twoNights(): array
    {
        return [
            now('Asia/Manila')->addDay()->toDateString(),
            now('Asia/Manila')->addDays(3)->toDateString(),
        ];
    }

    public function test_the_total_is_rate_times_rooms_times_nights(): void
    {
        [$in, $out] = $this->twoNights();

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasNoErrors();

        // 1800 × 1 room × 2 nights
        $this->assertEquals(3600.00, Booking::latest('id')->first()->total_price);
    }

    public function test_the_total_scales_with_the_number_of_rooms(): void
    {
        [$in, $out] = $this->twoNights();

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101', '102'], guests: 4)
            ->guests(4)
            ->toArray())
            ->assertSessionHasNoErrors();

        // 1800 × 2 rooms × 2 nights
        $this->assertEquals(7200.00, Booking::latest('id')->first()->total_price);
    }

    public function test_the_total_sums_across_room_types(): void
    {
        [$in, $out] = $this->twoNights();

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->block('triple', ['201'], guests: 3)
            ->guests(5)
            ->toArray())
            ->assertSessionHasNoErrors();

        // (1800 × 2) + (2400 × 2)
        $this->assertEquals(8400.00, Booking::latest('id')->first()->total_price);
    }

    /**
     * The headline anti-tamper test: a forged nightly rate in the POST body
     * must have no effect on what the guest is charged.
     */
    public function test_a_forged_nightly_rate_in_the_payload_is_ignored(): void
    {
        [$in, $out] = $this->twoNights();

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2, pricePerNight: 1.00)
            ->guests(2)
            ->toArray())
            ->assertSessionHasNoErrors();

        $booking = Booking::latest('id')->first();

        $this->assertEquals(3600.00, $booking->total_price, 'The catalog rate must win over the posted rate.');
        $this->assertNotEquals(2.00, $booking->total_price);
    }

    public function test_a_forged_rate_does_not_reach_the_reservation_rows_either(): void
    {
        [$in, $out] = $this->twoNights();

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2, pricePerNight: 1.00)
            ->guests(2)
            ->toArray());

        $booking = Booking::latest('id')->first();

        $this->assertEquals(1800.00, $booking->reservations->first()->price);
    }

    /**
     * `beds` is posted alongside the rate. If the backend trusted it, a guest
     * could claim a double sleeps ten and overfill the room.
     */
    public function test_a_forged_bed_count_cannot_overfill_a_room(): void
    {
        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 6, beds: 10)
            ->guests(6)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_reservation_capacity_comes_from_the_catalog(): void
    {
        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2, beds: 10)
            ->guests(2)
            ->toArray());

        $booking = Booking::latest('id')->first();

        $this->assertSame(2, (int) $booking->reservations->first()->capacity);
    }

    /**
     * An admin rate change must reach the next booking immediately — the whole
     * reason RoomCatalog lets the DB override the config file.
     */
    public function test_an_admin_rate_change_applies_to_the_next_booking(): void
    {
        [$in, $out] = $this->twoNights();

        \App\Models\RoomType::where('slug', 'double')->update(['base_price' => 2500.00]);

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasNoErrors();

        $this->assertEquals(5000.00, Booking::latest('id')->first()->total_price);
    }

    /**
     * A single-night stay must be charged one night, not zero. The controller
     * clamps with max(1, ...) — this pins that behaviour.
     */
    public function test_a_one_night_stay_is_charged_a_full_night(): void
    {
        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates(
                now('Asia/Manila')->addDay()->toDateString(),
                now('Asia/Manila')->addDays(2)->toDateString(),
            )
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasNoErrors();

        $this->assertEquals(1800.00, Booking::latest('id')->first()->total_price);
    }

    /**
     * A fresh booking carries no discount until one is approved. If
     * payable_amount were pre-filled the guest could be charged the discounted
     * total before any proof was reviewed.
     */
    public function test_a_new_booking_carries_no_discount(): void
    {
        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray());

        $booking = Booking::latest('id')->first();

        $this->assertEquals(0.0, (float) $booking->discount);
        $this->assertNull($booking->payable_amount);
    }
}
