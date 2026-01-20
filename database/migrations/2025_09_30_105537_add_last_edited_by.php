<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('last_edited_by')->nullable()->after('updated_at')
                  ->comment('Staff ID of last editor');
            $table->foreign('last_edited_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['last_edited_by']);
            $table->dropColumn('last_edited_by');
        });
    }
};
