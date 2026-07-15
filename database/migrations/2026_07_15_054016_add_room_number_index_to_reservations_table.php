<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reservations join to rooms by the room_number string everywhere
     * (stay context, occupancy lookups, availability guards), but the
     * column had no index — every lookup was a full table scan.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->index('room_number');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['room_number']);
        });
    }
};
