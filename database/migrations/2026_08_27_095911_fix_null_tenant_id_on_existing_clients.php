<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Clients created before tenant_id/slug were in $fillable had those fields
        // silently dropped and stored as null. Recover the correct value by joining
        // through users — users always had tenant_id set correctly by TenantController.
        DB::statement("
            UPDATE clients c
            INNER JOIN users u ON u.client_id = c.id
            SET c.tenant_id = u.tenant_id,
                c.slug      = u.tenant_id
            WHERE c.tenant_id IS NULL
              AND u.tenant_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Not reversible — restoring null tenant_id would break all tenant lookups.
    }
};
