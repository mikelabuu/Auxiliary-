<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The same room cannot be sold twice for overlapping dates.
 *
 * BookingController::store() used to answer "is this room already held?" by
 * asking the booking_room pivot — but that pivot was attached *after* the
 * transaction committed. Every booking was therefore invisible to the guard
 * for the moment between COMMIT and attach(), and `lockForUpdate` releases the
 * room row to the next waiter at exactly that moment. Two guests racing for
 * room 112 both passed. A pivot attach that ever failed left the room
 * permanently rebookable.
 *
 * The guard now reads reservations — written inside the same transaction, and
 * already the authoritative per-room source for the room grid,
 * availabilitySummary, calendarAvailability, manual booking and walk-in.
 *
 * The first test is the regression: a booking with reservations but no pivot
 * row is the exact state the old guard could not see.
 */
class DoubleBookingTest extends TestCase
{
    use RefreshDatabase;

    private function guest(string $email): User
    {
        $user = User::create([
            'username' => str($email)->before('@')->toString(),
            'email' => $email,
            'phone' => '09171234567',
            'password' => Hash::make('password-12345'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function room(string $number = '112', string $type = 'deluxe'): Room
    {
        return Room::create([
            'room_number' => $number,
            'room_type' => $type,
            'wing' => 'Main',
            'status' => 'available',
            'price' => 3000,
        ]);
    }

    /**
     * A booking holding the room, written the way store() writes one.
     * `$withPivot` reproduces the window where the row does not exist yet.
     */
    private function existingHold(Room $room, string $in, string $out, bool $withPivot = true): Booking
    {
        $booking = Booking::create([
            'user_id' => $this->guest('holder@example.test')->id,
            'expected_guests' => 2,
            'guest_name' => 'Existing Holder',
            'guest_address' => 'Somewhere',
            'guest_phone' => '09000000000',
            'check_in' => $in,
            'check_out' => $out,
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 6000,
            'payable_amount' => 6000,
            'status' => 'pending_payment',
        ]);

        Reservation::create([
            'booking_id' => $booking->id,
            'room_number' => $room->room_number,
            'room_type' => $room->room_type,
            'capacity' => 2,
            'price' => 3000,
            'num_seniors' => 0,
            'num_guests' => 2,
        ]);

        if ($withPivot) {
            $booking->rooms()->attach($room->id);
        }

        return $booking;
    }

    /** The payload the public checkout posts for one room. */
    private function payload(Room $room, string $in, string $out): array
    {
        return [
            'first_name' => 'Ana',
            'middle_name' => 'Cruz',
            'last_name' => 'Reyes',
            'guest_phone' => '09171234567',
            'check_in' => $in,
            'check_out' => $out,
            'expected_guests' => 2,
            'accept_terms' => 1,
            'region_code' => 'R03|Central Luzon',
            'province_code' => 'P01|Nueva Ecija',
            'city_code' => 'C01|Science City of Munoz',
            'barangay_code' => 'B01|Bantug',
            'reservations' => [[
                'room_type' => $room->room_type,
                'room_number' => $room->room_number,
                'num_guests' => 2,
                'num_seniors' => 0,
            ]],
        ];
    }

    private function bookingsFor(Room $room): int
    {
        return Reservation::where('room_number', $room->room_number)->count();
    }

    // ---------------------------------------------------------------

    public function test_a_hold_with_no_pivot_row_still_blocks_the_room(): void
    {
        $room = $this->room();
        $in = now()->addDays(10)->toDateString();
        $out = now()->addDays(12)->toDateString();

        // No pivot: the state every booking passes through, and the one the
        // old guard was blind to.
        $this->existingHold($room, $in, $out, withPivot: false);

        $response = $this->actingAs($this->guest('second@example.test'))
            ->post('/booking', $this->payload($room, $in, $out));

        $response->assertSessionHasErrors('reservations');
        $this->assertSame(1, $this->bookingsFor($room), 'Room 112 was sold twice.');
    }

    public function test_an_overlapping_booking_is_rejected(): void
    {
        $room = $this->room();
        $this->existingHold($room, now()->addDays(10)->toDateString(), now()->addDays(14)->toDateString());

        // Starts inside the existing stay.
        $response = $this->actingAs($this->guest('second@example.test'))
            ->post('/booking', $this->payload(
                $room,
                now()->addDays(12)->toDateString(),
                now()->addDays(16)->toDateString(),
            ));

        $response->assertSessionHasErrors('reservations');
        $this->assertSame(1, $this->bookingsFor($room));
    }

    public function test_a_stay_starting_the_day_the_last_one_ends_is_allowed(): void
    {
        $room = $this->room();
        $checkout = now()->addDays(12)->toDateString();
        $this->existingHold($room, now()->addDays(10)->toDateString(), $checkout);

        // Back-to-back, not overlapping: a stay is [check_in, check_out), so
        // the departing guest's last night is the 11th and this one's first
        // night is the 12th. Rejecting this would cost a night's revenue on
        // every turnover.
        $response = $this->actingAs($this->guest('second@example.test'))
            ->post('/booking', $this->payload($room, $checkout, now()->addDays(14)->toDateString()));

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(2, $this->bookingsFor($room));
    }

    public function test_a_released_booking_frees_the_room_again(): void
    {
        $room = $this->room();
        $in = now()->addDays(10)->toDateString();
        $out = now()->addDays(12)->toDateString();

        $held = $this->existingHold($room, $in, $out);
        // Expired and cancelled are outside BLOCKING_STATUSES, so the room
        // must come back on the market rather than staying dead.
        $held->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->guest('second@example.test'))
            ->post('/booking', $this->payload($room, $in, $out));

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(2, $this->bookingsFor($room));
    }

    public function test_the_new_booking_records_its_pivot_row(): void
    {
        $room = $this->room();

        $this->actingAs($this->guest('first@example.test'))
            ->post('/booking', $this->payload(
                $room,
                now()->addDays(20)->toDateString(),
                now()->addDays(22)->toDateString(),
            ))->assertSessionDoesntHaveErrors();

        // The pivot moved inside the transaction; RoomBoard reads it, so a
        // booking that committed without one would hold a room the board
        // showed as free.
        $booking = Booking::latest('id')->first();
        $this->assertSame(1, $booking->rooms()->count());
    }
}
