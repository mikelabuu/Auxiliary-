<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turn "endorsed by" into a reference person the desk can actually act on.
 *
 * `referred_by` (see 2026_08_12_000002) answered *who* endorsed the guest, and
 * that alone turned out to be half an answer. A name on its own — "Ma'am Beth",
 * "the RSTC office" — is not something the front desk can do anything with at
 * 9pm when a guest arrives claiming an arrangement nobody at the counter has
 * heard of. The two things they end up hunting for are the number to ring and
 * what the stay was endorsed *for*, and both were being crammed into the one
 * text box or left off entirely.
 *
 * So the field becomes three: the existing column keeps the name, and the
 * number and the purpose get columns of their own. Splitting them rather than
 * widening the string is the point — a phone number is only useful when it can
 * be dialled without a human first parsing it out of a sentence.
 *
 * All three stay nullable for the reasons the original migration gives: walk-ins
 * are typed at a counter with a queue behind them, and every booking that
 * predates these columns has no answer that would not be invented. The public
 * form is where the requirement is enforced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Held to the same shape as guest_phone at the form, but stored as
            // a plain string: the desk also takes landlines and office
            // extensions for a referrer, which the mobile pattern would reject.
            $table->string('referred_by_phone', 30)->nullable()->after('referred_by');

            // Why the stay was endorsed — "OJT deployment", "resource speaker
            // for the seminar", "contractor for the water works". Long enough
            // for a sentence, because that is what it is.
            $table->string('referred_by_purpose', 255)->nullable()->after('referred_by_phone');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['referred_by_phone', 'referred_by_purpose']);
        });
    }
};
