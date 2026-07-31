<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three things the checkout never asked and the front desk had no way to know.
 *
 * - arrival_time: check-in opens at 2:00 PM, but a guest landing at 11 PM and
 *   one landing at 2:15 PM are very different evenings for a 24/7 desk. Kept
 *   nullable: "not sure yet" is an honest answer and better than a guess.
 * - special_requests: ground floor, late arrival, allergy, travelling with an
 *   elderly parent. These were being phoned in, or not said at all.
 * - accepted_terms_at: a timestamp rather than a boolean, so a disputed
 *   booking can show *when* the policy was agreed to, not just that a box was
 *   ticked at some unknown point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->time('arrival_time')->nullable()->after('check_out');
            $table->text('special_requests')->nullable()->after('arrival_time');
            $table->timestamp('accepted_terms_at')->nullable()->after('special_requests');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['arrival_time', 'special_requests', 'accepted_terms_at']);
        });
    }
};
