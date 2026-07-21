<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops bookings.room_numbers. The value is now derived from the reservations
 * rows (Booking::getRoomNumbersAttribute), so the stored CSV can no longer
 * drift from the authoritative per-room source — e.g. when a room is renumbered
 * (RoomController::update updated reservations but not this column).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'room_numbers')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('room_numbers');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('room_numbers')->nullable()->after('status');
        });

        // Backfill the CSV from reservations so the rollback is non-lossy.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE bookings b
                SET room_numbers = (
                    SELECT GROUP_CONCAT(r.room_number ORDER BY r.id SEPARATOR ',')
                    FROM reservations r
                    WHERE r.booking_id = b.id
                )
            ");
        }
    }
};
