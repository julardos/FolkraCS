<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('tenants') || ! Schema::hasTable('domains')) {
            return;
        }

        $tenantIdCol = DB::selectOne("SHOW COLUMNS FROM tenants WHERE Field = 'id'");
        $domainTenantCol = DB::selectOne("SHOW COLUMNS FROM domains WHERE Field = 'tenant_id'");

        if (! $tenantIdCol || ! $domainTenantCol) {
            return;
        }

        $tenantIdType = $this->normalizeType((string) $tenantIdCol->Type);
        $domainTenantType = $this->normalizeType((string) $domainTenantCol->Type);

        $this->dropDomainTenantForeignKeyIfExists();

        // Align domains.tenant_id type with tenants.id type.
        if ($tenantIdType !== $domainTenantType) {
            if ($tenantIdType === 'bigint unsigned') {
                DB::statement("ALTER TABLE domains MODIFY tenant_id BIGINT UNSIGNED NOT NULL");
            } elseif (preg_match('/^char\((\d+)\)$/', $tenantIdType, $m)) {
                DB::statement("ALTER TABLE domains MODIFY tenant_id CHAR(" . (int) $m[1] . ") NOT NULL");
            } elseif (preg_match('/^varchar\((\d+)\)$/', $tenantIdType, $m)) {
                DB::statement("ALTER TABLE domains MODIFY tenant_id VARCHAR(" . (int) $m[1] . ") NOT NULL");
            } else {
                DB::statement("ALTER TABLE domains MODIFY tenant_id VARCHAR(255) NOT NULL");
            }
        }

        // Repair common corruption case where string tenant IDs became 0 in numeric columns.
        // Example: lkhm.folkra.co should map tenant_id -> lkhm.
        DB::statement("
            UPDATE domains d
            JOIN tenants t ON t.id = SUBSTRING_INDEX(d.domain, '.', 1)
            SET d.tenant_id = t.id
            WHERE d.tenant_id IN ('0', '')
        ");

        // Ensure no orphan domain records remain before FK recreation.
        $orphans = DB::selectOne("
            SELECT COUNT(*) AS cnt
            FROM domains d
            LEFT JOIN tenants t ON t.id = d.tenant_id
            WHERE t.id IS NULL
        ");

        if (($orphans->cnt ?? 0) > 0) {
            // Keep migration explicit: remove invalid rows so FK can be restored.
            DB::statement("
                DELETE d FROM domains d
                LEFT JOIN tenants t ON t.id = d.tenant_id
                WHERE t.id IS NULL
            ");
        }

        DB::statement("
            ALTER TABLE domains
            ADD CONSTRAINT domains_tenant_id_foreign
            FOREIGN KEY (tenant_id)
            REFERENCES tenants(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
        ");
    }

    public function down(): void
    {
        // No-op: schema rollback is intentionally not automatic for this data-repair migration.
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

    private function dropDomainTenantForeignKeyIfExists(): void
    {
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'domains'
              AND COLUMN_NAME = 'tenant_id'
              AND REFERENCED_TABLE_NAME = 'tenants'
            LIMIT 1
        ");

        if (! $fk || empty($fk->name)) {
            return;
        }

        DB::statement("ALTER TABLE domains DROP FOREIGN KEY `{$fk->name}`");
    }
};

