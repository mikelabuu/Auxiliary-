<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only DDL; see 2026_07_20_000001 for the same guard.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Update the enum definition to include 'master_admin'
        DB::statement("ALTER TABLE staff MODIFY role ENUM('master_admin', 'admin', 'frontdesk', 'housekeeping') NOT NULL DEFAULT 'frontdesk'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Revert to the previous enum definition (without master_admin)
        DB::statement("ALTER TABLE staff MODIFY role ENUM('admin', 'frontdesk', 'housekeeping') NOT NULL DEFAULT 'frontdesk'");
    }
};
