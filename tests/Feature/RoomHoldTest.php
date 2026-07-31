<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Support\RoomHold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A room held by a booking that has not been paid for is still off the market.
 *
 * The rule already lived in Booking::BLOCKING_STATUSES, but nothing pinned it
 * down — and it is exactly the kind of rule that gets quietly loosened while
 * someone is "tidying up" a status list. Two guests handed the same room
 * because one of them had not paid yet is the worst bug this system could
 * have, so it gets explicit cover.
 *
 * The second half asserts the *wording*: an unpaid hold reads as "reserved",
 * not "booked", so staff can tell a claim from a sale.
 */
class RoomHoldTest extends TestCase
{
    use RefreshDatabase;

    /** Reused rather than recreated — several helpers ask for the same guest. */
    private function user(string $email = 'guest@example.test'): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'username' => str($email)->before('@')->toString(),
                'password' => Hash::make('password-12345'),
            ]
        );

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }

    private function room(string $number = '101', string $type = 'deluxe'): Room
    {
        return Room::create([
            'room_number' => $number,
            'room_type' => $type,
            'wing' => 'Main',
            'status' => 'available',
            'price' => 3000,
        ]);
    }

    /** A booking holding $room for the given window. */
    private function holdRoom(Room $room, string $status, string $in = '+3 days', string $out = '+5 days'): Booking
    {
        $booking = Booking::create([
            'user_id' => $this->user('holder@example.test')->id,
            'guest_name' => 'Holder',
            'guest_address' => 'Somewhere',
            'guest_phone' => '09000000000',
            'check_in' => now()->parse($in)->toDateString(),
            'check_out' => now()->parse($out)->toDateString(),
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 6000,
            'payable_amount' => 6000,
            'status' => $status,
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

        $booking->rooms()->attach($room->id);

        return $booking;
    }

    private function availability(User $asUser, Room $room, string $in = '+3 days', string $out = '+5 days'): array
    {
        $response = $this->actingAs($asUser)->postJson('/rooms/available', [
            'room_type' => $room->room_type,
            'check_in' => now()->parse($in)->toDateString(),
            'check_out' => now()->parse($out)->toDateString(),
        ])->assertOk();

        return collect($response->json('rooms'))
            ->firstWhere('room_number', $room->room_number);
    }

    // ---------------------------------------------------------------
    // The rule: unpaid still means unavailable
    // ---------------------------------------------------------------

    public function test_every_pending_status_still_blocks_the_room(): void
    {
        foreach (RoomHold::PENDING_STATUSES as $status) {
            $room = $this->room('20' . array_search($status, RoomHold::PENDING_STATUSES, true));
            $this->holdRoom($room, $status);

            $tile = $this->availability($this->user('shopper' . $status . '@example.test'), $room);

            $this->assertNotSame(
                'available',
                $tile['status'],
                "A booking in '{$status}' must keep room {$room->room_number} off the market."
            );
        }
    }

    public function test_a_pending_payment_hold_reads_as_reserved_not_booked(): void
    {
        $room = $this->room('301');
        $this->holdRoom($room, 'pending_payment');

        $tile = $this->availability($this->user('shopper@example.test'), $room);

        // "Reserved" is the honest word: someone claimed it, nobody paid yet.
        $this->assertSame('reserved', $tile['status']);
    }

    public function test_a_paid_hold_still_reads_as_booked(): void
    {
        $room = $this->room('302');
        $this->holdRoom($room, 'paid');

        $tile = $this->availability($this->user('shopper@example.test'), $room);

        $this->assertSame('booked', $tile['status']);
    }

    public function test_a_free_room_is_available(): void
    {
        $room = $this->room('303');

        $tile = $this->availability($this->user('shopper@example.test'), $room);

        $this->assertSame('available', $tile['status']);
    }

    public function test_a_settled_hold_wins_over_an_overlapping_pending_one(): void
    {
        // Two bookings can overlap the same room in the data (one of them will
        // never be honoured) — the tile must not soften to "reserved" just
        // because an unpaid claim also exists.
        $room = $this->room('304');
        $this->holdRoom($room, 'pending_payment');
        $this->holdRoom($room, 'paid');

        $tile = $this->availability($this->user('shopper@example.test'), $room);

        $this->assertSame('booked', $tile['status']);
    }

    public function test_a_hold_outside_the_requested_window_does_not_block(): void
    {
        $room = $this->room('305');
        $this->holdRoom($room, 'pending_payment', '+20 days', '+22 days');

        $tile = $this->availability($this->user('shopper@example.test'), $room);

        $this->assertSame('available', $tile['status']);
    }

    // ---------------------------------------------------------------
    // The double-booking guard in store()
    // ---------------------------------------------------------------

    public function test_booking_a_room_held_by_an_unpaid_booking_is_refused(): void
    {
        $room = $this->room('306');
        $this->holdRoom($room, 'pending_payment');

        $shopper = $this->user('shopper@example.test');

        $this->actingAs($shopper)->post('/booking', [
            'first_name' => 'Ana',
            'middle_name' => 'B',
            'last_name' => 'Cruz',
            'guest_phone' => '09171234567',
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'expected_guests' => 2,
            'accept_terms' => 1,
            'region_code' => 'R03',
            'city_code' => 'C1|Munoz',
            'barangay_code' => 'B1|Bantug',
            'reservations' => [[
                'room_type' => $room->room_type,
                'room_number' => $room->room_number,
                'num_guests' => 2,
                'num_seniors' => 0,
                'meal' => [2],
            ]],
        ])->assertSessionHasErrors('reservations');

        // Only the original hold exists — no second booking got through.
        $this->assertSame(1, Booking::whereHas('reservations', fn ($q) =>
            $q->where('room_number', $room->room_number))->count());
    }

    // ---------------------------------------------------------------
    // The helper the console and the picker both read from
    // ---------------------------------------------------------------

    public function test_hold_helper_separates_claims_from_sales(): void
    {
        $this->assertTrue(RoomHold::isPending('pending_payment'));
        $this->assertTrue(RoomHold::isPending('pending_discount'));
        $this->assertFalse(RoomHold::isPending('paid'));
        $this->assertFalse(RoomHold::isPending('active'));
        $this->assertFalse(RoomHold::isPending(null));

        // Every pending status must also be a blocking one, or a room could
        // read as "reserved" while still being sold to someone else.
        foreach (RoomHold::PENDING_STATUSES as $status) {
            $this->assertContains($status, Booking::BLOCKING_STATUSES);
        }
    }

    public function test_stay_line_words_an_unpaid_hold_differently(): void
    {
        $unpaid = RoomHold::stayLine(['guest' => 'Ana', 'until' => 'Aug 03', 'status' => 'pending_payment'], null);
        $paid   = RoomHold::stayLine(['guest' => 'Ana', 'until' => 'Aug 03', 'status' => 'active'], null);

        $this->assertSame('current-pending', $unpaid['kind']);
        // Wording changed from "Reserved · awaiting payment": the label now
        // also carries the date, matching the next-pending variant's
        // "Reserved · Aug 08 · unpaid" and fitting a narrow card.
        $this->assertStringContainsString('unpaid', $unpaid['label']);
        $this->assertStringContainsString('Aug 03', $unpaid['label']);

        $this->assertSame('current', $paid['kind']);
        $this->assertStringContainsString('In use', $paid['label']);
        $this->assertStringNotContainsString('unpaid', $paid['label']);

        // Who holds the room is its own field now — it used to exist only
        // inside `title`, which a touch screen cannot hover.
        $this->assertSame('Ana', $unpaid['guest']);
        $this->assertSame('Ana', $paid['guest']);

        $none = RoomHold::stayLine(null, null);
        $this->assertSame('none', $none['kind']);
        $this->assertNull($none['guest']);
    }

    public function test_stay_line_flags_an_unpaid_future_arrival(): void
    {
        $line = RoomHold::stayLine(null, ['guest' => 'Ben', 'from' => 'Aug 08', 'status' => 'pending_payment']);

        $this->assertSame('next-pending', $line['kind']);
        $this->assertStringContainsString('unpaid', $line['label']);
    }

    // ---------------------------------------------------------------
    // A verified payment releases the "unpaid" wording
    // ---------------------------------------------------------------

    public function test_the_tile_hardens_to_booked_once_the_payment_is_verified(): void
    {
        $room = $this->room('307');
        $booking = $this->holdRoom($room, 'pending_payment');

        $shopper = $this->user('shopper@example.test');
        $this->assertSame('reserved', $this->availability($shopper, $room)['status']);

        Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $booking->payable_amount,
            'status' => 'success',
            'payment_type' => 'manual',
            'reference_no' => 'REFHARDEN01',
            'gateway' => 'gcash',
        ]);
        $booking->update(['status' => 'paid']);

        $this->assertSame('booked', $this->availability($shopper, $room)['status']);
    }
}
