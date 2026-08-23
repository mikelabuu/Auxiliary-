<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `idx_bookings_availability (check_in, check_out, status)`.
 *
 * The index already existed on the development database — it was created by
 * hand and never written down, so `migrate:fresh`, a new deploy and the CI
 * SQLite run all built a schema without it. Schema drift, and the silent kind:
 * everything works, one machine is just faster than the others.
 *
 * The column order is the one the availability queries actually filter in.
 * Every overlap check is `check_in < :out AND check_out > :in`, narrowed by
 * status afterwards — see BookingController::store's double-booking guard,
 * Booking::applyActiveHold, RoomBoard and the front-desk arrivals board.
 * `bookings_status_index` stays: status alone is still the whole predicate for
 * the lifecycle commands (bookings:expire, :mark-no-show, :autocheckout).
 *
 * This index was unusable by the app until the same change that added this
 * migration. Every one of those queries wrapped the column as
 * `date(check_in)`, which is not sargable — MySQL declined this index even
 * under FORCE INDEX (`key=NULL, type=ALL`). Booking's setCheckInAttribute /
 * setCheckOutAttribute now normalise the stored value to a bare `Y-m-d` on
 * every driver, so those calls became plain comparisons and the index is
 * reachable (`type=range, Using index condition`).
 *
 * Guarded on both ends: the dev database already has it under this exact name,
 * and re-adding it would abort the migration.
 */
return new class extends Migration
{
    private const NAME = 'idx_bookings_availability';

    public function up(): void
    {
        if ($this->indexExists()) {
            return;
        }

        Schema::table('bookings', function ($table) {
            $table->index(['check_in', 'check_out', 'status'], self::NAME);
        });
    }

    public function down(): void
    {
        if (! $this->indexExists()) {
            return;
        }

        Schema::table('bookings', function ($table) {
            $table->dropIndex(self::NAME);
        });
    }

    /**
     * Schema::hasIndex() only landed in Laravel 11.15 for some drivers and
     * still reports inconsistently on SQLite, so ask the connection directly.
     */
    private function indexExists(): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return DB::table('sqlite_master')
                ->where('type', 'index')
                ->where('name', self::NAME)
                ->exists();
        }

        return collect(DB::select('SHOW INDEX FROM bookings'))
            ->contains(fn ($row) => $row->Key_name === self::NAME);
    }
};
