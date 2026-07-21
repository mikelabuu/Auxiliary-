<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts the ENUM status/role columns to plain VARCHAR.
 *
 * These enums were altered by hand three times already (pending_discount,
 * expired, master_admin...), each needing a raw ALTER TABLE ... MODIFY. The
 * set of valid values now lives in the models (Booking::STATUSES,
 * Room::SETTABLE_STATUSES, Staff::ROLES) and is enforced there, so adding a new
 * status becomes a code change instead of a schema migration. It also drops the
 * dead 'confirmed' value the enum still carried.
 *
 * bookings.status is indexed here — it is filtered on nearly every booking
 * query (scopeActive, scopePending, the expire/no-show/checkout commands).
 *
 * SQLite has no ENUM type and cannot MODIFY columns via ALTER, so the DDL is
 * guarded to MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE bookings MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending_payment'");
        DB::statement("ALTER TABLE rooms MODIFY status VARCHAR(20) NOT NULL DEFAULT 'available'");
        DB::statement("ALTER TABLE staff MODIFY role VARCHAR(20) NOT NULL DEFAULT 'frontdesk'");

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        // Restore the enum definitions as they stood before this migration.
        DB::statement("ALTER TABLE bookings MODIFY status ENUM(
            'pending_discount','pending_payment','paid','confirmed','active',
            'completed','cancelled','no_show','expired'
        ) NOT NULL DEFAULT 'pending_payment'");

        DB::statement("ALTER TABLE rooms MODIFY status ENUM(
            'available','occupied','maintenance','cleaning'
        ) NOT NULL DEFAULT 'available'");

        DB::statement("ALTER TABLE staff MODIFY role ENUM(
            'master_admin','admin','frontdesk','housekeeping'
        ) NOT NULL DEFAULT 'frontdesk'");
    }
};
