<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('expected_guests')->after('room_numbers')->default(1);
            $table->decimal('discounted_price', 10, 2)->after('total_price')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('expected_guests');
            $table->dropColumn('discounted_price');
        });
    }
};
