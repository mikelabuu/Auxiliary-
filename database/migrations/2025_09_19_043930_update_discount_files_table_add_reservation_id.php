<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_files', function (Blueprint $table) {
            $table->foreignId('reservation_id')
                ->nullable()
                ->after('discount_id')
                ->constrained('reservations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('discount_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_id');
        });
    }
};
