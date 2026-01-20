<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number')->unique();
            $table->string('file_path'); // storage path e.g. receipts/RCPT-20251008-ABC123.pdf
            $table->string('sha256_hash', 64);
            $table->foreignId('issued_by')->nullable(); // staff id
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('receipts');
    }
};

