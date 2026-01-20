<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('room_number');
            $table->string('room_type'); // redundancy for faster lookup
            $table->unsignedInteger('capacity'); // cache capacity per room
            $table->unsignedInteger('num_seniors')->default(0); // seniors in this specific room
            $table->decimal('price', 10, 2); // total price for this room * nights
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
