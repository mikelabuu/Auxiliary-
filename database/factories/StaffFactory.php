<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Staff>
 *
 * The Staff model does not use the HasFactory trait, so `Staff::factory()`
 * does not exist. Build through the factory class directly:
 *
 *     StaffFactory::new()->admin()->create()
 *
 * or via the Make helper: Make::staff('admin').
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'password'          => static::$password ??= Hash::make('password'),
            'role'              => 'frontdesk',
            'is_suspended'      => false,
            'email_verified_at' => now(),

            // Set explicitly so the in-memory model carries every attribute a
            // row loaded from the database would. The session guard reads
            // remember_token, and Model::shouldBeStrict() throws on reading an
            // attribute that was never hydrated.
            'remember_token'    => Str::random(10),
            'last_login_at'     => null,
        ];
    }

    public function role(string $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function masterAdmin(): static
    {
        return $this->role('master_admin');
    }

    public function admin(): static
    {
        return $this->role('admin');
    }

    public function frontdesk(): static
    {
        return $this->role('frontdesk');
    }

    public function housekeeping(): static
    {
        return $this->role('housekeeping');
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['is_suspended' => true]);
    }
}
