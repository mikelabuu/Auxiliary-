<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A second contact number on a booking.
 *
 * One number is one point of failure: the phone is off, out of battery, or in
 * a bag in the room the desk is trying to reach its owner about. A hostel
 * needs a second way to get hold of the party that booked, and it needs it
 * most on exactly the occasions nobody planned for.
 *
 * Nullable, and no unique/format constraint here — the shape is enforced by
 * the same rule `guest_phone` uses, in the two request validators, so bookings
 * taken before this column existed stay valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('guest_phone_alt', 255)->nullable()->after('guest_phone');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('guest_phone_alt');
        });
    }
};
