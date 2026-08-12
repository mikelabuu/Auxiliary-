<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Family Room (3 pax), added at the client's request.
 *
 * `room_types` is staff-editable from Room Types & Pricing, which is exactly
 * why this is written as an insert-if-absent rather than a plain insert: the
 * rate below is a starting figure, and once someone has changed it a re-run of
 * this migration must not quietly put it back.
 *
 * ₱2,400 matches the Triple, the only other three-person room on the property,
 * so the two do not contradict each other on day one. It is a placeholder for
 * a number nobody has given us yet — change it in the admin screen, not here.
 *
 * NOTE: this adds the *type*, not any rooms. A type with no rooms behind it is
 * correctly unsellable (calendarAvailability counts sellable rooms and finds
 * none), so the Family Room shows as fully booked until staff create rooms of
 * it under Rooms → Add Room.
 */
return new class extends Migration
{
    private const SLUG = 'family';

    public function up(): void
    {
        if (DB::table('room_types')->where('slug', self::SLUG)->exists()) {
            return;
        }

        DB::table('room_types')->insert([
            'slug'       => self::SLUG,
            'name'       => 'Family Room',
            'base_price' => 2400.00,
            'capacity'   => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Only if nothing is using it. Rooms carry `room_type` as a slug string
        // and reservations copy it, so removing a type that has been sold would
        // orphan live bookings' room_type — the column that tells the desk what
        // it actually gave the guest.
        $inUse = DB::table('rooms')->where('room_type', self::SLUG)->exists()
            || DB::table('reservations')->where('room_type', self::SLUG)->exists();

        if (! $inUse) {
            DB::table('room_types')->where('slug', self::SLUG)->delete();
        }
    }
};
