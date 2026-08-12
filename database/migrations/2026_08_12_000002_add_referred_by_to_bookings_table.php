<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who sent this guest to us.
 *
 * Requested by the client: the auxiliary facilities take a lot of guests on
 * somebody's word — a department booking a visiting lecturer, an office
 * putting up a contractor — and the desk needs to know whose word it was.
 * "Ma'am Beth requested a mandatory field during booking to indicate if the
 * guest was referred by a specific office or person (especially for RSTC and
 * Guest House), as they need to know who is endorsing the guests."
 *
 * Nullable in the database on purpose, even though the public form asks for it.
 * Two reasons, and both are about not losing a booking over a text field:
 *
 *   · walk-ins are typed by staff at a counter with a queue behind them, and
 *     the desk already knows who walked in;
 *   · every booking that predates this column has no answer, and backfilling a
 *     guess ("Walk-in") into thousands of rows would be inventing a record of
 *     an endorsement nobody made.
 *
 * The public form enforces it; the column records what was actually said.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('referred_by')->nullable()->after('guest_phone_alt');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('referred_by');
        });
    }
};
