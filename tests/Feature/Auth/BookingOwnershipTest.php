<?php

namespace Tests\Feature\Auth;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * Horizontal access control on guest-owned records.
 *
 * Booking ids are sequential integers exposed in the URL, so every route that
 * takes a {booking} must prove the caller owns it. These are classic IDOR
 * checks: guest B walks guest A's booking ids and reads their name, address,
 * phone and itinerary — or cancels their stay.
 */
class BookingOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::room('101', 'double');
    }

    private function bookingOf($owner, string $status = 'pending_payment'): Booking
    {
        return Make::bookingHolding(['101'], $status, attributes: ['user_id' => $owner->id]);
    }

    public function test_the_owner_can_view_their_booking(): void
    {
        $owner = Make::user();

        $this->actingAs($owner)->get("/booking/{$this->bookingOf($owner)->id}")->assertOk();
    }

    public function test_another_guest_cannot_view_a_booking(): void
    {
        $booking = $this->bookingOf(Make::user());

        $this->actingAs(Make::user())->get("/booking/{$booking->id}")->assertForbidden();
    }

    public function test_an_anonymous_visitor_cannot_view_a_booking(): void
    {
        $booking = $this->bookingOf(Make::user());

        $this->get("/booking/{$booking->id}")->assertRedirect(route('login'));
    }

    public function test_another_guest_cannot_cancel_a_booking(): void
    {
        $booking = $this->bookingOf(Make::user());

        $this->actingAs(Make::user())
            ->post("/booking/{$booking->id}/cancel", ['reason' => 'not mine'])
            ->assertForbidden();

        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    public function test_the_owner_can_cancel_a_pending_booking(): void
    {
        $owner   = Make::user();
        $booking = $this->bookingOf($owner);

        $this->actingAs($owner)->post("/booking/{$booking->id}/cancel", ['reason' => 'change of plans']);

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    /**
     * Once a stay is paid for, self-service cancellation must stop — money has
     * changed hands and the refund path is a staff decision.
     */
    public function test_a_paid_booking_cannot_be_cancelled_by_the_guest(): void
    {
        $owner   = Make::user();
        $booking = $this->bookingOf($owner, 'paid');

        $this->actingAs($owner)->post("/booking/{$booking->id}/cancel", ['reason' => 'refund please']);

        $this->assertSame('paid', $booking->fresh()->status);
    }

    public function test_a_cancellation_requires_a_reason(): void
    {
        $owner   = Make::user();
        $booking = $this->bookingOf($owner);

        $this->actingAs($owner)
            ->post("/booking/{$booking->id}/cancel", [])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending_payment', $booking->fresh()->status);
    }

    /**
     * The 30-minute cooldown stops a guest cycling bookings to hold inventory
     * without ever paying — book, cancel, rebook, repeat.
     */
    public function test_a_second_cancellation_within_the_cooldown_is_refused(): void
    {
        $owner = Make::user();
        $first  = $this->bookingOf($owner);
        Make::room('102', 'double');
        $second = Make::bookingHolding(['102'], 'pending_payment', attributes: ['user_id' => $owner->id]);

        $this->actingAs($owner)->post("/booking/{$first->id}/cancel", ['reason' => 'one']);
        $this->actingAs($owner)->post("/booking/{$second->id}/cancel", ['reason' => 'two']);

        $this->assertSame('cancelled', $first->fresh()->status);
        $this->assertSame(
            'pending_payment',
            $second->fresh()->status,
            'The cancellation cooldown did not hold.',
        );
    }

    public function test_another_guest_cannot_open_a_discount_request(): void
    {
        $booking = $this->bookingOf(Make::user());

        $this->actingAs(Make::user())->get("/discount/{$booking->id}/create")->assertForbidden();
    }

    /**
     * The bookings list must be scoped to the signed-in guest. A leak here
     * exposes every guest's stay history at once.
     */
    public function test_the_bookings_list_shows_only_the_signed_in_guests_bookings(): void
    {
        $owner   = Make::user(['username' => 'owner_acct']);
        $other   = Make::user(['username' => 'other_acct']);

        $this->bookingOf($owner);
        Make::room('102', 'double');
        Make::bookingHolding(['102'], 'pending_payment', attributes: [
            'user_id'    => $other->id,
            'guest_name' => 'Private, Stranger',
        ]);

        $this->actingAs($owner)->get('/my-bookings')
            ->assertOk()
            ->assertDontSee('Private, Stranger');
    }
}
