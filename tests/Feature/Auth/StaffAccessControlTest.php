<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * The staff authorization matrix.
 *
 * Two route groups, guarded by StaffRoleMiddleware:
 *   staff.role:admin,master_admin      — the admin console
 *   staff.role:frontdesk,master_admin  — the front desk
 *
 * Every role must be checked against both groups. A single missing or
 * mistyped role in a route definition silently grants a whole console to the
 * wrong staff member, and nothing in the UI would reveal it.
 */
class StaffAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Make::catalog();
    }

    /** Representative admin-console routes. */
    public static function adminRoutes(): array
    {
        return [
            'dashboard'     => ['/staff/dashboard'],
            'rooms'         => ['/staff/rooms'],
            'booking hub'   => ['/bookings'],
            'user records'  => ['/staff/user-records'],
            'staff records' => ['/staff/staff-records'],
            'audit logs'    => ['/staff/audit-logs'],
            'discounts'     => ['/staff/discounts'],
        ];
    }

    /** Representative front-desk routes. */
    public static function frontdeskRoutes(): array
    {
        return [
            'dashboard' => ['/front-desk/dashboard'],
            'rooms'     => ['/front-desk/rooms'],
            'walk-in'   => ['/front-desk/create'],
            'bookings'  => ['/front-desk/bookings'],
        ];
    }

    // ---------------------------------------------------------------- denied

    #[DataProvider("adminRoutes")]
    public function test_front_desk_staff_are_locked_out_of_the_admin_console(string $route): void
    {
        $this->actingAs(Make::staff('frontdesk'), 'staff')->get($route)->assertForbidden();
    }

    #[DataProvider("adminRoutes")]
    public function test_housekeeping_staff_are_locked_out_of_the_admin_console(string $route): void
    {
        $this->actingAs(Make::staff('housekeeping'), 'staff')->get($route)->assertForbidden();
    }

    #[DataProvider("frontdeskRoutes")]
    public function test_admins_are_locked_out_of_the_front_desk(string $route): void
    {
        $this->actingAs(Make::staff('admin'), 'staff')->get($route)->assertForbidden();
    }

    #[DataProvider("frontdeskRoutes")]
    public function test_housekeeping_staff_are_locked_out_of_the_front_desk(string $route): void
    {
        $this->actingAs(Make::staff('housekeeping'), 'staff')->get($route)->assertForbidden();
    }

    /**
     * The security-relevant assertion is that access is denied. Where the
     * visitor is sent afterwards is graded separately below.
     */
    #[DataProvider("adminRoutes")]
    public function test_anonymous_visitors_are_denied_the_admin_console(string $route): void
    {
        $response = $this->get($route);

        $this->assertTrue($response->isRedirect(), 'An anonymous visitor was not turned away.');
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }

    /**
     * A public guest account must never satisfy the staff guard, however it
     * was obtained.
     */
    #[DataProvider("adminRoutes")]
    public function test_a_public_guest_account_cannot_reach_the_admin_console(string $route): void
    {
        $response = $this->actingAs(Make::user())->get($route);

        $this->assertTrue(
            $response->isRedirect() || $response->isForbidden(),
            'A public guest account reached the admin console.',
        );
        $this->assertFalse($response->isOk());
    }

    #[DataProvider("frontdeskRoutes")]
    public function test_a_public_guest_account_cannot_reach_the_front_desk(string $route): void
    {
        $response = $this->actingAs(Make::user())->get($route);

        $this->assertTrue(
            $response->isRedirect() || $response->isForbidden(),
            'A public guest account reached the front desk.',
        );
        $this->assertFalse($response->isOk());
    }

    /**
     * DEFECT PROBE — low severity, but a real inconsistency.
     *
     * App\Http\Middleware\Authenticate::redirectTo() hardcodes route('login')
     * for every guard, so a staff member whose session has expired is dropped
     * on the *customer* login form, where their credentials will not work.
     * StaffRoleMiddleware, by contrast, correctly sends them to staff.login.
     *
     * Access is still denied either way — this is a wrong-destination bug, not
     * an access-control hole.
     */
    public function test_an_expired_staff_session_lands_on_the_staff_login(): void
    {
        $this->get('/staff/dashboard')->assertRedirect(route('staff.login'));
    }

    // --------------------------------------------------------------- allowed

    #[DataProvider("adminRoutes")]
    public function test_an_admin_can_reach_the_admin_console(string $route): void
    {
        $this->actingAs(Make::staff('admin'), 'staff')->get($route)->assertOk();
    }

    #[DataProvider("frontdeskRoutes")]
    public function test_front_desk_staff_can_reach_the_front_desk(string $route): void
    {
        $this->actingAs(Make::staff('frontdesk'), 'staff')->get($route)->assertOk();
    }

    #[DataProvider("adminRoutes")]
    public function test_a_master_admin_can_reach_the_admin_console(string $route): void
    {
        $this->actingAs(Make::staff('master_admin'), 'staff')->get($route)->assertOk();
    }

    #[DataProvider("frontdeskRoutes")]
    public function test_a_master_admin_can_reach_the_front_desk(string $route): void
    {
        $this->actingAs(Make::staff('master_admin'), 'staff')->get($route)->assertOk();
    }

    // ------------------------------------------------------------- backdoors

    /**
     * DEFECT PROBE — routes/web.php:74 registers GET /__dev-login, which calls
     * `auth('staff')->login(Staff::first())` with no password. It is gated on
     * `app()->environment('local')` and carries a "remove before commit"
     * comment, but it is committed and the shipped .env sets APP_ENV=local.
     *
     * Any deployment that keeps that .env hands full staff access to anyone
     * who visits the URL. The route should not exist in the codebase at all.
     */
    public function test_the_developer_login_backdoor_is_not_registered(): void
    {
        $exists = collect(app('router')->getRoutes()->getRoutes())
            ->contains(fn ($route) => str_contains($route->uri(), '__dev-login'));

        $this->assertFalse($exists, 'The /__dev-login backdoor route is still registered.');
    }

    /**
     * Belt and braces: even while the route exists, hitting it must not
     * produce an authenticated staff session.
     */
    public function test_the_developer_login_backdoor_does_not_authenticate_anyone(): void
    {
        Make::staff('master_admin');

        $this->get('/__dev-login');

        $this->assertFalse(auth('staff')->check(), 'The backdoor logged in a staff account.');
    }

    // ------------------------------------------------------------- suspended

    /**
     * A suspended staff account is the mechanism for revoking access from a
     * departed or compromised employee. If the middleware only checks the role
     * and never the flag, suspension is cosmetic for anyone holding a session.
     */
    public function test_a_suspended_admin_cannot_reach_the_admin_console(): void
    {
        $staff = Make::staff('admin', ['is_suspended' => true]);

        $response = $this->actingAs($staff, 'staff')->get('/staff/dashboard');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'A suspended staff account still reached the admin console.',
        );
    }
}
