<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // If tenant_id exists and is not varchar, replace it with a string column compatible with stancl tenants.id
        if (Schema::hasColumn('users', 'tenant_id')) {
            // Inspect column type
            $row = DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'tenant_id'");
            if ($row && stripos($row->Type, 'varchar') === false) {
                Schema::table('users', function (Blueprint $table) {
                    // drop incompatible column
                    $table->dropColumn('tenant_id');
                });
            }
        }

        if (! Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('tenant_id')->nullable()->after('client_id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            });
        } else {
            // ensure foreign key exists
            try {
                DB::statement("ALTER TABLE users ADD CONSTRAINT users_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL");
            } catch (\Exception $e) {
                // ignore if fk exists or incompatible
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete()->after('client_id');
            });
        }
    }
};
