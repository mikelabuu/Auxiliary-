<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes reviewer/processor foreign keys that pointed at the wrong table (or at
 * no table at all). These columns are all written with staff ids in the app:
 *
 *   - discounts.reviewed_by      -> was constrained to `users` (wrong); staff id is stored
 *   - discount_files.reviewed_by -> had no FK; staff id is stored
 *   - checkouts.processed_by     -> had no FK; staff id (or null) is stored
 *
 * SQLite cannot add/drop foreign keys on an existing table via ALTER, so the
 * DDL is guarded to MySQL. Orphaned values (an id that is not a real staff row)
 * are nulled first so the new constraint can be applied cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // discounts.reviewed_by: repoint from users -> staff
        $this->nullOrphanedStaffRefs('discounts', 'reviewed_by');
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });
        Schema::table('discounts', function (Blueprint $table) {
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
        });

        // discount_files.reviewed_by: add missing FK -> staff
        $this->nullOrphanedStaffRefs('discount_files', 'reviewed_by');
        Schema::table('discount_files', function (Blueprint $table) {
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
        });

        // checkouts.processed_by: add missing FK -> staff
        $this->nullOrphanedStaffRefs('checkouts', 'processed_by');
        Schema::table('checkouts', function (Blueprint $table) {
            $table->foreign('processed_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
        });

        Schema::table('discount_files', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });

        // Restore discounts.reviewed_by FK to its original (incorrect) users target.
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });
        Schema::table('discounts', function (Blueprint $table) {
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Null any value in $column that does not reference an existing staff row,
     * so a staff FK can be added without violating referential integrity.
     */
    private function nullOrphanedStaffRefs(string $table, string $column): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, function ($query) {
                $query->select('id')->from('staff');
            })
            ->update([$column => null]);
    }
};
