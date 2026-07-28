<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * Suspension has to hold for the whole session, not just at the login form.
 *
 * Both login controllers reject a suspended account, but that says nothing
 * about a session that already exists — and suspension is how a compromised or
 * departed account gets cut off, which is exactly when it is likely to be
 * signed in already. With SESSION_LIFETIME at 120 minutes, a login-only check
 * leaves a two-hour window.
 */
class SuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
        Make::room('101', 'double');
    }

    // ----------------------------------------------------------- guest side

    public function test_a_suspended_guest_cannot_log_in(): void
    {
        $user = Make::user(['is_suspended' => true, 'email' => 'suspended@example.test']);

        $this->post('/login/user', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_guest_suspended_mid_session_loses_access(): void
    {
        $user = Make::user();

        // Session is live and working.
        $this->actingAs($user)->get('/settings')->assertOk();

        $user->update(['is_suspended' => true]);

        $this->actingAs($user)->get('/settings')->assertRedirect(route('login'));
    }

    public function test_a_suspended_guest_cannot_reach_the_checkout(): void
    {
        $user = Make::user(['is_suspended' => true]);

        $this->actingAs($user)->get('/checkout')->assertRedirect(route('login'));
    }

    public function test_a_suspended_guest_cannot_create_a_booking(): void
    {
        $user = Make::user(['is_suspended' => true]);

        $this->actingAs($user)->post('/booking', \Tests\Support\BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_suspended_guest_cannot_read_their_own_bookings(): void
    {
        $user    = Make::user();
        $booking = Make::bookingHolding(['101'], 'pending_payment', attributes: ['user_id' => $user->id]);

        $user->update(['is_suspended' => true]);

        $this->actingAs($user)->get("/booking/{$booking->id}")->assertRedirect(route('login'));
    }

    public function test_a_suspended_guest_is_told_why(): void
    {
        $user = Make::user(['is_suspended' => true]);

        $this->actingAs($user)->get('/settings')->assertSessionHasErrors('email');
    }

    /**
     * The bounce must land somewhere reachable — the login page is behind the
     * `guest` middleware, so a stale session would otherwise loop.
     */
    public function test_the_suspended_guest_can_reach_the_page_they_are_sent_to(): void
    {
        $user = Make::user(['is_suspended' => true]);

        $this->actingAs($user)->get('/settings');

        $this->assertGuest();
        $this->get('/login')->assertOk();
    }

    public function test_an_unsuspended_guest_regains_access(): void
    {
        $user = Make::user(['is_suspended' => true]);

        $this->actingAs($user)->get('/settings')->assertRedirect(route('login'));

        $user->update(['is_suspended' => false]);

        $this->actingAs($user)->get('/settings')->assertOk();
    }

    // ----------------------------------------------------------- staff side

    public function test_a_staff_member_suspended_mid_session_loses_the_console(): void
    {
        $staff = Make::staff('admin');

        $this->actingAs($staff, 'staff')->get('/staff/dashboard')->assertOk();

        $staff->update(['is_suspended' => true]);

        $this->actingAs($staff, 'staff')->get('/staff/dashboard')->assertRedirect(route('staff.login'));
    }

    public function test_a_suspended_front_desk_account_loses_the_front_desk(): void
    {
        $staff = Make::staff('frontdesk');

        $this->actingAs($staff, 'staff')->get('/front-desk/dashboard')->assertOk();

        $staff->update(['is_suspended' => true]);

        $this->actingAs($staff, 'staff')->get('/front-desk/dashboard')->assertRedirect(route('staff.login'));
    }

    /**
     * Routes guarded by `auth:staff` alone — without a role check — are covered
     * too. StaffRoleMiddleware handles the two consoles, but these sit outside
     * it, so the guard-level check is what protects them.
     */
    public function test_a_suspended_staff_account_cannot_use_auth_only_staff_routes(): void
    {
        $staff   = Make::staff('admin');
        $booking = Make::bookingHolding(['101'], 'paid');

        $this->actingAs($staff, 'staff')
            ->get("/staff/bookings/{$booking->id}/guest-history")
            ->assertOk();

        $staff->update(['is_suspended' => true]);

        $this->actingAs($staff, 'staff')
            ->get("/staff/bookings/{$booking->id}/guest-history")
            ->assertRedirect(route('staff.login'));
    }

    public function test_a_suspended_staff_member_is_sent_to_the_staff_login_not_the_guest_one(): void
    {
        $staff = Make::staff('admin', ['is_suspended' => true]);

        $response = $this->actingAs($staff, 'staff')->get('/staff/dashboard');

        $response->assertRedirect(route('staff.login'));
        $this->assertStringNotContainsString(
            route('login'),
            $response->headers->get('Location'),
            'A suspended staff member was sent to the customer login form.',
        );
    }
}
