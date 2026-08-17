<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use Illuminate\Database\Seeder;

/**
 * The property's inventory — 22 rooms across 7 types — split out of
 * DatabaseSeeder so it can be run against a database that already has data.
 *
 * DatabaseSeeder cannot be: it opens by creating three staff and a guest at
 * fixed addresses, so anywhere accounts already exist it dies on the unique
 * email constraint before reaching a single room. It also still carried the
 * original placeholder inventory — 12 rooms in "Block A"/"Block B" wings that
 * Room::WING_ORDER has never heard of, priced from before the client set their
 * rates. The data below is the real thing, read back out of the working system,
 * and DatabaseSeeder now calls this rather than keeping a second copy.
 *
 * Every write is insert-if-absent, keyed on the natural unique column, for the
 * same reason the Family Room migration is: room type prices and room statuses
 * are staff-editable from the console, and a re-run must not quietly undo what
 * the desk has changed. Running this twice is a no-op.
 */
class RoomSeeder extends Seeder
{
    /**
     * Rates here are the *type's* base price. A room also carries its own
     * `price` because the schema allows one room to deviate from its type;
     * today none do, so the two agree.
     */
    private const ROOM_TYPES = [
        ['slug' => 'double',     'name' => 'Double',      'base_price' => 1800.00, 'capacity' => 2],
        ['slug' => 'triple',     'name' => 'Triple',      'base_price' => 2400.00, 'capacity' => 3],
        ['slug' => 'family',     'name' => 'Family Room', 'base_price' => 2400.00, 'capacity' => 3],
        ['slug' => 'quadruple',  'name' => 'Quadruple',   'base_price' => 2800.00, 'capacity' => 4],
        ['slug' => 'deluxe',     'name' => 'Deluxe',      'base_price' => 3000.00, 'capacity' => 2],
        ['slug' => 'dormitory1', 'name' => 'Dormitory1',  'base_price' => 2500.00, 'capacity' => 5],
        ['slug' => 'dormitory2', 'name' => 'Dormitory2',  'base_price' => 3000.00, 'capacity' => 6],
    ];

    /**
     * [room_number, room_type, wing, price].
     *
     * Wings are the slugs in Room::WING_ORDER — the room boards group by these,
     * and anything outside that list still renders but sorts to the end.
     *
     * The numbering has gaps (no 111, no 113, most of the 200s missing). That
     * is the building as it stands, not a range with holes in it.
     *
     * `family` has no rooms behind it, which is deliberate and predates this
     * seeder: the type exists so staff can add rooms to it from Rooms → Add
     * Room, and until they do it correctly shows as fully booked.
     */
    private const ROOMS = [
        ['101', 'deluxe',     'rooster', 3000.00],
        ['102', 'deluxe',     'rooster', 3000.00],
        ['103', 'deluxe',     'rooster', 3000.00],
        ['104', 'deluxe',     'tumana',  3000.00],
        ['105', 'deluxe',     'tumana',  3000.00],
        ['106', 'deluxe',     'tumana',  3000.00],
        ['107', 'triple',     'tumana',  2400.00],
        ['108', 'triple',     'tumana',  2400.00],
        ['109', 'deluxe',     'tumana',  3000.00],
        ['110', 'double',     'rooster', 1800.00],
        ['112', 'double',     'rooster', 1800.00],
        ['202', 'quadruple',  'chev_re', 2800.00],
        ['203', 'dormitory2', 'chev_re', 3000.00],
        ['204', 'dormitory2', 'tumana',  3000.00],
        ['208', 'triple',     'torii',   2400.00],
        ['210', 'triple',     'torii',   2400.00],
        ['211', 'triple',     'torii',   2400.00],
        ['212', 'triple',     'torii',   2400.00],
        ['214', 'dormitory2', 'chev_re', 3000.00],
        ['215', 'dormitory1', 'chev_re', 2500.00],
        ['216', 'quadruple',  'torii',   2800.00],
        ['217', 'double',     'tumana',  1800.00],
    ];

    public function run(): void
    {
        foreach (self::ROOM_TYPES as $type) {
            RoomType::firstOrCreate(['slug' => $type['slug']], $type);
        }

        // Whoever the console will show as having last touched these. Nullable
        // with ON DELETE SET NULL, so an install with no master admin yet still
        // seeds cleanly and the rooms simply have no editor recorded.
        $seededBy = Staff::where('role', 'master_admin')->orderBy('id')->value('id');

        $created = 0;

        foreach (self::ROOMS as [$number, $type, $wing, $price]) {
            // Seeded 'available', never the status the room happens to hold in
            // the source system. Those are the leftovers of testing — carrying
            // a stale 'maintenance' into a fresh property would take a third of
            // the inventory off sale on day one, with nothing to explain why.
            $room = Room::firstOrCreate(
                ['room_number' => $number],
                [
                    'room_type'      => $type,
                    'wing'           => $wing,
                    'price'          => $price,
                    'status'         => 'available',
                    'last_edited_by' => $seededBy,
                ]
            );

            $created += (int) $room->wasRecentlyCreated;
        }

        $this->command?->info(sprintf(
            'Rooms: %d created, %d already present (%d total).',
            $created,
            count(self::ROOMS) - $created,
            count(self::ROOMS)
        ));
    }
}
