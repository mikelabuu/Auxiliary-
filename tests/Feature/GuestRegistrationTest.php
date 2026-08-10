<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Guest registration: where it lands, and what it refuses.
 *
 * Both halves come from the same report. Registering used to drop the new
 * guest back on the landing page still signed out, with a flash under a key
 * the auth board does not even render — so nothing had visibly happened. And
 * the name fields were validated as 'string|max:255', which is not a name
 * check: "1" passed as a middle initial.
 */
class GuestRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Several cases walk a list of rejected inputs, which is more than the
        // 5-per-10-minutes registration limiter allows from one IP. The limiter
        // is not what these assert; validation is.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /** A registration that should succeed, with $overrides applied. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'middle_initial' => 'B',
            'last_name' => 'Dela Cruz',
            'username' => 'anadc',
            'email' => 'ana@example.test',
            'password' => 'password-12345',
            'password_confirmation' => 'password-12345',
            'terms' => 'on',
            // The hidden marker the register panel posts.
            'panel' => 'register',
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Where it lands
    // ---------------------------------------------------------------

    public function test_registration_sends_the_new_guest_to_the_login_page(): void
    {
        $this->post('/signup', $this->payload())
            ->assertRedirect(route('login'))
            // The key the auth board's notes partial actually renders.
            ->assertSessionHas('status');

        $this->assertSame(1, User::where('email', 'ana@example.test')->count());

        // Registration does not sign anyone in — that is the whole reason the
        // login page is the destination.
        $this->assertGuest();
    }

    public function test_the_stored_name_keeps_the_last_first_middle_shape(): void
    {
        $this->post('/signup', $this->payload(['middle_initial' => 'b.']));

        $this->assertSame(
            'Dela Cruz, Ana, B',
            User::where('email', 'ana@example.test')->value('full_name')
        );
    }

    // ---------------------------------------------------------------
    // What it refuses
    // ---------------------------------------------------------------

    /** The reported case: a digit typed into M.I. sailed through. */
    public function test_a_number_is_not_a_middle_initial(): void
    {
        $this->post('/signup', $this->payload(['middle_initial' => '1']))
            ->assertSessionHasErrors('middle_initial');

        $this->assertSame(0, User::count());
    }

    public function test_a_middle_initial_is_one_letter(): void
    {
        foreach (['ab', 'B!', '  ', '#', '12'] as $value) {
            $this->post('/signup', $this->payload(['middle_initial' => $value]))
                ->assertSessionHasErrors('middle_initial');
        }

        $this->assertSame(0, User::count());
    }

    public function test_names_reject_digits_and_markup(): void
    {
        $bad = ['Ana3', '<script>alert(1)</script>', '...', '09171234567'];

        foreach ($bad as $value) {
            $this->post('/signup', $this->payload(['first_name' => $value]))
                ->assertSessionHasErrors('first_name');

            $this->post('/signup', $this->payload(['last_name' => $value]))
                ->assertSessionHasErrors('last_name');
        }

        $this->assertSame(0, User::count());
    }

    public function test_real_names_still_pass(): void
    {
        // Accents, Ñ, hyphens, apostrophes and spaces are all ordinary here.
        $this->post('/signup', $this->payload([
            'first_name' => 'María-José',
            'last_name' => "Niño O'Brien",
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, User::count());
    }

    public function test_the_terms_box_must_actually_be_ticked(): void
    {
        // 'required' passed on any truthy value, including the string '0' an
        // unchecked box can be posted as; 'accepted' is the rule that means it
        // came back ticked.
        $this->post('/signup', $this->payload(['terms' => '0']))
            ->assertSessionHasErrors('terms');

        $payload = $this->payload();
        unset($payload['terms']);
        $this->post('/signup', $payload)->assertSessionHasErrors('terms');

        $this->assertSame(0, User::count());
    }

    public function test_a_password_past_bcrypts_limit_is_refused_not_truncated(): void
    {
        $long = str_repeat('a', 80);

        $this->post('/signup', $this->payload([
            'password' => $long,
            'password_confirmation' => $long,
        ]))->assertSessionHasErrors('password');

        $this->assertSame(0, User::count());
    }

    public function test_a_rejected_signup_reopens_the_register_panel(): void
    {
        // The page keys off the hidden marker the form posts, so this holds
        // even when the field that failed is the one left empty — reading
        // old('username') put the sign-in panel's errors over the wrong form.
        $this->from(route('login'))
            ->post('/signup', $this->payload(['username' => '']))
            ->assertSessionHasErrors('username');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee("show('register'", false);
    }
}
