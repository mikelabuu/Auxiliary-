<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes bookings.user_id nullable.
 *
 * Walk-in and manual bookings are created for guests who have no account —
 * both WalkInBookingController::store() and ManualBookingController::store()
 * write `'user_id' => null` explicitly. The original create_bookings_table
 * migration declared the column NOT NULL, so on any database built purely
 * from migrations both of those flows fail with
 *
 *     SQLSTATE[23000]: Column 'user_id' cannot be null
 *
 * which the controllers swallow into a generic "Failed to create booking"
 * flash message.
 *
 * The development database was altered by hand at some point and is already
 * nullable, which is why this went unnoticed — but the change was never
 * recorded, so the migrations did not reproduce a working schema. Running this
 * against the existing dev database is a no-op; it repairs every database
 * built from scratch (CI, a new machine, production).
 *
 * The CASCADE foreign key is dropped and restored around the change so the
 * behaviour for registered guests is preserved exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if ($this->isNullable()) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE bookings MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Rolling back cannot succeed while walk-in bookings exist, since they
        // are precisely the rows with a null user_id. Clear them first so the
        // NOT NULL constraint can be restored.
        DB::table('bookings')->whereNull('user_id')->delete();

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE bookings MODIFY user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    private function isNullable(): bool
    {
        $column = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['bookings', 'user_id'],
        );

        return $column !== null && strtoupper($column->IS_NULLABLE) === 'YES';
    }
};
