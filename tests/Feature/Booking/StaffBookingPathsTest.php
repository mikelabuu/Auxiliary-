<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Make;
use Tests\Support\StaffBookingPayload;
use Tests\TestCase;

/**
 * The two staff-side booking paths.
 *
 * The system can create an inventory-holding booking three different ways:
 *
 *   POST /booking                    — the public guest flow
 *   POST /front-desk/store           — front-desk walk-in
 *   POST /staff/manual-booking/store — admin manual booking
 *
 * Each re-implements its own occupancy check, price calculation and room
 * validation. The public path was audited and hardened; these two were not,
 * and a guard that exists on one path is worth nothing on the others.
 *
 * Every test here is written as an invariant that should hold no matter which
 * door a booking comes through.
 */
class StaffBookingPathsTest extends TestCase
{
    use RefreshDatabase;

    private const WALK_IN = '/front-desk/store';
    private const MANUAL  = '/staff/manual-booking/store';

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::rooms(['101', '102'], 'double');
        Make::rooms(['201'], 'triple');
    }

    private function frontdesk()
    {
        return $this->actingAs(Make::staff('frontdesk'), 'staff');
    }

    private function admin()
    {
        return $this->actingAs(Make::staff('admin'), 'staff');
    }

    private function range(int $from = 1, int $to = 3): array
    {
        return [
            now('Asia/Manila')->addDays($from)->toDateString(),
            now('Asia/Manila')->addDays($to)->toDateString(),
        ];
    }

    // ------------------------------------------------- occupancy invariants

    public function test_walk_in_refuses_a_room_already_held(): void
    {
        [$in, $out] = $this->range();
        Make::bookingHolding(['101'], 'paid', $in, $out);

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101')
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(1, Booking::count());
    }

    public function test_manual_booking_refuses_a_room_already_held(): void
    {
        [$in, $out] = $this->range();
        Make::bookingHolding(['101'], 'paid', $in, $out);

        $this->admin()->post(self::MANUAL, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101')
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(1, Booking::count());
    }

    public function test_walk_in_refuses_a_room_held_by_a_partially_overlapping_stay(): void
    {
        Make::bookingHolding(['101'], 'paid', ...$this->range(1, 5));
        [$in, $out] = $this->range(3, 7);

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101')
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    public function test_walk_in_allows_a_back_to_back_turnover(): void
    {
        [$in, $mid] = $this->range(1, 3);
        $out = now('Asia/Manila')->addDays(5)->toDateString();

        Make::bookingHolding(['101'], 'paid', $in, $mid);

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($mid, $out)
            ->room('101')
            ->toArray())
            ->assertSessionHasNoErrors();
    }

    /**
     * A walk-in booking must be visible to the public availability endpoint
     * immediately, or the website keeps selling a room the desk just gave away.
     */
    public function test_a_walk_in_booking_blocks_the_room_on_the_public_site(): void
    {
        [$in, $out] = $this->range();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101')
            ->toArray())
            ->assertSessionHasNoErrors();

        $this->postJson('/rooms/available', [
            'room_type' => 'double',
            'check_in'  => $in,
            'check_out' => $out,
        ])->assertJsonPath('rooms.0.status', 'booked');
    }

    /**
     * And the reverse: a booking made on the website must block the front desk.
     */
    public function test_a_public_booking_blocks_the_room_at_the_front_desk(): void
    {
        [$in, $out] = $this->range();
        Make::bookingHolding(['101'], 'pending_payment', $in, $out);

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101')
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    // ---------------------------------------------- housekeeping invariants

    /**
     * DEFECT PROBE — WalkInBookingController::store() has no room-status guard.
     *
     * Both the public flow and ManualBookingController explicitly refuse a room
     * that is in maintenance, cleaning or occupied. The walk-in path never
     * looks at `rooms.status`, so the desk can sell a room that is out of
     * service — and the guest is standing right there when it is discovered.
     */
    public function test_walk_in_refuses_a_room_under_maintenance(): void
    {
        Make::room('110', 'double', 'maintenance');
        [$in, $out] = $this->range();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('110')
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(0, Booking::count());
    }

    public function test_walk_in_refuses_a_room_being_cleaned(): void
    {
        Make::room('111', 'double', 'cleaning');
        [$in, $out] = $this->range();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('111')
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    /** The control: manual booking does have this guard and should pass. */
    public function test_manual_booking_refuses_a_room_under_maintenance(): void
    {
        Make::room('110', 'double', 'maintenance');
        [$in, $out] = $this->range();

        $this->admin()->post(self::MANUAL, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('110')
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    // ---------------------------------------------------- price invariants

    /**
     * DEFECT PROBE — WalkInBookingController trusts `price_per_night` from the
     * request and multiplies it out (`$totalPrice += $price * $nights`).
     *
     * ManualBookingController does not: it looks the rate up from the `rooms`
     * table and ignores what was posted. The public flow uses RoomCatalog.
     * The walk-in path is the only one where the client decides the price.
     *
     * Lower severity than the public equivalent because the caller is
     * authenticated staff — but it means a stale form, a JS bug or a modified
     * request writes a wrong total straight into the books, with no
     * server-side cross-check against the catalog.
     */
    public function test_walk_in_ignores_a_forged_nightly_rate(): void
    {
        [$in, $out] = $this->range();   // two nights

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101', 'double', pricePerNight: 1.00)
            ->toArray())
            ->assertSessionHasNoErrors();

        $this->assertEquals(
            3600.00,
            Booking::latest('id')->first()->total_price,
            'The walk-in path used the posted rate instead of the catalog rate.',
        );
    }

    /** The control: manual booking recomputes from the rooms table. */
    public function test_manual_booking_ignores_a_forged_nightly_rate(): void
    {
        [$in, $out] = $this->range();

        $this->admin()->post(self::MANUAL, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101', 'double', pricePerNight: 1.00)
            ->toArray())
            ->assertSessionHasNoErrors();

        $this->assertEquals(
            3600.00,
            Booking::latest('id')->first()->total_price,
            'The manual path used the posted rate instead of the rooms table.',
        );
    }

    // ------------------------------------------------ room validity

    /**
     * DEFECT PROBE — the walk-in path never checks that the room number
     * actually belongs to the claimed room type. Manual booking does
     * (`$room->room_type !== $roomType`), and so does the public flow.
     *
     * Combined with the client-supplied rate above, a triple can be sold and
     * recorded at the double rate.
     */
    public function test_walk_in_refuses_a_room_that_is_not_the_claimed_type(): void
    {
        [$in, $out] = $this->range();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('201', 'double')   // 201 is a triple
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(0, Booking::count());
    }

    public function test_manual_booking_refuses_a_room_that_is_not_the_claimed_type(): void
    {
        [$in, $out] = $this->range();

        $this->admin()->post(self::MANUAL, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('201', 'double')
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    // ------------------------------------------------- shared arithmetic

    public function test_walk_in_rejects_duplicate_room_numbers(): void
    {
        [$in, $out] = $this->range();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101', guests: 2)
            ->room('101', guests: 2)
            ->guests(4)
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    public function test_walk_in_rejects_more_guests_than_the_room_sleeps(): void
    {
        [$in, $out] = $this->range();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101', guests: 6)
            ->guests(6)
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    public function test_walk_in_requires_assigned_guests_to_match_the_expected_count(): void
    {
        [$in, $out] = $this->range();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101', guests: 2)
            ->guests(4)
            ->toArray())
            ->assertSessionHasErrors('expected_guests');
    }

    /**
     * A rejected staff booking must leave nothing behind, same as the public
     * path — no orphaned booking row holding a room nobody can see.
     */
    public function test_a_rejected_walk_in_writes_no_partial_rows(): void
    {
        [$in, $out] = $this->range();
        Make::bookingHolding(['101'], 'paid', $in, $out);

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101')
            ->toArray());

        $this->assertSame(1, Booking::count());
        $this->assertSame(1, Reservation::count());
    }

    // ------------------------------------------------------------- locking

    /**
     * Both staff paths must take a row lock on the rooms before checking
     * whether they are free. Without it the occupancy check is a read that
     * anyone can race: two desks book the same room in the same moment, both
     * see it free, both insert.
     *
     * Asserted structurally — a genuine two-connection race is not
     * reproducible in a single-threaded test — by watching for the `FOR UPDATE`
     * the lock emits.
     */
    public function test_the_walk_in_path_locks_the_rooms_before_checking_them(): void
    {
        $this->assertLocksRooms(self::WALK_IN, $this->frontdesk());
    }

    public function test_the_manual_path_locks_the_rooms_before_checking_them(): void
    {
        $this->assertLocksRooms(self::MANUAL, $this->admin());
    }

    private function assertLocksRooms(string $route, $actor): void
    {
        [$in, $out] = $this->range();

        $queries = [];
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        $actor->post($route, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101')
            ->toArray())
            ->assertSessionHasNoErrors();

        $lockingQuery = collect($queries)->first(
            fn ($sql) => str_contains($sql, 'from `rooms`') && str_contains($sql, 'for update'),
        );

        $this->assertNotNull(
            $lockingQuery,
            "{$route} never locked the room rows — its occupancy check can be raced.",
        );
    }

    /**
     * The guards now run inside the transaction, so each rejection path has to
     * roll back explicitly. A missed rollback would leave the booking row
     * committed, or the connection stuck mid-transaction.
     */
    public function test_a_walk_in_rejected_for_room_status_leaves_nothing_behind(): void
    {
        Make::room('110', 'double', 'maintenance');
        [$in, $out] = $this->range();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('110')
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, Reservation::count());
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_manual_booking_rejected_for_room_status_leaves_nothing_behind(): void
    {
        Make::room('110', 'double', 'maintenance');
        [$in, $out] = $this->range();

        $this->admin()->post(self::MANUAL, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('110')
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, Reservation::count());
    }

    /**
     * A rejection must also leave the connection usable — if the rollback were
     * missing, the next write in the same request would land inside an
     * abandoned transaction.
     */
    public function test_the_connection_is_usable_after_a_rejected_walk_in(): void
    {
        Make::room('110', 'double', 'maintenance');
        [$in, $out] = $this->range();

        // RefreshDatabase wraps each test in its own transaction, so the
        // baseline nesting level is 1 rather than 0 — compare against it.
        $before = \Illuminate\Support\Facades\DB::transactionLevel();

        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('110')
            ->toArray());

        $this->assertSame(
            $before,
            \Illuminate\Support\Facades\DB::transactionLevel(),
            'A rejected walk-in left an open transaction behind.',
        );

        // And a subsequent booking on a good room still succeeds.
        $this->frontdesk()->post(self::WALK_IN, StaffBookingPayload::make()
            ->dates($in, $out)
            ->room('101')
            ->toArray())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Booking::count());
    }

    // -------------------------------------------------------- authorization

    public function test_an_admin_cannot_post_a_walk_in(): void
    {
        $this->admin()->post(self::WALK_IN, StaffBookingPayload::make()->toArray())
            ->assertForbidden();
    }

    public function test_front_desk_staff_cannot_post_a_manual_booking(): void
    {
        $this->frontdesk()->post(self::MANUAL, StaffBookingPayload::make()->toArray())
            ->assertForbidden();
    }

    public function test_an_anonymous_visitor_cannot_post_a_walk_in(): void
    {
        $this->post(self::WALK_IN, StaffBookingPayload::make()->toArray())
            ->assertRedirect();

        $this->assertSame(0, Booking::count());
    }
}
