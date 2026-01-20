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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Reference to staff who performed the action
            $table->foreignId('staff_id')->nullable()
                  ->constrained('staff')
                  ->onDelete('set null');

            // Role of the actor (useful if you later add admins/supervisors)
            $table->string('role', 50)->default('staff');

            // Core audit data
            $table->string('action', 100);          // e.g., "approved_discount", "deleted_room"
            $table->string('target_type', 100);     // e.g., "Booking", "Room"
            $table->unsignedBigInteger('target_id')->nullable(); // ID of affected record

            // Change tracking (before/after values)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Optional details
            $table->text('description')->nullable(); // Human-readable description
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            $table->text('user_agent')->nullable(); // Device/browser info

            // Indexes for performance
            $table->index(['action']);
            $table->index(['target_type', 'target_id']);
            $table->index(['staff_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
