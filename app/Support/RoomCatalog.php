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
    /**
     * Resolved catalog for the current request.
     *
     * Every read used to re-run Schema::hasTable() plus a full room_types
     * select. That was invisible while the only callers were page controllers
     * asking once — but dormTypes()/standardTypes()/capacityFor() all route
     * through here, and the dashboard calls those from inside a filter()
     * closure, once per room. The result was 72 catalog reads and 144 queries
     * on a single page load, two thirds of its total database work.
     *
     * The catalog cannot change mid-request, so it is resolved once. Tests
     * that edit room_types mid-test must call flush(); Tests\TestCase does it
     * between cases so a memo cannot leak from one test into the next.
     */
    private static ?array $memo = null;

    public static function flush(): void
    {
        static::$memo = null;
    }

    public static function all(): array
    {
        if (static::$memo !== null) {
            return static::$memo;
        }

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

        // Normalise the presentation keys the detail page relies on, so a type
        // with no config entry still renders a gallery (its hero shot alone)
        // instead of an empty mosaic.
        foreach ($catalog as $slug => $type) {
            $catalog[$slug]['tags'] = $type['tags'] ?? [];
            $catalog[$slug]['gallery'] = collect([$type['image'] ?? null])
                ->merge($type['gallery'] ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return static::$memo = $catalog;
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
     * Capacity for one slug, with a caller-supplied fallback for a type the
     * catalog does not know.
     *
     * Use this rather than a local map. Capacity is admin-editable in Room
     * Types & Pricing, so a hardcoded copy is not merely duplication — it is a
     * number that stops tracking the one staff can change, silently, in
     * whatever it is used to calculate.
     */
    public static function capacityFor(?string $slug, int $fallback = 1): int
    {
        if ($slug === null) {
            return $fallback;
        }

        return static::capacityMap()[strtolower(trim($slug))] ?? $fallback;
    }

    /**
     * Shared-occupancy room types.
     *
     * Derived from the slug rather than listed, so a third dormitory added in
     * Room Types & Pricing is grouped correctly the day it is created instead
     * of the day somebody remembers to update a literal.
     *
     * @return array<int, string>
     */
    public static function dormTypes(): array
    {
        return collect(static::all())
            ->keys()
            ->filter(fn ($slug) => str_starts_with($slug, 'dormitory'))
            ->values()
            ->all();
    }

    /**
     * Everything that is not a dormitory — defined as the remainder rather
     * than as its own list, which is the part that matters.
     *
     * The two groups were previously two hardcoded arrays, and 'deluxe' had
     * been added to the catalog without being added to either. Seven of the
     * hostel's twenty-two rooms were therefore counted in neither, so the
     * occupancy snapshot quietly described two thirds of the building. Any
     * pair of hand-listed groups can drift apart like that; a group and its
     * complement cannot.
     *
     * @return array<int, string>
     */
    public static function standardTypes(): array
    {
        return array_values(array_diff(
            collect(static::all())->keys()->all(),
            static::dormTypes()
        ));
    }

    /*
     * NOTE: the dashboard's Room Status Map used to group by room type, from
     * three filters it built itself — dorm, standard, deluxe. standardTypes()
     * is *everything that is not a dormitory*, deluxe included, so the seven
     * deluxe rooms were drawn in both the Standard row and the Deluxe row: 29
     * tiles under a heading reading "All 22 rooms at a glance", with the live
     * feed patching only the first copy so the two disagreed on colour.
     *
     * The map is laid out by wing now (App\Models\Room::groupByWing), which is
     * how Room Management already reads and how staff actually walk the
     * building, so no type partition lives here. dormTypes()/standardTypes()
     * remain for the occupancy snapshot, where "shared occupancy vs private
     * room" is the real question.
     */

    /**
     * Short display label for a room-type slug: 'double' => 'Double', and every
     * dormitory variant collapsed to a single 'Dormitory', because guests and
     * staff say "dormitory", not "dormitory2".
     */
    public static function label(?string $slug, string $fallback = 'Room'): string
    {
        $slug = $slug ? strtolower(trim($slug)) : '';

        if ($slug === '') {
            return $fallback;
        }

        return ucfirst(str_starts_with($slug, 'dormitory') ? 'dormitory' : $slug);
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
