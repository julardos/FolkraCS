<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Update existing values before changing enum
        DB::statement("UPDATE users SET role = 'landlord' WHERE role = 'admin' AND tenant_id IS NULL");
        DB::statement("UPDATE users SET role = 'admin'   WHERE role = 'admin' AND tenant_id IS NOT NULL");
        DB::statement("UPDATE users SET role = 'admin'   WHERE role = 'agent'");

        DB::statement("ALTER TABLE users MODIFY role ENUM('landlord', 'admin') NOT NULL DEFAULT 'admin'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'landlord'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'agent') NOT NULL DEFAULT 'agent'");
    }
};
