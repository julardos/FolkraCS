<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantIdColumn = DB::selectOne("SHOW COLUMNS FROM tenants WHERE Field = 'id'");
        $tenantIdType = $this->normalizeType((string) ($tenantIdColumn->Type ?? 'varchar(255)'));

        $userTenantColumn = DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'tenant_id'");
        $userTenantType = $userTenantColumn ? $this->normalizeType((string) $userTenantColumn->Type) : null;

        // If tenant_id exists but doesn't match tenants.id type, recreate it.
        if ($userTenantType !== null && $userTenantType !== $tenantIdType) {
            $this->dropTenantForeignKeyIfExists();
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
            $userTenantType = null;
        }

        if ($userTenantType === null) {
            Schema::table('users', function (Blueprint $table) use ($tenantIdType) {
                if ($tenantIdType === 'bigint unsigned') {
                    $table->unsignedBigInteger('tenant_id')->nullable();
                } elseif (preg_match('/^char\((\d+)\)$/', $tenantIdType, $matches)) {
                    $table->char('tenant_id', (int) $matches[1])->nullable();
                } elseif (preg_match('/^varchar\((\d+)\)$/', $tenantIdType, $matches)) {
                    $table->string('tenant_id', (int) $matches[1])->nullable();
                } else {
                    $table->string('tenant_id')->nullable();
                }
            });
        }

        if (! $this->tenantForeignKeyExists()) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'tenant_id')) {
            return;
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
        } catch (\Throwable $e) {
            // Ignore missing constraint in rollback.
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        if (str_contains($type, 'bigint') && str_contains($type, 'unsigned')) {
            return 'bigint unsigned';
        }

        if (preg_match('/^char\(\d+\)$/', $type)) {
            return $type;
        }

        if (preg_match('/^varchar\(\d+\)$/', $type)) {
            return $type;
        }

        return $type;
    }

    private function tenantForeignKeyExists(): bool
    {
        $result = DB::selectOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'users'
               AND COLUMN_NAME = 'tenant_id'
               AND REFERENCED_TABLE_NAME = 'tenants'
             LIMIT 1"
        );

        return $result !== null;
    }

    private function dropTenantForeignKeyIfExists(): void
    {
        if (! $this->tenantForeignKeyExists()) {
            return;
        }

        try {
            DB::statement("ALTER TABLE users DROP FOREIGN KEY users_tenant_id_foreign");
        } catch (\Throwable $e) {
            // If constraint name differs, fallback to schema helper.
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['tenant_id']);
                });
            } catch (\Throwable $ignored) {
            }
        }
    }
};
