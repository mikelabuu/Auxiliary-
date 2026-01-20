<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add processed_by to expiry_logs
        Schema::table('expiry_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('processed_by')->nullable()->after('expired_at');
            $table->foreign('processed_by')
                  ->references('id')
                  ->on('staff')
                  ->onDelete('set null');
        });

        // Add processed_by to no_show_logs
        Schema::table('no_show_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('processed_by')->nullable()->after('marked_at');
            $table->foreign('processed_by')
                  ->references('id')
                  ->on('staff')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Remove columns on rollback
        Schema::table('expiry_logs', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->dropColumn('processed_by');
        });

        Schema::table('no_show_logs', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->dropColumn('processed_by');
        });
    }
};
