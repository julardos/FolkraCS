<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Explicit fillable to avoid attribute helpers mismatch
class User extends Authenticatable
{
    /** @var array<string> */
    protected $fillable = ['name', 'email', 'password', 'client_id', 'tenant_id', 'role'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Hidden attributes for arrays
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * A user belongs to a client (existing concept) when applicable.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * A user belongs to a tenant (core multi-tenant record).
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
