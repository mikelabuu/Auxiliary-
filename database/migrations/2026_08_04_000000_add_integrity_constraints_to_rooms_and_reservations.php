<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two invariants the application has always assumed and the schema never
 * enforced.
 *
 * 1. A room number identifies exactly one room.
 *
 *    Every lookup in the app is `Room::where('room_number', $n)->first()` —
 *    the availability endpoints, both booking flows, the room board. And
 *    `reservations.room_number` is a plain string with no foreign key, so the
 *    join that decides whether a room is free is a string match against a
 *    column that only carried a non-unique KEY. One duplicated room number and
 *    `first()` silently picks a room at random, while reservations attach to
 *    both. Nothing would have reported an error.
 *
 * 2. A booking holds a given room once.
 *
 *    Both store() paths reject duplicate room numbers in the submitted blocks,
 *    but that is a loop over request data, not a guarantee. This is the same
 *    rule where it cannot be skipped.
 *
 * Neither constraint can be added over data that already violates it, so both
 * are checked first and refuse with something a person can act on rather than
 * a driver-level duplicate-key error.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->guardAgainst(
            DB::table('rooms')
                ->select('room_number')
                ->groupBy('room_number')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('room_number'),
            'rooms.room_number is not unique yet. Merge or renumber these rooms first: '
        );

        $this->guardAgainst(
            // Formatted in PHP, not by the database. CONCAT() does not exist
            // in SQLite, and the test suite migrates against SQLite — a
            // migration that only runs on MySQL fails every test that touches
            // the schema, which is most of them.
            DB::table('reservations')
                ->select('booking_id', 'room_number')
                ->groupBy('booking_id', 'room_number')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->map(fn ($row) => $row->booking_id . '/' . $row->room_number),
            'reservations holds the same room twice on one booking. Remove the duplicate rows first: '
        );

        Schema::table('rooms', function (Blueprint $table) {
            // The pre-existing non-unique `idx_rooms_number` is left in place:
            // it is redundant beside this, but dropping an index by name
            // behaves differently across MySQL and the SQLite the tests run
            // on, and a failed migration costs more than a spare index on a
            // table of a few dozen rows.
            $table->unique('room_number', 'rooms_room_number_unique');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['booking_id', 'room_number'], 'reservations_booking_room_unique');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique('rooms_room_number_unique');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_booking_room_unique');
        });
    }

    /**
     * Refuse the migration with the offending values named, rather than
     * letting the database raise a duplicate-key error that says only that
     * one exists.
     */
    private function guardAgainst(\Illuminate\Support\Collection $offenders, string $message): void
    {
        if ($offenders->isNotEmpty()) {
            throw new RuntimeException($message . $offenders->implode(', '));
        }
    }
};
