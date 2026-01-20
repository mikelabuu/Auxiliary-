<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id(); // int(10) unsigned, auto_increment

            $table->unsignedBigInteger('user_id'); // bigint(20) unsigned
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('room_numbers', 255)->nullable(); // varchar(255)
            $table->string('guest_name', 255);
            $table->string('guest_address', 255);
            $table->string('guest_phone', 255);

            $table->date('check_in');
            $table->date('check_out');

            $table->decimal('discount', 10, 2)->nullable()->default(0.00);
            $table->decimal('total_price', 10, 2);

            $table->enum('status', ['pending', 'booked', 'cancelled'])
                  ->default('pending');

            $table->integer('num_seniors')->nullable()->default(0);
            $table->string('meal', 255)->nullable();

            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
