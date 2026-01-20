<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            // pending | success | failed | cancelled

            $table->string('reference_no')->unique();
            // internal unique reference for tracking

            $table->string('gateway')->default('landbank');
            // in case more gateways are added in future

            $table->json('gateway_response')->nullable();
            // store raw payload from gateway (or mock)

            // prepared but waiting: landbank fields
            $table->string('landbank_transaction_id')->nullable();
            $table->boolean('webhook_verified')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
