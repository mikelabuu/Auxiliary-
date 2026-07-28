<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\Make;
use Tests\TestCase;

/**
 * Rate limiting on the endpoints that are worth abusing.
 *
 * Before this the application had exactly one throttle, on the
 * verification-resend route: staff login was brute-forceable, the OTP endpoint
 * would accept unlimited guesses at a six-digit code, and OTP resend would send
 * an outbound message as fast as it was asked to.
 *
 * The limiters live in AppServiceProvider::configureRateLimiting().
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The limiter is cache-backed and the array store persists for the
        // whole process, so counts would otherwise leak between tests.
        RateLimiter::clear('');
        cache()->flush();

        Make::catalog();
        Make::room('101', 'double');
    }

    /**
     * Hit a route until it throttles, returning the status of each attempt.
     *
     * @return array<int, int>
     */
    private function hammer(string $uri, array $payload, int $times): array
    {
        $statuses = [];

        for ($i = 0; $i < $times; $i++) {
            $statuses[] = $this->post($uri, $payload)->status();
        }

        return $statuses;
    }

    public function test_guest_login_is_throttled_after_repeated_failures(): void
    {
        Make::user(['email' => 'victim@example.test']);

        $statuses = $this->hammer('/login/user', [
            'email'    => 'victim@example.test',
            'password' => 'wrong-password',
        ], 7);

        $this->assertContains(429, $statuses, 'Guest login accepted unlimited password attempts.');
    }

    /**
     * Throttling one account must not lock out everyone else — the limiter is
     * keyed on the submitted identifier as well as the address, so an attacker
     * hammering one login cannot deny service to the rest.
     */
    public function test_throttling_one_account_does_not_lock_out_another(): void
    {
        Make::user(['email' => 'victim@example.test']);
        Make::user(['email' => 'bystander@example.test']);

        $this->hammer('/login/user', [
            'email'    => 'victim@example.test',
            'password' => 'wrong-password',
        ], 7);

        $this->post('/login/user', [
            'email'    => 'bystander@example.test',
            'password' => 'password',
        ])->assertStatus(302);
    }

    public function test_staff_login_is_throttled(): void
    {
        Make::staff('admin', ['email' => 'admin@example.test']);

        $statuses = $this->hammer('/staff/login', [
            'email'    => 'admin@example.test',
            'password' => 'wrong-password',
        ], 7);

        $this->assertContains(429, $statuses, 'Staff login accepted unlimited password attempts.');
    }

    /**
     * A six-digit code is guessable in a few thousand tries, so the attempt
     * rate is the thing that makes the second factor worth having.
     */
    public function test_otp_verification_is_throttled(): void
    {
        $statuses = $this->hammer('/staff/otp', ['otp' => '000000'], 8);

        $this->assertContains(429, $statuses, 'OTP verification accepted unlimited guesses.');
    }

    /**
     * Every resend costs an outbound message.
     */
    public function test_otp_resend_is_throttled_tightly(): void
    {
        $statuses = $this->hammer('/staff/otp/resend', [], 4);

        $this->assertContains(429, $statuses, 'OTP resend could be used to send unlimited messages.');
    }

    public function test_password_reset_requests_are_throttled(): void
    {
        Make::user(['email' => 'victim@example.test']);

        $statuses = $this->hammer('/forgot-password', ['email' => 'victim@example.test'], 5);

        $this->assertContains(429, $statuses, 'Password reset could be used to send unlimited mail.');
    }

    public function test_signup_is_throttled(): void
    {
        $statuses = [];

        for ($i = 0; $i < 5; $i++) {
            $statuses[] = $this->post('/signup', [
                'username'              => "spam{$i}",
                'email'                 => "spam{$i}@example.test",
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ])->status();
        }

        $this->assertContains(429, $statuses, 'Signup accepted unlimited account creation.');
    }

    /**
     * Booking is authenticated, so the limiter keys on the account rather than
     * the address — one guest cannot exhaust the allowance for everyone behind
     * the same NAT.
     */
    public function test_booking_creation_is_throttled_per_account(): void
    {
        $user    = Make::user();
        $payload = \Tests\Support\BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray();

        $statuses = [];
        for ($i = 0; $i < 12; $i++) {
            $statuses[] = $this->actingAs($user)->post('/booking', $payload)->status();
        }

        $this->assertContains(429, $statuses, 'Booking creation was unlimited.');
    }

    public function test_a_normal_login_is_not_throttled(): void
    {
        $user = Make::user(['email' => 'normal@example.test']);

        $this->post('/login/user', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertStatus(302);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * A guest making one booking must never see a 429 — the limits exist to
     * stop abuse, not ordinary use.
     */
    public function test_a_single_booking_is_not_throttled(): void
    {
        $this->actingAs(Make::user())->post('/booking', \Tests\Support\BookingPayload::make()
            ->block('double', ['101'], guests: 2)
            ->guests(2)
            ->toArray())
            ->assertSessionHasNoErrors();
    }
}
