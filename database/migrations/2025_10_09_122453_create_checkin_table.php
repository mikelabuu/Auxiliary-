<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();

            // Link to booking
            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->onDelete('cascade');

            // Timestamp for check-in
            $table->timestamp('checked_in_at')->useCurrent();

            // Staff who processed the check-in
            $table->foreignId('processed_by')
                  ->nullable()
                  ->constrained('staff') // or 'staff' — depending on your table
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
