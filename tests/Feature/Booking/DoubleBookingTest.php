<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Reservation;
use Database\Factories\ReservationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BookingPayload;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * The double-booking guard in BookingController::store().
 *
 * Selling the same room twice is the worst failure this system can produce:
 * it is discovered at the front desk, in front of the guest, and cannot be
 * undone. The guard locks the room rows (`lockForUpdate`) and rejects any
 * overlapping stay in a blocking status.
 *
 * Note the asymmetry these tests probe: the guard reads occupancy from the
 * `booking_room` pivot, while both availability endpoints read it from
 * `reservations`. Two sources of truth for one fact.
 */
class DoubleBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::rooms(['101', '102'], 'double');
    }

    private function range(int $from = 1, int $to = 3): array
    {
        return [
            now('Asia/Manila')->addDays($from)->toDateString(),
            now('Asia/Manila')->addDays($to)->toDateString(),
        ];
    }

    public function test_a_room_already_held_for_the_same_dates_cannot_be_rebooked(): void
    {
        [$in, $out] = $this->range();

        Make::bookingHolding(['101'], 'paid', $in, $out);

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(1, Booking::count(), 'Only the original booking should exist.');
    }

    public function test_a_partially_overlapping_stay_is_rejected(): void
    {
        Make::bookingHolding(['101'], 'paid', ...$this->range(1, 5));

        [$in, $out] = $this->range(3, 7);   // starts mid-stay

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    public function test_a_stay_fully_enclosing_an_existing_one_is_rejected(): void
    {
        Make::bookingHolding(['101'], 'paid', ...$this->range(3, 5));

        [$in, $out] = $this->range(1, 8);   // swallows the existing stay

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    /**
     * The legitimate turnover case: one guest leaves on the day the next
     * arrives. Rejecting this would cost a night's revenue on every changeover.
     */
    public function test_a_back_to_back_stay_on_the_turnover_day_is_allowed(): void
    {
        [$in, $mid] = $this->range(1, 3);
        $out = now('Asia/Manila')->addDays(5)->toDateString();

        Make::bookingHolding(['101'], 'paid', $in, $mid);

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($mid, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::count());
    }

    public function test_a_cancelled_booking_releases_its_room(): void
    {
        [$in, $out] = $this->range();

        Make::bookingHolding(['101'], 'cancelled', $in, $out);

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasNoErrors();
    }

    public function test_an_expired_booking_releases_its_room(): void
    {
        [$in, $out] = $this->range();

        Make::bookingHolding(['101'], 'expired', $in, $out);

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasNoErrors();
    }

    /**
     * Only the conflicting room is refused — the rest of the request should
     * not be collateral damage, and nothing may be committed either way.
     */
    public function test_a_conflict_on_one_room_rejects_the_whole_submission(): void
    {
        [$in, $out] = $this->range();

        Make::bookingHolding(['101'], 'paid', $in, $out);

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101', '102'], guests: 4)
            ->guests(4)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(1, Booking::count());
        $this->assertSame(1, Reservation::count(), 'The free room must not be silently reserved.');
    }

    /**
     * DEFECT PROBE — expected to fail against the current implementation.
     *
     * store() attaches the booking_room pivot rows *after* its transaction
     * commits (BookingController.php:318), but the overlap guard inside the
     * transaction queries that same pivot. So a booking that already holds
     * rooms via `reservations` — which is exactly the state that exists in the
     * window between commit and attach, and the state the availability
     * endpoints already consider "booked" — is invisible to the guard.
     *
     * This test reproduces that state deterministically: reservation rows with
     * no pivot rows. A second guest booking the same room for the same nights
     * must still be refused. If this fails, the room has been sold twice.
     *
     * Fix direction: have the guard read `reservations` (the same source as the
     * availability endpoints), and move the pivot attach inside the transaction.
     */
    public function test_a_room_held_only_by_reservation_rows_is_still_protected(): void
    {
        [$in, $out] = $this->range();

        // A booking in the pre-attach state: reservations written, pivot not yet.
        $existing = Booking::factory()->create([
            'status'    => 'paid',
            'check_in'  => $in,
            'check_out' => $out,
        ]);
        ReservationFactory::new()->forBooking($existing)->room('101', 'double')->create();

        // Sanity check: the public availability endpoint already treats it as taken.
        $this->postJson('/rooms/available', [
            'room_type' => 'double',
            'check_in'  => $in,
            'check_out' => $out,
        ])->assertJsonPath('rooms.0.status', 'booked');

        $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(
            1,
            Reservation::where('room_number', '101')->count(),
            'Room 101 has been sold twice for overlapping dates.',
        );
    }

    /**
     * DEFECT PROBE — the same inconsistency stated as an invariant.
     *
     * Whatever the availability endpoint reports as unavailable must also be
     * refused by store(). Any divergence between the two is a room the guest
     * is told they cannot have but the system will nevertheless sell them
     * (or the reverse).
     */
    public function test_the_availability_endpoint_and_the_booking_guard_agree(): void
    {
        [$in, $out] = $this->range();

        $existing = Booking::factory()->create([
            'status'    => 'paid',
            'check_in'  => $in,
            'check_out' => $out,
        ]);
        ReservationFactory::new()->forBooking($existing)->room('101', 'double')->create();

        $reportedAvailable = collect($this->postJson('/rooms/available', [
            'room_type' => 'double',
            'check_in'  => $in,
            'check_out' => $out,
        ])->json('rooms'))->firstWhere('room_number', '101')['status'] === 'available';

        $response = $this->actingAs(Make::user())->post('/booking', BookingPayload::make()
            ->dates($in, $out)
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray());

        $bookingAccepted = ! $response->baseResponse->getSession()->has('errors');

        $this->assertSame(
            $reportedAvailable,
            $bookingAccepted,
            'The availability endpoint and the booking guard disagree about room 101.',
        );
    }
}
