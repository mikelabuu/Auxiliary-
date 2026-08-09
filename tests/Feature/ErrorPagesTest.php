<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The error views are the app's last line of output, so they get tested for the
 * two things that actually matter about them: that they render at all, and that
 * they keep rendering when the app around them does not.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /** Every status we ship a view for. */
    public static function errorCodes(): array
    {
        return [[403], [404], [419], [429], [500], [503]];
    }

    #[DataProvider('errorCodes')]
    public function test_error_view_renders_and_is_branded(int $code): void
    {
        $html = view("errors.{$code}")->render();

        $this->assertStringContainsString('Farmers', $html);
        $this->assertStringContainsString((string) $code, $html);

        // Self-contained by contract: no Vite tags, because a broken or absent
        // manifest is one of the things that produces a 500 in the first place.
        $this->assertStringNotContainsString('/build/', $html);
        $this->assertStringNotContainsString('@vite', $html);
    }

    public function test_a_missing_page_returns_the_branded_404(): void
    {
        $response = $this->get('/no-such-page-exists');

        $response->assertNotFound();
        $response->assertSee('We can’t find that page.', false);
    }

    /**
     * The whole reason the layout inlines its own CSS and wraps its guard
     * lookups: a 500 is frequently a database that is not answering, and the
     * page explaining that must not itself need the database.
     */
    public function test_the_500_page_renders_with_no_database(): void
    {
        // Point the default connection at something that cannot resolve, so any
        // query — including the one auth()->check() runs — throws.
        //
        // The swap has to be undone in a finally: RefreshDatabase opened a
        // transaction on the real connection and will try to roll it back after
        // this method returns. Leaving `database.default` pointed elsewhere
        // makes that rollback land on the wrong connection, which surfaces as
        // "cannot start a transaction within a transaction" in whichever test
        // happens to run next.
        $original = config('database.default');

        config(['database.connections.broken' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '1',
            'database' => 'nothing',
            'username' => 'nobody',
            'password' => '',
        ]]);

        try {
            config(['database.default' => 'broken']);
            DB::purge('broken');

            $html = view('errors.500')->render();
        } finally {
            config(['database.default' => $original]);
        }

        $this->assertStringContainsString('Something went wrong on our side.', $html);
        // Fell back to the hard-coded path rather than dying in route().
        $this->assertStringContainsString('Back to home', $html);
    }

    public function test_a_signed_in_guest_is_offered_their_bookings(): void
    {
        $user = User::forceCreate([
            'username' => 'errors-guest',
            'email' => 'errors-guest@example.test',
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $html = view('errors.404')->render();

        $this->assertStringContainsString('My bookings', $html);
        $this->assertStringNotContainsString('Sign in', $html);
    }

    /**
     * A 404 inside the console should not dump an admin onto the guest front
     * door — the way back is their own dashboard.
     */
    public function test_signed_in_staff_are_offered_their_dashboard(): void
    {
        $staff = Staff::create([
            'name' => 'Errors Tester',
            'email' => 'errors-staff@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'master_admin',
            'is_suspended' => false,
        ]);

        $this->actingAs($staff, 'staff');

        $html = view('errors.404')->render();

        $this->assertStringContainsString('Back to dashboard', $html);
        $this->assertStringContainsString(route('staff.dashboard'), $html);
    }

    /**
     * The words a person actually reads — heading and body — with the inline
     * stylesheet left out.
     *
     * strip_tags() alone is not enough: it removes the <style> tags but keeps
     * everything between them, so the declarations end up in the "text". That
     * is how an earlier version of the 429 test below failed on "5 ", matching
     * the 5 in `oklch(94.5% 0.025 90)` rather than anything a user could see.
     */
    private function visibleCopy(string $view): string
    {
        $html = view($view)->render();

        preg_match('#<h1 class="err-title">(.*?)</h1>#s', $html, $heading);
        preg_match('#<p class="err-body">(.*?)</p>#s', $html, $body);

        return html_entity_decode(trim(($heading[1] ?? '') . ' ' . ($body[1] ?? '')));
    }

    /**
     * PRODUCT.md makes this binding: attempt limits, lockout windows and resend
     * caps stay out of the UI, because they tell an attacker how far to push
     * and how long to wait. The throttles behind this page are 5-per-email,
     * 15-minute decay and 20-per-IP-per-minute; none of those may leak.
     */
    public function test_the_429_page_names_no_limits_or_windows(): void
    {
        $copy = $this->visibleCopy('errors.429');

        $this->assertNotSame('', $copy, 'Guard against the extraction silently matching nothing.');

        foreach (['15', 'minute', 'hour', 'attempt', '5', '20'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase(
                $leak,
                $copy,
                "The 429 page must not disclose '{$leak}'."
            );
        }
    }

    /**
     * A 403 must not confirm that the thing behind it exists, for the same
     * enumeration reason the login form returns one message for every failure.
     */
    public function test_the_403_page_does_not_confirm_the_resource_exists(): void
    {
        $copy = $this->visibleCopy('errors.403');

        $this->assertNotSame('', $copy);

        foreach (['belongs to', 'another guest', 'owner', 'does not exist'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase($leak, $copy);
        }
    }
}
