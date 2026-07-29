<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * One form now serves guests, front desk and admins. These cover the guard
 * resolution — that the right identity is picked, that neither side can be
 * told apart from the outside, and that an address can only ever mean one
 * person.
 */
class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    private function guest(string $email = 'guest@example.test'): User
    {
        $user = User::create([
            'username' => 'guest1',
            'email' => $email,
            'password' => Hash::make('password-12345'),
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function staff(string $email = 'staff@example.test', string $role = 'admin'): Staff
    {
        return Staff::create([
            'name' => 'Staff Member',
            'email' => $email,
            'password' => 'password-12345',
            'role' => $role,
            'is_suspended' => false,
        ]);
    }

    public function test_guest_signs_in_through_the_shared_form(): void
    {
        $user = $this->guest();

        $this->post('/login', [
            'email' => 'guest@example.test',
            'password' => 'password-12345',
        ])->assertRedirect('/checkout');

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_signs_in_through_the_shared_form(): void
    {
        config(['staff.otp_enabled' => false]);
        $staff = $this->staff();

        $this->post('/login', [
            'email' => 'staff@example.test',
            'password' => 'password-12345',
        ])->assertRedirect('/staff/dashboard');

        $this->assertAuthenticatedAs($staff, 'staff');
    }

    public function test_front_desk_lands_on_its_own_dashboard(): void
    {
        config(['staff.otp_enabled' => false]);
        $staff = $this->staff('fd@example.test', 'frontdesk');

        $this->post('/login', [
            'email' => 'fd@example.test',
            'password' => 'password-12345',
        ])->assertRedirect('/front-desk/dashboard');

        $this->assertAuthenticatedAs($staff, 'staff');
    }

    public function test_email_is_case_insensitive(): void
    {
        $user = $this->guest();

        $this->post('/login', [
            'email' => '  GUEST@Example.TEST ',
            'password' => 'password-12345',
        ])->assertRedirect('/checkout');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * The response must not reveal whether an address is a guest, staff, or
     * absent entirely — otherwise the shared form becomes a way to map which
     * addresses belong to staff.
     */
    public function test_guest_and_staff_failures_are_indistinguishable(): void
    {
        $this->guest();
        $this->staff();

        foreach (['guest@example.test', 'staff@example.test', 'nobody@example.test'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors(['email' => 'Invalid email or password.']);

            $this->flushSession();
        }

        $this->assertGuest();
        $this->assertGuest('staff');
    }

    public function test_suspended_staff_cannot_sign_in(): void
    {
        config(['staff.otp_enabled' => false]);
        $this->staff()->update(['is_suspended' => true]);

        $this->post('/login', [
            'email' => 'staff@example.test',
            'password' => 'password-12345',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('staff');
    }

    public function test_suspended_guest_cannot_sign_in(): void
    {
        $this->guest()->update(['is_suspended' => true]);

        $this->post('/login', [
            'email' => 'guest@example.test',
            'password' => 'password-12345',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_old_staff_login_url_redirects_to_the_shared_form(): void
    {
        $this->get('/staff/login')->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // One address, one identity
    // ---------------------------------------------------------------

    public function test_signup_rejects_an_email_already_used_by_staff(): void
    {
        $this->staff('taken@example.test');

        $this->post('/signup', [
            'first_name' => 'A', 'middle_initial' => 'B', 'last_name' => 'C',
            'username' => 'newguest', 'email' => 'taken@example.test',
            'password' => 'password-12345', 'password_confirmation' => 'password-12345',
            'terms' => 'on',
        ])->assertSessionHasErrors('email');

        $this->assertSame(0, User::where('email', 'taken@example.test')->count());
    }

    public function test_staff_creation_rejects_an_email_already_used_by_a_guest(): void
    {
        $master = $this->staff('master@example.test', 'master_admin');
        $this->guest('taken@example.test');

        $this->actingAs($master, 'staff')
            ->post('/staff/staff/create', [
                'name' => 'New Staff',
                'email' => 'taken@example.test',
                'role' => 'frontdesk',
                'password' => 'password-12345',
                'password_confirmation' => 'password-12345',
            ])->assertSessionHasErrors('email');

        $this->assertSame(0, Staff::where('email', 'taken@example.test')->count());
    }
}
