<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 *
 * `room_types` is the authoritative source for nightly rate and capacity —
 * RoomCatalog overlays these values on top of config/room_types.php, and the
 * DB always wins. Seed this table in any test that asserts on price.
 */
class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(1);

        return [
            'slug'       => $slug,
            'name'       => ucfirst($slug),
            'base_price' => 1500.00,
            'capacity'   => 2,
        ];
    }

    public function slug(string $slug): static
    {
        return $this->state(fn () => [
            'slug' => $slug,
            'name' => ucfirst($slug),
        ]);
    }

    public function price(float $price): static
    {
        return $this->state(fn () => ['base_price' => $price]);
    }

    public function capacity(int $beds): static
    {
        return $this->state(fn () => ['capacity' => $beds]);
    }
}
