<?php

namespace Tests\Feature;

use App\Events\StaffNotification;
use App\Models\Staff;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

/**
 * Cover for the live staff alert feed.
 *
 * The property under test is not "does a popup appear" — it is that the one
 * channel in this app carrying a guest's name is the one channel nobody can
 * subscribe to anonymously. Every other broadcast here rides a public channel
 * and is deliberately payload-free; if StaffNotification ever slipped onto a
 * public channel, or the `staff` guard were dropped from its authorisation
 * callback, guest names would go out to anyone holding the Reverb app key —
 * which is shipped in the JS bundle, i.e. everyone.
 */
class StaffAlertBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'name' => 'Desk Tester',
            'email' => 'desk@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'admin',
            'is_suspended' => false,
        ], $overrides));
    }

    private function alert(): StaffNotification
    {
        return new StaffNotification(
            id: 'booking:1:1000',
            type: 'booking',
            title: 'New booking',
            text: '#1 · Maria Santos',
            url: '/staff/bookings',
            level: 'success',
            at: 1000,
        );
    }

    /** The whole point: a guest's name must not ride a public channel. */
    public function test_alert_broadcasts_on_a_private_channel(): void
    {
        $channel = $this->alert()->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-staff.alerts', (string) $channel);
    }

    public function test_payload_carries_only_the_fields_the_console_renders(): void
    {
        $this->assertSame([
            'id' => 'booking:1:1000',
            'type' => 'booking',
            'title' => 'New booking',
            'text' => '#1 · Maria Santos',
            'url' => '/staff/bookings',
            'level' => 'success',
            'at' => 1000,
        ], $this->alert()->broadcastWith());
    }

    /**
     * The ids the live event mints have to collide with the ids the topbar's
     * view composer derives, or an alert seen live would reappear as unread on
     * the next page load — and "mark all read" would never stick.
     */
    public function test_alert_id_matches_the_view_composer_format(): void
    {
        $this->assertMatchesRegularExpression(
            '/^(booking|payment|discount|maintenance):[^:]+:\d+$/',
            $this->alert()->id
        );
    }

    /**
     * Alert links must be paths, never absolute URLs.
     *
     * Emitters frequently have no request to infer a host from — the artisan
     * demo command, the scheduler, a queue worker — so route() falls back to
     * APP_URL. When APP_URL does not match where the app is actually served
     * (an `artisan serve` high port, or an Apache whose default vhost belongs
     * to another project) the link resolves to a different application and
     * 404s. The console clicking these is already on the right origin, so a
     * path is both correct and immune to the whole class of problem.
     */
    public function test_alert_urls_are_relative_paths(): void
    {
        // No request context — exactly the condition that produced the bug.
        config(['app.url' => 'http://wrong-host.example']);

        $room = \App\Models\Room::forceCreate([
            'room_number' => '101',
            'room_type' => 'double',
            'wing' => 'A',
            'status' => 'maintenance',
        ]);

        $url = StaffNotification::roomMaintenance($room)->url;

        $this->assertStringStartsWith('/', $url, "Alert url must be a path, got: {$url}");
        $this->assertStringNotContainsString('wrong-host.example', $url);
        $this->assertStringNotContainsString('http', $url);
    }

    // ── Channel authorisation ────────────────────────────────────────────────

    /** @return callable */
    private function authCallback()
    {
        $channels = Broadcast::getChannels();

        $this->assertArrayHasKey('staff.alerts', $channels, 'staff.alerts channel is not registered.');

        return $channels['staff.alerts'];
    }

    public function test_active_console_roles_are_authorised(): void
    {
        $callback = $this->authCallback();

        foreach (['master_admin', 'admin', 'frontdesk'] as $role) {
            $staff = $this->staff(['role' => $role, 'email' => $role . '@example.test']);

            $this->assertTrue($callback($staff), "{$role} should be able to subscribe.");
        }
    }

    /** Housekeeping has no console to pop an alert into (see PRODUCT.md). */
    public function test_housekeeping_is_not_authorised(): void
    {
        $staff = $this->staff(['role' => 'housekeeping', 'email' => 'hk@example.test']);

        $this->assertFalse($this->authCallback()($staff));
    }

    /**
     * A suspended account keeps its session cookie until it expires, so the
     * role check alone would keep feeding it desk traffic.
     */
    public function test_suspended_staff_are_not_authorised(): void
    {
        $staff = $this->staff(['is_suspended' => true]);

        $this->assertFalse($this->authCallback()($staff));
    }

    /**
     * The end-to-end handshake a browser actually performs, which is the only
     * thing that proves `guards: ['staff']` is doing its job. Without that
     * option Laravel resolves the default `web` guard — which holds *guests* —
     * so a signed-in staff member would be treated as nobody and this 403s.
     */
    public function test_signed_in_staff_can_authorise_the_channel(): void
    {
        $this->actingAs($this->staff(), 'staff')
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-staff.alerts',
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    /** An unauthenticated browser must not be able to open the channel. */
    public function test_guests_cannot_authorise_the_channel(): void
    {
        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-staff.alerts',
        ])->assertForbidden();
    }

    /**
     * A signed-in *guest* is still not staff. The two guards share a browser
     * and a session cookie, so this is the case where getting the guard wrong
     * would be invisible in manual testing and wide open in production.
     */
    public function test_a_signed_in_guest_cannot_authorise_the_channel(): void
    {
        // Built by hand rather than by factory: UserFactory still writes a
        // `name` column that was renamed to `username` back in the 2025_09_12
        // migration, so ::factory() throws.
        $guest = \App\Models\User::forceCreate([
            'username' => 'guest-tester',
            'email' => 'guest@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($guest)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-staff.alerts',
            ])
            ->assertForbidden();
    }
}
