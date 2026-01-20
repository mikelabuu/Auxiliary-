<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('booking_id');

            $table->string('payment_type')
                ->after('amount');

            $table->timestamp('paid_at')
                ->nullable()
                ->after('gateway');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'payment_type', 'paid_at']);
        });
    }
};