<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke cover for the two screens changed in the alerts + checkout pass.
 *
 * Both are heavy Blade/Alpine surfaces where a typo renders a broken page
 * rather than throwing, so these assert the specific hooks the JS depends on
 * are actually in the HTML — a missing #allocationMeter or a renamed x-ref
 * fails silently in the browser and would only be discovered on stage.
 */
class CheckoutAndTopbarRenderTest extends TestCase
{
    use RefreshDatabase;

    private function guest(): User
    {
        // UserFactory still writes the pre-2025_09_12 `name` column; build by hand.
        return User::forceCreate([
            'username' => 'checkout-tester',
            'email' => 'checkout@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);
    }

    private function admin(): Staff
    {
        return Staff::create([
            'name' => 'Console Tester',
            'email' => 'console@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'admin',
            'is_suspended' => false,
        ]);
    }

    public function test_checkout_renders_the_new_flow_controls(): void
    {
        $response = $this->actingAs($this->guest())->get('/checkout');

        $response->assertOk();

        // The allocation meter — booking.js writes into each of these by id.
        $response->assertSee('id="allocationMeter"', false);
        $response->assertSee('id="allocAssigned"', false);
        $response->assertSee('id="allocExpected"', false);
        $response->assertSee('id="allocMeterFill"', false);
        $response->assertSee('id="allocMeterHint"', false);

        // The blocker line above Confirm.
        $response->assertSee('id="bookingBlockerText"', false);

        // Date presets, and every key applyPreset() knows how to handle.
        $response->assertSee('id="datePresets"', false);
        foreach (['tonight', 'tomorrow', 'weekend', 'week'] as $preset) {
            $response->assertSee('data-preset="' . $preset . '"', false);
        }
    }

    public function test_checkout_renders_the_new_guest_fields(): void
    {
        $response = $this->actingAs($this->guest())->get('/checkout');

        $response->assertOk();

        // Arrival time and requests — the two things the front desk had no way
        // of knowing before a guest walked in.
        $response->assertSee('name="arrival_time"', false);
        $response->assertSee('name="special_requests"', false);
        // "Not sure yet" has to stay an option; a forced guess is worse than
        // no answer because the desk would plan around it.
        $response->assertSee('Not sure yet');

        // The terms gate, and the hold window it refers to.
        $response->assertSee('name="accept_terms"', false);
        $response->assertSee('id="accept_terms"', false);
        $response->assertSee('held for');
    }

    public function test_the_hold_window_shown_is_the_one_actually_enforced(): void
    {
        // The page used to promise an "Instant hold" without ever saying how
        // long. If this number drifts from the config, the page is lying about
        // a deadline ExpireBookingsCommand really does enforce.
        config(['bookings.expiry_minutes' => 45]);

        $this->actingAs($this->guest())->get('/checkout')->assertSee('45 minutes');
    }

    public function test_a_returning_guest_does_not_retype_their_details(): void
    {
        $guest = $this->guest();

        \App\Models\Booking::create([
            'user_id'         => $guest->id,
            'expected_guests' => 1,
            'guest_name'      => 'Reyes, Ana, Bartholomew Jr',
            'guest_address'   => 'Bantug, Munoz',
            'guest_phone'     => '09171234567',
            'check_in'        => now()->addDays(2),
            'check_out'       => now()->addDays(3),
            'total_price'     => 1800,
            'payable_amount'  => 1800,
            'status'          => 'completed',
        ]);

        $response = $this->actingAs($guest)->get('/checkout');

        // `users` stores no name at all, so the last booking is the only place
        // this person's name has ever been written.
        $response->assertSee('value="Ana"', false);
        $response->assertSee('value="Reyes"', false);
        // The suffix is glued onto the name on the way in; it has to come back
        // off without taking the middle name with it.
        $response->assertSee('value="Bartholomew"', false);
        $response->assertSee('value="Jr"', false);
    }

    /** The meter is a live region — it reports a number that changes silently. */
    public function test_allocation_meter_is_announced_to_screen_readers(): void
    {
        $this->actingAs($this->guest())
            ->get('/checkout')
            ->assertSee('aria-live="polite"', false);
    }

    public function test_admin_topbar_renders_the_live_bell(): void
    {
        $response = $this->actingAs($this->admin(), 'staff')->get('/staff/rooms');

        $response->assertOk();

        // Echo signs POST /broadcasting/auth with this; without it the private
        // subscription 419s and no alert ever arrives.
        $response->assertSee('name="csrf-token"', false);

        // The gate admin-notifications.js checks before subscribing.
        $response->assertSee('data-staff-alerts', false);

        // The bell's live wiring: the listener, and the refs ring() animates.
        $response->assertSee('staff-alert', false);
        $response->assertSee('x-ref="bell"', false);
        $response->assertSee('x-ref="dot"', false);

        // The list is data-driven now, not a Blade @forelse.
        $response->assertSee('x-for="n in items"', false);
    }

    /**
     * The dropdown and the live event have to agree on field names or rows
     * arriving over the wire render blank next to rows rendered by PHP.
     */
    public function test_topbar_seeds_items_in_the_broadcast_payload_shape(): void
    {
        // A room in maintenance is the cheapest entry the composer will emit —
        // no booking, payment or guest required.
        \App\Models\Room::forceCreate([
            'room_number' => '101',
            'room_type' => 'double',
            'wing' => 'A',
            'status' => 'maintenance',
        ]);

        // The composer is bound to the topbar *component*, not to any page, so
        // it is not reachable through a response's view data. Run it directly.
        $view = view('components.admin.layout.topbar');
        app('view')->callComposer($view);

        $items = $view->getData()['notifications'];

        $this->assertCount(1, $items, 'Expected the maintenance room to produce one alert.');

        $this->assertSame(
            ['id', 'type', 'title', 'text', 'url', 'level', 'at'],
            array_keys($items[0]),
            'Topbar feed item does not match StaffNotification::broadcastWith().'
        );

        // `at` is a unix timestamp, not a Carbon or a formatted string — the
        // dropdown does its own "2m ago" arithmetic against it.
        $this->assertIsInt($items[0]['at']);
    }
}
