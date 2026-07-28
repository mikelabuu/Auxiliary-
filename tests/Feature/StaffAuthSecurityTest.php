<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\StaffOtp;
use App\Notifications\StaffLoginOtpNotification;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression cover for the staff auth hardening pass.
 *
 * The headline case is the "000000" master OTP that used to be accepted for
 * any account: it skipped the code match, the used_at check and the expiry
 * check, so a leaked password was enough to walk straight through 2FA.
 */
class StaffAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'admin',
            'is_suspended' => false,
        ], $overrides));
    }

    /** Puts the session in the state step 2 expects, without re-running step 1. */
    private function pendingOtpSession(Staff $staff): array
    {
        return ['staff_pending_id' => $staff->id];
    }

    public function test_master_otp_000000_is_rejected(): void
    {
        config(['staff.otp_enabled' => true]);
        $staff = $this->staff();

        StaffOtp::create([
            'staff_id' => $staff->id,
            'otp_code' => '135790',
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->withSession($this->pendingOtpSession($staff))
            ->post('/staff/otp', ['otp_code' => '000000']);

        $response->assertSessionHasErrors('otp_code');
        $this->assertGuest('staff');
    }

    public function test_expired_otp_is_rejected(): void
    {
        config(['staff.otp_enabled' => true]);
        $staff = $this->staff();

        StaffOtp::create([
            'staff_id' => $staff->id,
            'otp_code' => '135790',
            'otp_expires_at' => now()->subMinute(),
        ]);

        $this->withSession($this->pendingOtpSession($staff))
            ->post('/staff/otp', ['otp_code' => '135790'])
            ->assertSessionHasErrors('otp_code');

        $this->assertGuest('staff');
    }

    public function test_already_used_otp_is_rejected(): void
    {
        config(['staff.otp_enabled' => true]);
        $staff = $this->staff();

        StaffOtp::create([
            'staff_id' => $staff->id,
            'otp_code' => '135790',
            'otp_expires_at' => now()->addMinutes(5),
            'used_at' => now(),
        ]);

        $this->withSession($this->pendingOtpSession($staff))
            ->post('/staff/otp', ['otp_code' => '135790'])
            ->assertSessionHasErrors('otp_code');

        $this->assertGuest('staff');
    }

    public function test_valid_otp_still_logs_staff_in(): void
    {
        config(['staff.otp_enabled' => true]);
        $staff = $this->staff();

        StaffOtp::create([
            'staff_id' => $staff->id,
            'otp_code' => '135790',
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $this->withSession($this->pendingOtpSession($staff))
            ->post('/staff/otp', ['otp_code' => '135790'])
            ->assertRedirect('/staff/dashboard');

        $this->assertAuthenticatedAs($staff, 'staff');
    }

    public function test_consuming_one_otp_burns_the_other_outstanding_codes(): void
    {
        config(['staff.otp_enabled' => true]);
        $staff = $this->staff();

        $stale = StaffOtp::create([
            'staff_id' => $staff->id,
            'otp_code' => '111111',
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        StaffOtp::create([
            'staff_id' => $staff->id,
            'otp_code' => '222222',
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $this->withSession($this->pendingOtpSession($staff))
            ->post('/staff/otp', ['otp_code' => '222222']);

        $this->assertNotNull($stale->fresh()->used_at, 'the earlier code should no longer be replayable');
    }

    public function test_login_step_one_issues_an_otp_and_marks_the_session_pending(): void
    {
        config(['staff.otp_enabled' => true]);
        Notification::fake();
        $staff = $this->staff();

        $this->post('/staff/login', [
            'staff_email' => 'admin@example.test',
            'staff_password' => 'correct-horse-battery',
        ])->assertRedirect(route('staff.otp.form'));

        Notification::assertSentTo($staff, StaffLoginOtpNotification::class);
        $this->assertSame($staff->id, session('staff_pending_id'));
        $this->assertGuest('staff');
    }

    public function test_generated_otp_is_a_six_digit_code(): void
    {
        config(['staff.otp_enabled' => true]);
        Notification::fake();
        $staff = $this->staff();

        $this->post('/staff/login', [
            'staff_email' => 'admin@example.test',
            'staff_password' => 'correct-horse-battery',
        ]);

        $code = StaffOtp::where('staff_id', $staff->id)->value('otp_code');
        $this->assertMatchesRegularExpression('/^[1-9]\d{5}$/', (string) $code);
    }

    public function test_mail_failure_does_not_strand_the_login(): void
    {
        config(['staff.otp_enabled' => true]);
        $staff = $this->staff();

        // Simulate the mailer throwing mid-request, which is what an SMTP
        // outage looks like here (the notification sends inline).
        $dispatcher = \Mockery::mock(NotificationDispatcher::class);
        $dispatcher->shouldReceive('send')->andThrow(new \RuntimeException('smtp down'));
        $this->app->instance(NotificationDispatcher::class, $dispatcher);

        $response = $this->post('/staff/login', [
            'staff_email' => 'admin@example.test',
            'staff_password' => 'correct-horse-battery',
        ]);

        // Not a 500, and the session still knows who is mid-login so the
        // Resend button on the OTP form is usable.
        $response->assertRedirect(route('staff.otp.form'));
        $this->assertSame($staff->id, session('staff_pending_id'));
        $this->assertGuest('staff');
    }

    public function test_unknown_staff_email_gets_the_same_error_as_a_bad_password(): void
    {
        $this->staff();

        // Both cases must yield the identical message, or the response tells
        // an attacker which staff emails are real.
        $this->post('/staff/login', [
            'staff_email' => 'nobody@example.test',
            'staff_password' => 'whatever',
        ])->assertSessionHasErrors(['staff_email' => 'Invalid staff credentials']);

        $this->flushSession();

        $this->post('/staff/login', [
            'staff_email' => 'admin@example.test',
            'staff_password' => 'not-the-password',
        ])->assertSessionHasErrors(['staff_email' => 'Invalid staff credentials']);
    }

    public function test_suspended_staff_session_is_terminated_mid_session(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff, 'staff')
            ->get('/staff/user-records')
            ->assertOk();

        $staff->update(['is_suspended' => true]);

        $this->actingAs($staff, 'staff')
            ->get('/staff/user-records')
            ->assertRedirect(route('staff.login'));
    }

    public function test_suspended_staff_gets_json_403_on_ajax_routes(): void
    {
        $staff = $this->staff(['is_suspended' => true]);

        $this->actingAs($staff, 'staff')
            ->getJson('/staff/search?q=test')
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_customer_login_is_rate_limited(): void
    {
        \App\Models\User::create([
            'username' => 'guest1',
            'email' => 'guest@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/user', [
                'email' => 'guest@example.test',
                'password' => 'wrong',
            ]);
        }

        // Sixth attempt is refused even though the password is now correct.
        $response = $this->post('/login/user', [
            'email' => 'guest@example.test',
            'password' => 'correct-password',
        ]);

        $this->assertStringContainsString(
            'Too many login attempts',
            (string) $response->getSession()->get('errors')->first('email')
        );
        $this->assertGuest();
    }
}
