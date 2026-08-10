<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registration has always asked for a first name, a middle initial and a last
 * name, joined them into "Last, First, M" — and then handed the result to
 * User::create(), where mass-assignment silently dropped it, because no such
 * column existed. Every guest account was created with only a username and an
 * email, and the three fields the form insisted on went nowhere.
 *
 * Nullable, because that is the truth about every row created before this.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
};
