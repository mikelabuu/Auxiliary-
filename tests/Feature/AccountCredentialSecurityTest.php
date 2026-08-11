<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Re-authentication and password strength on the account-settings paths.
 *
 * Two things were wrong together. The settings page posts both of its cards to
 * the same route, and they were told apart by "did current_password arrive?" —
 * so the profile card could not ask for a password without every profile save
 * being mistaken for a password change. The consequence was that a session
 * alone could move the email address, which is the address a password reset is
 * later sent to.
 */
class AccountCredentialSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function guest(): User
    {
        // User::create rather than the factory: the stock UserFactory still
        // seeds a `name` column this schema does not have (it is `full_name`),
        // which is why every other test in this suite builds users by hand.
        $user = User::create([
            'username' => 'existing_guest',
            'full_name' => 'Cruz, Juan, D',
            'email' => 'guest@example.test',
            'password' => Hash::make('correct-horse-battery'),
        ]);

        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function profilePayload(array $overrides = []): array
    {
        return array_merge([
            '_form' => 'profile',
            'username' => 'existing_guest',
            'email' => 'guest@example.test',
            'phone' => '09171234567',
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Email changes require the password
    // ---------------------------------------------------------------

    public function test_changing_email_without_the_current_password_is_rejected(): void
    {
        $user = $this->guest();

        $this->actingAs($user)
            ->put('/settings', $this->profilePayload(['email' => 'attacker@example.test']))
            ->assertSessionHasErrors('current_password');

        $this->assertSame('guest@example.test', $user->fresh()->email);
    }

    public function test_changing_email_with_a_wrong_current_password_is_rejected(): void
    {
        $user = $this->guest();

        $this->actingAs($user)
            ->put('/settings', $this->profilePayload([
                'email' => 'attacker@example.test',
                'current_password' => 'not-the-password',
            ]))
            ->assertSessionHasErrors('current_password');

        $this->assertSame('guest@example.test', $user->fresh()->email);
    }

    public function test_changing_email_with_the_correct_password_succeeds(): void
    {
        $user = $this->guest();

        $this->actingAs($user)
            ->put('/settings', $this->profilePayload([
                'email' => 'new-address@example.test',
                'current_password' => 'correct-horse-battery',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertSame('new-address@example.test', $fresh->email);
        // Verification must be cleared and the session dropped with it.
        $this->assertNull($fresh->email_verified_at);
        $this->assertGuest();
    }

    /**
     * The password is asked for only when the address actually moves —
     * otherwise editing a phone number would need a credential.
     */
    public function test_editing_username_and_phone_needs_no_password(): void
    {
        $user = $this->guest();

        $this->actingAs($user)
            ->put('/settings', $this->profilePayload([
                'username' => 'renamed_guest',
                'phone' => '09998887777',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertSame('renamed_guest', $fresh->username);
        $this->assertSame('09998887777', $fresh->phone);
        $this->assertSame('guest@example.test', $fresh->email);
    }

    /**
     * The profile card now carries a current_password field of its own. With
     * the old "did current_password arrive?" discriminator, filling it would
     * have sent the whole submission down the password branch and failed for
     * want of a new password.
     */
    public function test_a_profile_save_carrying_a_password_is_not_mistaken_for_a_password_change(): void
    {
        $user = $this->guest();
        $originalHash = $user->password;

        $this->actingAs($user)
            ->put('/settings', $this->profilePayload([
                'username' => 'renamed_guest',
                'current_password' => 'correct-horse-battery',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertSame('renamed_guest', $fresh->username);
        $this->assertSame($originalHash, $fresh->password, 'the password was changed by a profile save');
    }

    // ---------------------------------------------------------------
    // Password strength
    // ---------------------------------------------------------------

    public function test_guest_signup_rejects_a_password_under_eight_characters(): void
    {
        $this->post('/signup', [
            'first_name' => 'Juan',
            'middle_initial' => 'D',
            'last_name' => 'Cruz',
            'username' => 'juandc',
            'email' => 'juan@example.test',
            'password' => 'short7c',
            'password_confirmation' => 'short7c',
            'terms' => 'on',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'juan@example.test']);
    }

    public function test_guest_password_change_rejects_a_password_under_eight_characters(): void
    {
        $user = $this->guest();

        $this->actingAs($user)
            ->put('/settings', [
                '_form' => 'password',
                'current_password' => 'correct-horse-battery',
                'password' => 'short7c',
                'password_confirmation' => 'short7c',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('correct-horse-battery', $user->fresh()->password));
    }

    public function test_staff_creation_rejects_a_password_under_ten_characters(): void
    {
        $master = Staff::create([
            'name' => 'Master Account',
            'email' => 'master@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'master_admin',
            'is_suspended' => false,
        ]);

        $this->actingAs($master, 'staff')
            ->post('/staff/staff/create', [
                'name' => 'New Desk',
                'email' => 'desk@example.test',
                'role' => 'frontdesk',
                'password' => 'ninechars',
                'password_confirmation' => 'ninechars',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('staff', ['email' => 'desk@example.test']);
    }

    public function test_staff_creation_accepts_a_long_enough_password(): void
    {
        $master = Staff::create([
            'name' => 'Master Account',
            'email' => 'master@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'master_admin',
            'is_suspended' => false,
        ]);

        $this->actingAs($master, 'staff')
            ->post('/staff/staff/create', [
                'name' => 'New Desk',
                'email' => 'desk@example.test',
                'role' => 'frontdesk',
                'password' => 'a-perfectly-fine-password',
                'password_confirmation' => 'a-perfectly-fine-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('staff', ['email' => 'desk@example.test', 'role' => 'frontdesk']);
    }
}
