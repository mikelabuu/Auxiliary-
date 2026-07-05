<?php

namespace App\Support;

use App\Models\RoomType;
use Illuminate\Support\Facades\Schema;

/**
 * Public room catalog: merges the presentation data in config/room_types.php
 * (images, descriptions, amenities) with the authoritative pricing/capacity
 * managed by staff in the `room_types` table. The DB always wins for
 * `price` and `beds`, so admin rate changes reach the public site instantly
 * and the frontend can never dictate what a room costs.
 */
class RoomCatalog
{
    /**
     * Catalog keyed by room-type slug (e.g. 'double' => [...]).
     *
     * @return array<string, array>
     */
    public static function all(): array
    {
        $catalog = collect(config('room_types', []))->keyBy('id')->toArray();

        // Overlay live pricing/capacity from the DB when available.
        if (Schema::hasTable('room_types')) {
            foreach (RoomType::all() as $type) {
                if (isset($catalog[$type->slug])) {
                    $catalog[$type->slug]['price'] = (float) $type->base_price;
                    $catalog[$type->slug]['beds']  = (int) $type->capacity;
                } else {
                    // Type created by staff that has no config presentation yet.
                    $catalog[$type->slug] = [
                        'id'          => $type->slug,
                        'title'       => $type->name,
                        'description' => '',
                        'floor'       => 'Farmers Hostel',
                        'beds'        => (int) $type->capacity,
                        'price'       => (float) $type->base_price,
                        'image'       => 'image/hostel1.jpg',
                        'capacity'    => $type->capacity . ' pax',
                        'includes'    => [],
                        'amenities'   => [],
                    ];
                }
            }
        }

        return $catalog;
    }

    /**
     * Single room type by slug, or null.
     */
    public static function find(?string $slug): ?array
    {
        if ($slug === null) {
            return null;
        }

        return static::all()[$slug] ?? null;
    }

    /**
     * Capacity (beds) map: slug => beds. Backend-authoritative.
     *
     * @return array<string, int>
     */
    public static function capacityMap(): array
    {
        return collect(static::all())->map(fn ($t) => (int) $t['beds'])->toArray();
    }

    /**
     * Lowest nightly rate across the catalog (for "from ₱X / night" UI).
     */
    public static function minPrice(): float
    {
        $prices = collect(static::all())->pluck('price')->filter();

        return (float) ($prices->min() ?? 0);
    }
}
