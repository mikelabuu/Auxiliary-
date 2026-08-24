<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingPayloads;
use Tests\TestCase;

/**
 * A `pending_payment` hold stops blocking its rooms once the payment window
 * has run out — whether or not `bookings:expire` has been anywhere near it.
 *
 * This is the scheduler-independence guarantee. The expiry command is
 * bookkeeping: it writes down that a hold died, sends the mail, frees the
 * payment row. Availability must not wait for it. When it did, a scheduler
 * that was not running (a deleted task, a moved folder, a wrong path in the
 * runbook) meant lapsed holds blocked their rooms forever, with nothing on
 * any screen to say why the room had stopped selling.
 *
 * Every test here leaves the booking's status untouched at `pending_payment`,
 * exactly as a dead scheduler would.
 */
class LapsedHoldTest extends TestCase
{
    use BuildsBookingPayloads;
    use RefreshDatabase;

    private function seedRoom(string $number = '101', string $type = 'double'): Room
    {
        RoomType::firstOrCreate(
            ['slug' => $type],
            ['name' => ucfirst($type), 'base_price' => 1600, 'capacity' => 2]
        );

        return Room::forceCreate([
            'room_number' => $number,
            'room_type'   => $type,
            'wing'        => 'Block A',
            'price'       => 1600,
            'status'      => 'available',
        ]);
    }

    /**
     * @param  int  $ageMinutes  How long ago the hold was placed.
     */
    private function holdRoom(string $number, string $checkIn, string $checkOut, int $ageMinutes): Booking
    {
        $guest = User::forceCreate([
            'username'          => 'holder' . $number . $ageMinutes,
            'email'             => "holder{$number}{$ageMinutes}@example.test",
            'password'          => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        $booking = Booking::create([
            'user_id'       => $guest->id,
            'guest_name'    => 'Holder, Test',
            'guest_address' => 'Bangkal, Abucay, Bataan',
            'guest_phone'   => '09171234567',
            'check_in'    => $checkIn,
            'check_out'   => $checkOut,
            'total_price' => 3200,
            'status'      => 'pending_payment',
        ]);

        // The status mutator stamps this to now(); age it by hand so the hold
        // looks as old as the test needs without touching the status.
        $booking->forceFill(['pending_payment_since' => now()->subMinutes($ageMinutes)])->save();

        Reservation::forceCreate([
            'booking_id'  => $booking->id,
            'room_number' => $number,
            'room_type'   => 'double',
            'num_guests'  => 2,
            'capacity'    => 2,
            'price'       => 1600,
        ]);

        return $booking;
    }

    private function window(): int
    {
        return (int) config('bookings.expiry_minutes');
    }

    /**
     * Inside the 365-day booking horizon: `/booking` rejects anything past it,
     * so a fixed far-future date would fail validation rather than the guard
     * these tests are actually about.
     */
    private function checkIn(): string
    {
        return now()->addDays(40)->toDateString();
    }

    private function checkOut(): string
    {
        return now()->addDays(42)->toDateString();
    }

    public function test_a_fresh_hold_still_blocks_its_room(): void
    {
        $this->seedRoom('101');
        $this->holdRoom('101', $this->checkIn(), $this->checkOut(), ageMinutes: 5);

        $response = $this->postJson('/rooms/available', [
            'check_in'  => $this->checkIn(),
            'check_out' => $this->checkOut(),
            'room_type' => 'double',
        ]);

        $response->assertOk();
        $this->assertSame(
            'reserved',
            collect($response->json('rooms'))->firstWhere('room_number', '101')['status'],
            'A hold inside its payment window must still hold the room.'
        );
    }

    public function test_a_lapsed_hold_releases_its_room_without_the_scheduler(): void
    {
        $this->seedRoom('101');
        $booking = $this->holdRoom('101', $this->checkIn(), $this->checkOut(), ageMinutes: $this->window() + 5);

        $response = $this->postJson('/rooms/available', [
            'check_in'  => $this->checkIn(),
            'check_out' => $this->checkOut(),
            'room_type' => 'double',
        ]);

        $response->assertOk();
        $this->assertSame(
            'available',
            collect($response->json('rooms'))->firstWhere('room_number', '101')['status'],
            'A hold past its window must free the room even though bookings:expire never ran.'
        );

        // The point of the guarantee: nothing rewrote the booking.
        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    public function test_a_lapsed_hold_does_not_block_a_new_booking(): void
    {
        $this->seedRoom('101');
        $this->holdRoom('101', $this->checkIn(), $this->checkOut(), ageMinutes: $this->window() + 5);

        $guest = User::forceCreate([
            'username'          => 'secondguest',
            'email'             => 'secondguest@example.test',
            'password'          => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($guest)->post('/booking', $this->bookingPayload([
            'first_name'   => 'Second',
            'middle_name'  => 'In',
            'last_name'    => 'Line',
            'check_in'     => $this->checkIn(),
            'check_out'    => $this->checkOut(),
            'reservations' => [$this->bookingReservation()],
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(
            2,
            Booking::count(),
            'The second guest must be able to take a room whose hold has lapsed.'
        );
    }

    public function test_a_fresh_hold_still_rejects_a_competing_booking(): void
    {
        $this->seedRoom('101');
        $this->holdRoom('101', $this->checkIn(), $this->checkOut(), ageMinutes: 5);

        $guest = User::forceCreate([
            'username'          => 'competing',
            'email'             => 'competing@example.test',
            'password'          => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($guest)->post('/booking', $this->bookingPayload([
            'first_name'   => 'Too',
            'middle_name'  => 'Late',
            'last_name'    => 'Guest',
            'check_in'     => $this->checkIn(),
            'check_out'    => $this->checkOut(),
            'reservations' => [$this->bookingReservation()],
        ]));

        $this->assertSame(
            1,
            Booking::count(),
            'A live hold must still win: the double-booking guard has not moved.'
        );
    }

    public function test_a_paid_booking_never_lapses(): void
    {
        $this->seedRoom('101');
        $booking = $this->holdRoom('101', $this->checkIn(), $this->checkOut(), ageMinutes: $this->window() * 10);

        // Paid clears pending_payment_since via the status mutator; the room
        // must stay held regardless of how long ago the hold began.
        $booking->update(['status' => 'paid']);

        $response = $this->postJson('/rooms/available', [
            'check_in'  => $this->checkIn(),
            'check_out' => $this->checkOut(),
            'room_type' => 'double',
        ]);

        $response->assertOk();
        $this->assertSame(
            'booked',
            collect($response->json('rooms'))->firstWhere('room_number', '101')['status'],
            'Settled statuses have no clock on them and must never be released by age.'
        );
    }
}
