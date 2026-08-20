<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $fillable = [
        'name', 'phone', 'phone_lid', 'push_name',
        'wa_session', 'is_human_takeover', 'takeover_agent_id',
    ];

    protected $casts = [
        'is_human_takeover' => 'boolean',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function activeConversation(): HasOne
    {
        return $this->hasOne(Conversation::class)->where('status', 'active')->latestOfMany();
    }

    public function takeoverAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'takeover_agent_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
