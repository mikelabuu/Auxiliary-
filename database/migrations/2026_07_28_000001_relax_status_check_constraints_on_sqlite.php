<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings non-MySQL drivers in line with the schema MySQL already has.
 *
 * bookings.status, rooms.status and staff.role started life as ENUM columns
 * and were widened repeatedly by raw ALTER TABLE ... MODIFY statements, all of
 * which are MySQL-only. 2026_07_20_000001 finished the job by converting them
 * to VARCHAR — also MySQL-only.
 *
 * On SQLite (which the test suite uses) none of that ever ran, so the columns
 * still carry the CHECK constraint Laravel generates for the *original* enum:
 * bookings.status only accepts 'pending', 'booked' or 'cancelled'. Any test
 * touching a booking with a modern status fails on a constraint violation
 * rather than on anything the test was actually asserting.
 *
 * MySQL is deliberately skipped — it reached this state four migrations ago.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('status', 30)->default('pending_payment')->change();
        });

        if (Schema::hasColumn('rooms', 'status')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('status', 20)->default('available')->change();
            });
        }

        if (Schema::hasColumn('staff', 'role')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->string('role', 20)->default('frontdesk')->change();
            });
        }
    }

    public function down(): void
    {
        // Nothing to undo: this only ever widens what a column accepts, and
        // restoring the original enum would reject rows written since.
    }
};
