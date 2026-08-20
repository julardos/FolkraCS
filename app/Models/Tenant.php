<?php

namespace App\Models;

use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasDomains;

    public function users()
    {
        return $this->hasMany(User::class, 'tenant_id', 'id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'tenant_id', 'id');
    }
}
