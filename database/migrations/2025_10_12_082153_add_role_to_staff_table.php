<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Adding enum role column
            $table->enum('role', ['admin', 'frontdesk', 'housekeeping'])
                  ->default('frontdesk')
                  ->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
