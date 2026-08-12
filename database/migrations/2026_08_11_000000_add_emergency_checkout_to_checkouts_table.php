<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes room for the emergency check-out.
 *
 * `method` was ENUM('auto','manual') — every check-out was either the 2 PM
 * sweep or the front desk pressing the button on the day a guest was due to
 * leave. An emergency check-out is neither: it ends a stay days before its
 * check-out date, so it needs its own value rather than passing as an ordinary
 * manual one. Widened to VARCHAR for the same reason bookings.status was in
 * 2026_07_20_000001 — the valid set belongs in the model, not in the schema.
 *
 * `reason` is the price of the exception. Cutting a paid-for stay short is the
 * one check-out someone will ask about later, so the desk records why at the
 * moment they do it and it rides along in the booking's timeline. Nullable
 * because the two ordinary methods have nothing to explain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->string('method', 20)->default('auto')->change();
            $table->string('reason', 255)->nullable()->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropColumn('reason');
        });

        // `method` is deliberately left as VARCHAR: narrowing it back to the
        // enum would reject every emergency check-out already recorded.
    }
};
