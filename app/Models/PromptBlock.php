<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptBlock extends Model
{
    protected $fillable = ['key', 'label', 'content', 'is_enabled', 'sort_order', 'is_required'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)->orderBy('sort_order');
    }
}
