<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL-only DDL; see 2026_07_20_000001 for the same guard.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Update enum to include "expired"
        DB::statement("
            ALTER TABLE bookings 
            MODIFY COLUMN status ENUM(
                'pending_discount',
                'pending_payment',
                'paid',
                'confirmed',
                'active',
                'completed',
                'cancelled',
                'no_show',
                'expired'
            ) NOT NULL DEFAULT 'pending_payment'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Rollback: remove "expired"
        DB::statement("
            ALTER TABLE bookings 
            MODIFY COLUMN status ENUM(
                'pending_discount',
                'pending_payment',
                'paid',
                'confirmed',
                'active',
                'completed',
                'cancelled',
                'no_show'
            ) NOT NULL DEFAULT 'pending_payment'
        ");
    }
};