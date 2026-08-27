<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'tenant_id', 'slug', 'channels', 'name', 'business_type', 'status',
        'wa_base_url', 'wa_api_key', 'wa_session',
        'openrouter_api_key', 'openrouter_model', 'ai_instruction',
        'instagram_account_id', 'instagram_username',
        'instagram_access_token', 'instagram_token_expires_at',
    ];

    protected $casts = [
        'instagram_token_expires_at' => 'datetime',
    ];

    protected $hidden = ['wa_api_key', 'openrouter_api_key'];

    public function getMaskedWaKeyAttribute(): string
    {
        return $this->wa_api_key ? '••••' . substr($this->wa_api_key, -4) : '—';
    }

    public function getMaskedAiKeyAttribute(): string
    {
        return $this->openrouter_api_key ? '••••' . substr($this->openrouter_api_key, -4) : '—';
    }
}
