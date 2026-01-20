<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->string('otp_code');
            $table->timestamp('otp_expires_at');
            $table->timestamp('used_at')->nullable(); // mark OTP as consumed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_otps');
    }
};