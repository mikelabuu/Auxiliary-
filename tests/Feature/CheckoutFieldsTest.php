<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The checkout's own form contract.
 *
 * Several of these fields disagreed with themselves before: middle_name was
 * required by the server and optional in the markup, guest_phone was pattern-
 * checked in the browser and waved through by the server, and num_guests was
 * never validated at all — it was read as `?? 0`, so an omitted value made
 * every per-block capacity check compare against zero and pass.
 */
class CheckoutFieldsTest extends TestCase
{
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

    private function room(): Room
    {
        return Room::create([
            'room_number' => '112',
            'room_type'   => 'double',
            'wing'        => 'Main',
            'status'      => 'available',
            'price'       => 1800,
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'first_name'      => 'Ana',
            'middle_name'     => 'Cruz',
            'last_name'       => 'Reyes',
            'guest_phone'     => '09171234567',
            'check_in'        => now()->addDays(3)->toDateString(),
            'check_out'       => now()->addDays(5)->toDateString(),
            'expected_guests' => 2,
            'accept_terms'    => 1,
            'region_code'     => 'R03|Central Luzon',
            'province_code'   => 'P01|Nueva Ecija',
            'city_code'       => 'C01|Science City of Munoz',
            'barangay_code'   => 'B01|Bantug',
            'reservations'    => [[
                'room_type'   => 'double',
                'room_number' => '112',
                'num_guests'  => 2,
                'num_seniors' => 0,
            ]],
        ], $override);
    }

    private function book(array $override = [])
    {
        $this->room();

        return $this->actingAs($this->guest())->post('/booking', $this->payload($override));
    }

    // ---------------------------------------------------------------

    public function test_a_successful_booking_lands_on_its_own_summary_not_back_on_the_form(): void
    {
        $response = $this->book();

        $booking = Booking::sole();

        $response->assertRedirect(route('booking.show', $booking->id));
        $response->assertSessionHasNoErrors();
    }

    /** The page it redirects to has to actually render. */
    public function test_the_summary_it_redirects_to_renders(): void
    {
        $this->room();
        $guest = $this->guest();

        $this->actingAs($guest)->post('/booking', $this->payload());

        $this->actingAs($guest)
            ->get(route('booking.show', Booking::sole()->id))
            ->assertOk();
    }

    public function test_a_booking_cannot_be_made_without_agreeing_to_the_terms(): void
    {
        $this->book(['accept_terms' => null])->assertSessionHasErrors('accept_terms');

        $this->assertSame(0, Booking::count());
    }

    public function test_agreeing_is_stamped_with_when_it_happened(): void
    {
        $this->book();

        $booking = Booking::sole();

        $this->assertNotNull($booking->accepted_terms_at);
        $this->assertTrue($booking->accepted_terms_at->isToday());
    }

    public function test_arrival_time_and_special_requests_are_stored(): void
    {
        $this->book([
            'arrival_time'     => '22:00',
            'special_requests' => '  Ground floor if possible  ',
        ]);

        $booking = Booking::sole();

        $this->assertSame('22:00:00', (string) $booking->arrival_time);
        $this->assertSame('Ground floor if possible', $booking->special_requests);
    }

    public function test_not_sure_yet_stays_unknown_rather_than_becoming_midnight(): void
    {
        $this->book(['arrival_time' => '']);

        $this->assertNull(Booking::sole()->arrival_time);
        $this->assertNull(Booking::sole()->special_requests);
    }

    public function test_a_junk_arrival_time_is_rejected(): void
    {
        $this->book(['arrival_time' => 'whenever'])->assertSessionHasErrors('arrival_time');
    }

    public function test_special_requests_are_length_capped(): void
    {
        $this->book(['special_requests' => str_repeat('a', 501)])
            ->assertSessionHasErrors('special_requests');
    }

    public function test_the_phone_number_is_checked_server_side_too(): void
    {
        // The markup has always carried this pattern; the server took any 20
        // characters, so anything posted around the form got stored verbatim.
        $this->book(['guest_phone' => 'call me maybe'])->assertSessionHasErrors('guest_phone');

        $this->assertSame(0, Booking::count());
    }

    public function test_a_middle_name_longer_than_ten_characters_is_accepted(): void
    {
        // max:10 rejected ordinary names. "Bartholomew" is 11.
        $this->book(['middle_name' => 'Bartholomew'])->assertSessionHasNoErrors();

        $this->assertSame(1, Booking::count());
    }

    public function test_a_room_block_must_say_how_many_guests_are_in_it(): void
    {
        $this->book(['reservations' => [[
            'room_type'   => 'double',
            'room_number' => '112',
            'num_seniors' => 0,
        ]]])->assertSessionHasErrors('reservations.0.num_guests');

        $this->assertSame(0, Booking::count());
    }

    public function test_a_stay_cannot_run_past_the_maximum(): void
    {
        $this->book([
            'check_out' => now()->addDays(3 + 31)->toDateString(),
        ])->assertSessionHasErrors('check_out');
    }

    public function test_a_stay_cannot_start_beyond_the_booking_horizon(): void
    {
        $this->book([
            'check_in'  => now()->addDays(400)->toDateString(),
            'check_out' => now()->addDays(402)->toDateString(),
        ])->assertSessionHasErrors('check_in');
    }
}
