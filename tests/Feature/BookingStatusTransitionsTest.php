<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\ArrivalsDepartures;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The desk's own status transitions: check-in, check-out, emergency check-out
 * and no-show.
 *
 * These live in the arrivals/departures panel, which is where a booking's
 * status is actually moved by a person — and it was the single least covered
 * file in the app despite holding more status logic than any other (31 of the
 * 86 literals replaced by Booking::STATUS_* constants were in here). Page
 * renders prove the *queries* read the right statuses; only these prove the
 * *writes* do.
 *
 * Each transition is asserted end to end: the booking's new status, the room's
 * new status, and the refusal path when the guard says no. A constant pointing
 * at the wrong string would pass a render test and fail every one of these.
 */
class BookingStatusTransitionsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): Staff
    {
        return Staff::create([
            'name'         => 'Desk Tester',
            'email'        => 'desk@example.test',
            'password'     => 'correct-horse-battery',
            'role'         => 'frontdesk',
            'is_suspended' => false,
        ]);
    }

    private function guest(): User
    {
        $u = User::forceCreate([
            'username'          => 'transitions',
            'email'             => 'transitions@example.test',
            'password'          => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        return $u;
    }

    private function room(string $number = '401', string $status = 'available'): Room
    {
        return Room::create([
            'room_number' => $number,
            'room_type'   => 'double',
            'wing'        => 'Main',
            'status'      => $status,
            'price'       => 1800,
        ]);
    }

    /**
     * A booking with a room attached, and optionally a verified payment —
     * check-in and no-show both refuse anything without one.
     */
    private function booking(string $status, array $dates = [], bool $withPayment = true, string $roomNumber = '401'): Booking
    {
        $room = $this->room($roomNumber, $status === Booking::STATUS_ACTIVE ? 'occupied' : 'available');

        $booking = Booking::create(array_merge([
            'user_id'         => $this->guest()->id,
            'expected_guests' => 2,
            'guest_name'      => 'Reyes, Ana',
            'guest_address'   => 'Bangkal, Abucay',
            'guest_phone'     => '09171234567',
            'check_in'        => now()->toDateString(),
            'check_out'       => now()->addDay()->toDateString(),
            'discount'        => 0,
            'num_seniors'     => 0,
            'total_price'     => 3600,
            'payable_amount'  => 3600,
            'status'          => $status,
        ], $dates));

        Reservation::create([
            'booking_id'  => $booking->id,
            'room_number' => $room->room_number,
            'room_type'   => $room->room_type,
            'capacity'    => 2,
            'price'       => 1800,
            'num_guests'  => 2,
            'num_seniors' => 0,
        ]);

        $booking->rooms()->attach($room->id);

        if ($withPayment) {
            Payment::create([
                'booking_id'   => $booking->id,
                'user_id'      => $booking->user_id,
                'amount'       => 3600,
                'payment_type' => 'manual',
                'status'       => 'success',
                'reference_no' => 'TESTREF001',
            ]);
        }

        return $booking->refresh();
    }

    private function panel()
    {
        return Livewire::actingAs($this->staff(), 'staff')->test(ArrivalsDepartures::class);
    }

    // ---------------------------------------------------------------

    /**
     * The constants must keep their exact wire values.
     *
     * Everything above seeds and asserts through Booking::STATUS_*, so a
     * constant silently repointed to 'activ' would still compare equal to
     * itself and every transition test would pass. These are the strings that
     * actually go in the column — and the Blade views were deliberately left
     * on literals, so `@if($booking->status === 'active')` in a template is
     * reading this table, not the constants. Break one and the console renders
     * a booking that is live everywhere except on screen.
     */
    public function test_the_status_constants_hold_their_literal_wire_values(): void
    {
        $this->assertSame('pending_discount', Booking::STATUS_PENDING_DISCOUNT);
        $this->assertSame('pending_payment', Booking::STATUS_PENDING_PAYMENT);
        $this->assertSame('paid', Booking::STATUS_PAID);
        $this->assertSame('active', Booking::STATUS_ACTIVE);
        $this->assertSame('completed', Booking::STATUS_COMPLETED);
        $this->assertSame('cancelled', Booking::STATUS_CANCELLED);
        $this->assertSame('expired', Booking::STATUS_EXPIRED);
        $this->assertSame('no_show', Booking::STATUS_NO_SHOW);

        // And the derived lists must still say what they said before.
        $this->assertSame(
            ['pending_payment', 'pending_discount', 'paid', 'active'],
            Booking::BLOCKING_STATUSES
        );
        $this->assertSame(
            ['pending_discount', 'paid', 'active'],
            Booking::SETTLED_BLOCKING_STATUSES
        );
        $this->assertSame(
            ['pending_discount', 'pending_payment', 'paid', 'active', 'completed', 'cancelled', 'expired', 'no_show'],
            Booking::STATUSES
        );
    }

    public function test_checking_in_moves_a_paid_booking_to_active_and_occupies_the_room(): void
    {
        $booking = $this->booking(Booking::STATUS_PAID);

        $this->panel()->call('confirmCheckIn', $booking->id);

        $this->assertSame('active', $booking->fresh()->status);
        $this->assertSame('occupied', Room::where('room_number', '401')->value('status'));
    }

    /** An unpaid hold must never be admitted, whatever the panel shows. */
    public function test_checking_in_an_unsettled_booking_is_refused(): void
    {
        $booking = $this->booking(Booking::STATUS_PENDING_PAYMENT, withPayment: false);

        $this->panel()->call('confirmCheckIn', $booking->id);

        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    /** Paid, but the money was never verified — the blocker the desk hit most. */
    public function test_checking_in_without_a_verified_payment_is_refused(): void
    {
        $booking = $this->booking(Booking::STATUS_PAID, withPayment: false);

        $this->panel()->call('confirmCheckIn', $booking->id);

        $this->assertSame('paid', $booking->fresh()->status);
    }

    public function test_checking_out_moves_an_active_stay_to_completed_and_frees_the_room(): void
    {
        $booking = $this->booking(Booking::STATUS_ACTIVE, [
            'check_in'  => now()->subDay()->toDateString(),
            'check_out' => now()->toDateString(),
        ]);

        $this->panel()->call('confirmCheckOut', $booking->id);

        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertNotSame('occupied', Room::where('room_number', '401')->value('status'));
    }

    /**
     * A stay with nights still to run is not the ordinary check-out's to end —
     * that is what emergency check-out is for.
     */
    public function test_checking_out_a_stay_with_nights_left_is_refused(): void
    {
        $booking = $this->booking(Booking::STATUS_ACTIVE, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]);

        $this->panel()->call('confirmCheckOut', $booking->id);

        $this->assertSame('active', $booking->fresh()->status);
    }

    public function test_emergency_check_out_ends_a_stay_that_still_has_nights_left(): void
    {
        $booking = $this->booking(Booking::STATUS_ACTIVE, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]);

        $this->panel()->call('confirmEmergencyCheckOut', $booking->id, 'Guest called away');

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_marking_a_no_show_moves_a_paid_booking_to_no_show(): void
    {
        $booking = $this->booking(Booking::STATUS_PAID);

        $this->panel()->call('confirmNoShow', $booking->id);

        $this->assertSame('no_show', $booking->fresh()->status);
    }

    /** Somebody standing in the room cannot also have failed to arrive. */
    public function test_marking_a_checked_in_guest_a_no_show_is_refused(): void
    {
        $booking = $this->booking(Booking::STATUS_ACTIVE);

        $this->panel()->call('confirmNoShow', $booking->id);

        $this->assertSame('active', $booking->fresh()->status);
    }
}
