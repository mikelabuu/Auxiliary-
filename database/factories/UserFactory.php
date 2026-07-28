<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Shared across every generated user so tests can log in with 'password'
     * without paying for a bcrypt hash per row.
     */
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            // NOTE: the column is `username`, not `name` — renamed by
            // 2025_09_12_000000_rename_name_to_username_in_users_table.
            'username'          => fake()->unique()->userName(),
            'email'             => fake()->unique()->safeEmail(),
            'phone'             => '09' . fake()->numerify('#########'),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'is_suspended'      => false,

            // Nullable columns are set explicitly so the in-memory model
            // carries every attribute a row loaded from the database would.
            // Model::shouldBeStrict() throws on reading an attribute that was
            // never hydrated, and a factory that omits them produces models
            // unlike anything the application actually sees.
            'last_login_at'     => null,
            'last_cancelled_at' => null,
        ];
    }

    /**
     * A guest who has not clicked the verification link. The booking flow sits
     * behind the `verified` middleware, so this state should be locked out of
     * /checkout and /booking.
     */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['is_suspended' => true]);
    }
}
