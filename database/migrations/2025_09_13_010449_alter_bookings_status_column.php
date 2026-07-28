<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite has no ENUM and cannot MODIFY a column, so this DDL is
        // MySQL-only — same guard the 2026_07_20 migrations use.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Update the enum definition
        DB::statement("ALTER TABLE bookings
            MODIFY COLUMN status ENUM(
                'pending_discount',
                'pending_payment',
                'paid',
                'confirmed',
                'active',
                'completed',
                'cancelled',
                'no_show'
            ) NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Rollback to old enum definition
        DB::statement("ALTER TABLE bookings
            MODIFY COLUMN status ENUM(
                'pending',
                'booked',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'");
    }
};
