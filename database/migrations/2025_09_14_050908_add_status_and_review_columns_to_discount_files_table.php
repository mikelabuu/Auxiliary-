<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_files', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('file_path'); // pending, approved, rejected
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            // Optional: if you want to enforce staff user FK
            // $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('discount_files', function (Blueprint $table) {
            if (Schema::hasColumn('discount_files', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('discount_files', 'reviewed_by')) {
                $table->dropColumn('reviewed_by');
            }
            if (Schema::hasColumn('discount_files', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
        });
    }
};
