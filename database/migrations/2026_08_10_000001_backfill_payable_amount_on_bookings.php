<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `payable_amount` was only ever written by the staff booking path and by the
 * discount flow. A guest booking that never asked for a discount was created
 * without it, so the column sat NULL on roughly half of all bookings.
 *
 * That went unnoticed because every path that spends the number reads
 * `payable_amount ?? total_price` — payments were always charged correctly.
 * The reporting side does not: ReportColumnMapper selects
 * `bookings.payable_amount` directly for the financial and combined report
 * sets, and the bookings export wrote the raw column, so those rows showed an
 * empty money cell — which reads as nothing owed rather than as missing data.
 *
 * BookingController::store now sets it at creation. This repairs the rows made
 * before that. `total_price - discount` is exactly what every reader already
 * computed, so no figure changes meaning; it is only written down.
 *
 * No down(): restoring NULLs would put the reports back the way they were, and
 * nothing distinguishes a row this filled in from one written correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->whereNull('payable_amount')
            ->update([
                'payable_amount' => DB::raw('total_price - COALESCE(discount, 0)'),
            ]);
    }

    public function down(): void
    {
        // Intentionally irreversible — see the note above.
    }
};
