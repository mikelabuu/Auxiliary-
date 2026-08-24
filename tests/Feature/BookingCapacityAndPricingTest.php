<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsBookingPayloads;
use Tests\TestCase;

/**
 * The per-block arithmetic in BookingController::store().
 *
 * These rules decide what a guest is charged and how many people the desk is
 * expecting, and every one of them was uncovered: the form-shape rules had
 * tests (CheckoutFieldsTest) and the concurrency guard had tests
 * (DoubleBookingTest), but the capacity, senior, breakfast and pricing checks
 * between them had none. They are also the rules most worth pinning before
 * store() is broken up, because they are the ones a refactor can silently
 * drop — a skipped `return back()` still returns a 302, so a lost check looks
 * exactly like a passing one from the outside.
 *
 * `double` is 2 beds at ₱1,600 in config/room_types.php, and nothing here
 * creates a room_types row, so those are the numbers the catalog serves.
 */
class BookingCapacityAndPricingTest extends TestCase
{
    use BuildsBookingPayloads;
    use RefreshDatabase;

    private function guest(): User
    {
        $user = User::create([
            'username' => 'ana',
            'email'    => 'ana@example.test',
            'phone'    => '09171234567',
            'password' => Hash::make('password-12345'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    /** @return Collection<int, Room> */
    private function rooms(int $count = 1, string $type = 'double')
    {
        return collect(range(1, $count))->map(fn ($n) => Room::create([
            'room_number' => (string) (300 + $n),
            'room_type'   => $type,
            'wing'        => 'Main',
            'status'      => 'available',
            'price'       => 1800,
        ]));
    }

    private function book(array $overrides = [])
    {
        return $this->actingAs($this->guest())->post('/booking', $this->bookingPayload($overrides));
    }

    // ---------------------------------------------------------------

    /**
     * The whole reason price_per_night is posted at all is display continuity;
     * the total is rebuilt from the catalog. Two nights at ₱1,600 is ₱3,200
     * however cheap the form claims the room was.
     */
    public function test_the_total_is_built_from_the_catalog_not_the_posted_price(): void
    {
        $this->rooms();

        $this->book([
            'reservations' => [$this->bookingReservation(['price_per_night' => 1])],
        ])->assertSessionHasNoErrors();

        $booking = Booking::sole();

        $this->assertEquals(3200, (float) $booking->total_price);
        // payable_amount is set equal to total at creation; a NULL here is what
        // used to leave the financial report and the bookings export blank.
        $this->assertEquals(3200, (float) $booking->payable_amount);
    }

    public function test_an_unknown_room_type_is_rejected(): void
    {
        $this->rooms();

        $this->book([
            'reservations' => [$this->bookingReservation(['room_type' => 'penthouse'])],
        ])->assertSessionHasErrors(['reservations' => 'Unknown room type: penthouse.']);

        $this->assertSame(0, Booking::count());
    }

    public function test_a_block_cannot_hold_more_guests_than_it_has_beds(): void
    {
        $this->rooms();

        $this->book([
            'expected_guests' => 3,
            'reservations'    => [$this->bookingReservation(['num_guests' => 3])],
        ])->assertSessionHasErrors(['reservations' => 'Guests assigned (3) exceed capacity (2) for double.']);

        $this->assertSame(0, Booking::count());
    }

    public function test_a_block_cannot_hold_more_seniors_than_it_has_beds(): void
    {
        $this->rooms();

        $this->book([
            'reservations' => [$this->bookingReservation(['num_seniors' => 3])],
        ])->assertSessionHasErrors(['reservations' => 'Senior count cannot exceed block capacity.']);

        $this->assertSame(0, Booking::count());
    }

    /**
     * Breakfast is complimentary, so only the upper bound is enforced: you
     * cannot claim more breakfasts than there are guests to eat them.
     */
    public function test_breakfasts_cannot_outnumber_the_guests_in_the_block(): void
    {
        $this->rooms();

        $this->book([
            'reservations' => [$this->bookingReservation(['meal' => [3]])],
        ])->assertSessionHasErrors(['reservations' => 'Breakfasts selected (3) cannot exceed the guests in the double room (2).']);

        $this->assertSame(0, Booking::count());
    }

    /** Fewer breakfasts than guests is a real answer, not an error. */
    public function test_taking_fewer_breakfasts_than_guests_is_allowed(): void
    {
        $this->rooms();

        $this->book([
            'reservations' => [$this->bookingReservation(['meal' => [1]])],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Booking::count());
    }

    public function test_guests_assigned_to_rooms_must_equal_the_expected_headcount(): void
    {
        $this->rooms();

        $this->book([
            'expected_guests' => 3,
            'reservations'    => [$this->bookingReservation(['num_guests' => 2])],
        ])->assertSessionHasErrors('expected_guests');

        $this->assertSame(0, Booking::count());
    }

    /**
     * Seniors are counted per block against beds, so a block can declare more
     * seniors than it has guests without tripping the per-block check. The
     * total is what catches it.
     */
    public function test_seniors_cannot_outnumber_the_expected_guests(): void
    {
        $this->rooms();

        $this->book([
            'expected_guests' => 1,
            'reservations'    => [$this->bookingReservation(['num_guests' => 1, 'num_seniors' => 2])],
        ])->assertSessionHasErrors('num_seniors');

        $this->assertSame(0, Booking::count());
    }

    /**
     * Two blocks means two rooms and twice the nightly rate — and the rooms
     * must be different ones, which is the assignment loop's per-type cursor
     * doing its job.
     */
    public function test_two_blocks_are_priced_and_assigned_as_two_rooms(): void
    {
        $this->rooms(2);

        $this->book([
            'expected_guests' => 4,
            'reservations'    => [$this->bookingReservation(), $this->bookingReservation()],
        ])->assertSessionHasNoErrors();

        $booking = Booking::sole();

        $this->assertEquals(6400, (float) $booking->total_price);
        $this->assertCount(2, $booking->reservations);
        $this->assertCount(
            2,
            $booking->reservations->pluck('room_number')->unique(),
            'Both blocks were handed the same room.'
        );
    }
}
