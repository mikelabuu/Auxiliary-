<?php

namespace Tests\Feature;

use App\Mail\RescheduleDecidedMail;
use App\Models\Booking;
use App\Models\RescheduleRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReschedulePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config([
            'app.name' => 'Farmers Hostel',
            'app.url' => 'https://booking.example.test',
        ]);
    }

    private function guest(): User
    {
        $suffix = uniqid();

        return User::forceCreate([
            'username' => 'reschedule-' . $suffix,
            'email' => "reschedule-{$suffix}@example.test",
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);
    }

    private function frontDesk(): Staff
    {
        return Staff::create([
            'name' => 'Reschedule Reviewer',
            'email' => 'reschedule-reviewer-' . uniqid() . '@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'frontdesk',
            'is_suspended' => false,
        ]);
    }

    private function booking(User $guest, string $roomNumber): Booking
    {
        Room::create([
            'room_number' => $roomNumber,
            'room_type' => 'double',
            'wing' => 'rooster',
            'price' => 1600,
            'status' => 'available',
        ]);

        $booking = Booking::create([
            'user_id' => $guest->id,
            'expected_guests' => 2,
            'guest_name' => 'Reschedule Guest',
            'guest_address' => 'Science City of Muñoz',
            'guest_phone' => '09171234567',
            'check_in' => now()->addDays(14)->toDateString(),
            'check_out' => now()->addDays(16)->toDateString(),
            'discount' => 0,
            'num_seniors' => 0,
            'total_price' => 3200,
            'payable_amount' => 3200,
            'status' => Booking::STATUS_PAID,
        ]);

        Reservation::create([
            'booking_id' => $booking->id,
            'room_number' => $roomNumber,
            'room_type' => 'double',
            'capacity' => 2,
            'price' => 1600,
            'num_guests' => 2,
            'num_seniors' => 0,
        ]);

        return $booking->refresh();
    }

    private function reschedule(
        Booking $booking,
        string $status,
        int $moveByDays = 7,
        array $overrides = []
    ): RescheduleRequest {
        return RescheduleRequest::create(array_merge([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'status' => $status,
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'requested_check_in' => $booking->check_in->copy()->addDays($moveByDays),
            'requested_check_out' => $booking->check_out->copy()->addDays($moveByDays),
            'reason' => 'The event dates changed.',
            'submitted_at' => now(),
            'reviewed_at' => $status === RescheduleRequest::STATUS_PENDING ? null : now(),
        ], $overrides));
    }

    private function requestPayload(Booking $booking, int $moveByDays = 7): array
    {
        return [
            'requested_check_in' => $booking->check_in->copy()->addDays($moveByDays)->toDateString(),
            'requested_check_out' => $booking->check_out->copy()->addDays($moveByDays)->toDateString(),
            'reason' => 'The event dates changed again.',
        ];
    }

    public function test_an_approved_reschedule_permanently_blocks_another_guest_request(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'R-101');
        $this->reschedule($booking, RescheduleRequest::STATUS_APPROVED);

        $this->assertFalse(RescheduleRequest::isOpenFor($booking));

        $this->actingAs($guest)
            ->get(route('booking.reschedule.create', $booking))
            ->assertRedirect(route('booking.show', $booking))
            ->assertSessionHas('info', fn ($message) => str_contains($message, 'one allowed reschedule'));

        $this->actingAs($guest)
            ->post(route('booking.reschedule.store', $booking), $this->requestPayload($booking))
            ->assertRedirect(route('booking.show', $booking))
            ->assertSessionHas('info', fn ($message) => str_contains($message, 'one allowed reschedule'));

        $this->assertSame(1, RescheduleRequest::where('booking_id', $booking->id)->count());

        $this->actingAs($guest)
            ->get(route('booking.show', $booking))
            ->assertOk()
            ->assertSee('Reschedule used')
            ->assertDontSee('Request a reschedule');
    }

    public function test_declined_and_withdrawn_requests_do_not_consume_the_allowance(): void
    {
        foreach ([RescheduleRequest::STATUS_DECLINED, RescheduleRequest::STATUS_WITHDRAWN] as $index => $status) {
            $guest = $this->guest();
            $booking = $this->booking($guest, 'R-20' . $index);
            $this->reschedule($booking, $status);

            $this->assertTrue(RescheduleRequest::isOpenFor($booking));

            $this->actingAs($guest)
                ->post(route('booking.reschedule.store', $booking), $this->requestPayload($booking, 8))
                ->assertRedirect(route('booking.show', $booking))
                ->assertSessionHas('success');

            $this->assertSame(1, RescheduleRequest::where('booking_id', $booking->id)->pending()->count());
            $this->assertSame(2, RescheduleRequest::where('booking_id', $booking->id)->count());
        }
    }

    public function test_staff_cannot_approve_a_second_stale_request_for_the_same_booking(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'R-301');
        $first = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING, 7);
        $second = $this->reschedule($booking, RescheduleRequest::STATUS_PENDING, 12);
        $staff = $this->frontDesk();

        $this->actingAs($staff, 'staff')
            ->post(route('staff.reschedules.approve', $first))
            ->assertSessionHas('success');

        $approvedCheckIn = $first->requested_check_in->toDateString();
        $approvedCheckOut = $first->requested_check_out->toDateString();

        $this->actingAs($staff, 'staff')
            ->post(route('staff.reschedules.approve', $second))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'one allowed reschedule'));

        $this->assertSame(1, RescheduleRequest::where('booking_id', $booking->id)->approved()->count());
        $this->assertSame(RescheduleRequest::STATUS_PENDING, $second->fresh()->status);
        $this->assertSame($approvedCheckIn, $booking->fresh()->check_in->toDateString());
        $this->assertSame($approvedCheckOut, $booking->fresh()->check_out->toDateString());
    }

    public function test_approval_email_says_the_one_reschedule_has_been_used(): void
    {
        $guest = $this->guest();
        $booking = $this->booking($guest, 'R-401');
        $reschedule = $this->reschedule($booking, RescheduleRequest::STATUS_APPROVED);

        $text = html_entity_decode(strip_tags((new RescheduleDecidedMail($booking, $reschedule))->render()));

        $this->assertStringContainsStringIgnoringCase('one reschedule', $text);
        $this->assertStringContainsStringIgnoringCase('dates above are now final', $text);
        $this->assertStringNotContainsStringIgnoringCase('same rule applies to your new dates', $text);
    }
}
