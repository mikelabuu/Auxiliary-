<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A staff-created booking has no guest account behind it.
 *
 * Both staff booking flows (admin Manual Booking, front desk Walk-In) insert
 * `user_id => null` — a walk-in guest never signed up. But the original
 * create_bookings_table declared user_id NOT NULL, so on any database actually
 * built from these migrations the insert dies with an integrity-constraint
 * violation and the desk sees "Failed to create booking".
 *
 * The live database had evidently been loosened by hand at some point, which
 * is why this never surfaced there. This brings the migrations back in line
 * with what the application has always written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows created at the front desk have no user to point at, so they must
        // go before the column can be NOT NULL again.
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
