<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name', 'business_type', 'status',
        'wa_base_url', 'wa_api_key', 'wa_session',
        'openrouter_api_key', 'openrouter_model', 'ai_instruction',
    ];

    protected $hidden = ['wa_api_key', 'openrouter_api_key'];

    protected $casts = [];

    public function getMaskedWaKeyAttribute(): string
    {
        return $this->wa_api_key ? '••••' . substr($this->wa_api_key, -4) : '—';
    }

    public function getMaskedAiKeyAttribute(): string
    {
        return $this->openrouter_api_key ? '••••' . substr($this->openrouter_api_key, -4) : '—';
    }
}
