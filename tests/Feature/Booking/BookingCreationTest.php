<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BookingPayload;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * POST /booking — BookingController::store().
 *
 * This is the single most consequential endpoint in the system: it is the only
 * public path that creates inventory-holding records, and it enforces roughly a
 * dozen business rules inline before opening a transaction. Each of those
 * guards gets a test here; pricing has its own file.
 */
class BookingCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::rooms(['101', '102', '103'], 'double');
        Make::rooms(['201', '202'], 'triple');
    }

    public function test_a_valid_submission_creates_the_booking_and_its_reservations(): void
    {
        $user = Make::user();

        $payload = BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray();

        $response = $this->actingAs($user)->post('/booking', $payload);

        $response->assertSessionHasNoErrors();

        $booking = Booking::latest('id')->first();
        $this->assertNotNull($booking, 'A booking row should have been created.');
        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame('pending_payment', $booking->status);
        $this->assertSame(2, $booking->expected_guests);

        $response->assertRedirect(route('booking.show', $booking->id));

        $this->assertDatabaseHas('reservations', [
            'booking_id'  => $booking->id,
            'room_number' => '101',
            'room_type'   => 'double',
        ]);
    }

    public function test_the_room_numbers_accessor_is_derived_from_reservations(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101', '102'], guests: 4)
            ->guests(4)
            ->toArray());

        $booking = Booking::latest('id')->first();

        $this->assertEqualsCanonicalizing(['101', '102'], $booking->room_numbers);
    }

    public function test_the_booking_room_pivot_is_populated(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray());

        $booking = Booking::latest('id')->first();

        $this->assertCount(1, $booking->rooms, 'The booking_room pivot backs the overlap guard and must be written.');
    }

    public function test_an_unknown_room_type_is_rejected(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('penthouse', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertDatabaseCount('bookings', 0);
    }

    /**
     * A room number that exists but belongs to a different (cheaper) type must
     * not be bookable under the claimed type — otherwise a guest could pay the
     * double rate for a dormitory.
     */
    public function test_a_room_belonging_to_another_type_is_rejected(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['201'], guests: 2)   // 201 is a triple
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_same_room_cannot_appear_in_two_blocks(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->block('double', ['101'], guests: 2)
            ->guests(4)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_guests_assigned_must_equal_expected_guests(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(4)              // claims four, assigns two
            ->toArray())
            ->assertSessionHasErrors('expected_guests');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_guests_cannot_exceed_the_capacity_of_the_booked_rooms(): void
    {
        $user = Make::user();

        // One double sleeps 2; cramming in 3 must fail.
        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 3)
            ->guests(3)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_meal_selections_must_match_the_guest_count(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2, meals: ['breakfast' => 1])
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    public function test_seniors_cannot_exceed_block_capacity(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2, seniors: 3)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    /**
     * A room the front desk just pulled for maintenance must not be bookable,
     * even if the guest's page still shows it as open (stale tab, no JS).
     */
    public function test_a_room_under_maintenance_cannot_be_booked(): void
    {
        $user = Make::user();
        Make::room('110', 'double', 'maintenance');

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['110'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_room_being_cleaned_cannot_be_booked(): void
    {
        $user = Make::user();
        Make::room('111', 'double', 'cleaning');

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['111'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('reservations');
    }

    public function test_requesting_a_senior_discount_parks_the_booking_for_review(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2, seniors: 1)
            ->guests(2)
            ->requestDiscount()
            ->toArray())
            ->assertSessionHasNoErrors();

        $booking = Booking::latest('id')->first();

        $this->assertSame('pending_discount', $booking->status);
        $this->assertTrue((bool) $booking->wants_discount);
    }

    /**
     * Ticking the discount box with zero seniors has nothing to verify, so the
     * booking should go straight to payment rather than stalling in review.
     */
    public function test_requesting_a_discount_with_no_seniors_goes_straight_to_payment(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2, seniors: 0)
            ->guests(2)
            ->requestDiscount()
            ->toArray())
            ->assertSessionHasNoErrors();

        $this->assertSame('pending_payment', Booking::latest('id')->first()->status);
    }

    /**
     * Booking::setStatusAttribute stamps this column so `bookings:expire` has
     * a clock to work from. Without it the sweep can never fire.
     */
    public function test_a_pending_payment_booking_is_stamped_for_the_expiry_sweep(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray());

        $this->assertNotNull(
            Booking::latest('id')->first()->pending_payment_since,
            'pending_payment_since drives the expiry command and must be set on creation.',
        );
    }

    public function test_a_script_tag_in_the_guest_name_is_rejected(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->name('<script>alert(1)</script>')
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('first_name');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_checkin_date_in_the_past_is_rejected(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->dates(
                now('Asia/Manila')->subDays(2)->toDateString(),
                now('Asia/Manila')->addDay()->toDateString(),
            )
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('check_in');
    }

    public function test_a_checkout_before_checkin_is_rejected(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->dates(
                now('Asia/Manila')->addDays(5)->toDateString(),
                now('Asia/Manila')->addDays(2)->toDateString(),
            )
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasErrors('check_out');
    }

    public function test_a_guest_who_is_not_logged_in_cannot_book(): void
    {
        $this->post('/booking', BookingPayload::make()->toArray())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_user_with_an_unverified_email_cannot_book(): void
    {
        $user = Make::unverifiedUser();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('bookings', 0);
    }

    /**
     * Any rejected submission must leave nothing behind — a half-written
     * booking would hold inventory nobody can see or release.
     */
    public function test_a_rejected_submission_writes_no_partial_rows(): void
    {
        $user = Make::user();

        $this->actingAs($user)->post('/booking', BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->block('double', ['101'], guests: 2)   // duplicate room
            ->guests(4)
            ->toArray());

        $this->assertSame(0, Booking::count(), 'No booking should survive a rejected submission.');
        $this->assertSame(0, Reservation::count(), 'No reservation should survive a rejected submission.');
    }
}
