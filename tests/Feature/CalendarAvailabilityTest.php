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
 * /rooms/calendar-availability, scoped to a room type.
 *
 * Property-wide counts are close to useless to a guest who has already chosen
 * a style. Room 112 is one of three doubles in a 22-room house: a hold on it
 * left 21 rooms free, the night scored "not full", and the date picker showed
 * it wide open — while the type actually being shopped for was down to its
 * last one. The guest only found out at the room grid, two steps later.
 *
 * Scoped, the same night reports what it really is.
 */
class CalendarAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private string $in;
    private string $out;
    private User $holder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->in  = now()->addDays(4)->toDateString();
        $this->out = now()->addDays(5)->toDateString();

        $this->holder = User::create([
            'username' => 'holder',
            'email'    => 'holder@example.test',
            'phone'    => '09171234567',
            'password' => Hash::make('password-12345'),
        ]);
    }

    private function room(string $number, string $type, string $status = 'available'): Room
    {
        return Room::create([
            'room_number' => $number,
            'room_type'   => $type,
            'wing'        => 'Main',
            'status'      => $status,
            'price'       => 1800,
        ]);
    }

    /** A blocking hold on one room, written the way store() writes one. */
    private function hold(Room $room, ?string $in = null, ?string $out = null): Booking
    {
        $booking = Booking::create([
            'user_id'         => $this->holder->id,
            'expected_guests' => 2,
            'guest_name'      => 'Holder',
            'guest_address'   => 'Somewhere',
            'guest_phone'     => '09000000000',
            'check_in'        => $in ?? $this->in,
            'check_out'       => $out ?? $this->out,
            'discount'        => 0,
            'num_seniors'     => 0,
            'total_price'     => 1800,
            'payable_amount'  => 1800,
            'status'          => 'paid',
        ]);

        Reservation::create([
            'booking_id'  => $booking->id,
            'room_number' => $room->room_number,
            'room_type'   => $room->room_type,
            'capacity'    => 2,
            'price'       => 1800,
            'num_seniors' => 0,
            'num_guests'  => 2,
        ]);

        return $booking;
    }

    private function calendar(?string $roomType = null): array
    {
        return $this->getJson('/rooms/calendar-availability' . ($roomType ? '?room_type=' . $roomType : ''))
            ->assertOk()
            ->json();
    }

    // ---------------------------------------------------------------

    public function test_a_single_held_room_does_not_move_the_property_wide_count(): void
    {
        $this->room('110', 'double');
        $this->room('112', 'double');
        foreach (['101', '102', '103'] as $n) {
            $this->room($n, 'deluxe');
        }

        $this->hold(Room::where('room_number', '112')->first());

        $data = $this->calendar();

        // 5 sellable, 1 held — the night is nowhere near full, which is the
        // whole reason the unscoped view could not answer the guest's question.
        $this->assertSame(5, $data['sellable']);
        $this->assertSame(4, $data['remaining'][$this->in]);
        $this->assertNotContains($this->in, $data['full']);
    }

    public function test_scoping_to_the_type_reports_what_is_actually_left(): void
    {
        $this->room('110', 'double');
        $this->room('112', 'double');
        $this->room('101', 'deluxe');

        $this->hold(Room::where('room_number', '112')->first());

        $data = $this->calendar('double');

        $this->assertSame('double', $data['room_type']);
        $this->assertSame(2, $data['sellable']);
        $this->assertSame(1, $data['remaining'][$this->in], 'room 110 is the one left');
        $this->assertNotContains($this->in, $data['full'], 'still bookable — do not strike it out');
    }

    public function test_the_night_goes_full_only_when_the_last_room_of_the_type_goes(): void
    {
        $this->room('110', 'double');
        $this->room('112', 'double');
        $this->room('101', 'deluxe');

        $this->hold(Room::where('room_number', '112')->first());
        $this->hold(Room::where('room_number', '110')->first());

        $data = $this->calendar('double');

        $this->assertSame(0, $data['remaining'][$this->in]);
        $this->assertContains($this->in, $data['full']);

        // The deluxe is untouched by any of it.
        $this->assertNotContains($this->in, $this->calendar('deluxe')['full']);
    }

    public function test_check_out_day_is_not_a_held_night(): void
    {
        $this->room('112', 'double');
        $this->hold(Room::where('room_number', '112')->first());

        $data = $this->calendar('double');

        // A stay covers [check_in, check_out): only the check-in night is held,
        // so the departure date stays free for the next guest to arrive on.
        $this->assertContains($this->in, $data['full']);
        $this->assertArrayNotHasKey($this->out, $data['remaining']);
    }

    public function test_a_room_in_maintenance_is_not_inventory(): void
    {
        $this->room('110', 'double');
        $this->room('217', 'double', 'maintenance');

        $data = $this->calendar('double');

        $this->assertSame(1, $data['sellable'], 'only 110 is sellable');
    }

    public function test_a_hold_on_a_non_sellable_room_is_not_double_counted(): void
    {
        $this->room('110', 'double');
        // Booked first, pulled into maintenance afterwards — a real sequence.
        $maintained = $this->room('217', 'double', 'maintenance');
        $this->hold($maintained);

        $data = $this->calendar('double');

        // 217 was never in the sellable total, so subtracting it would report
        // zero rooms left on a night when 110 is sitting empty.
        $this->assertSame(1, $data['sellable']);
        $this->assertNotContains($this->in, $data['full']);
    }

    public function test_a_type_with_no_sellable_rooms_is_off_entirely(): void
    {
        $this->room('217', 'double', 'maintenance');

        $data = $this->calendar('double');

        $this->assertSame(0, $data['sellable']);
        // No booking is needed to make these nights unavailable.
        $this->assertContains($this->in, $data['full']);
        $this->assertContains($this->out, $data['full']);
    }

    public function test_an_unknown_room_type_is_rejected(): void
    {
        $this->room('110', 'double');

        $this->getJson('/rooms/calendar-availability?room_type=penthouse')
            ->assertStatus(422);
    }

    public function test_a_cancelled_booking_frees_the_night(): void
    {
        $this->room('112', 'double');
        $this->hold(Room::where('room_number', '112')->first())
            ->forceFill(['status' => 'cancelled'])->save();

        $data = $this->calendar('double');

        $this->assertNotContains($this->in, $data['full']);
        $this->assertArrayNotHasKey($this->in, $data['remaining']);
    }
}
