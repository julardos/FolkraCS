<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'channel_wa', 'wa_number', 'channel_email', 'email', 'notify_on',
    ];

    protected $casts = [
        'channel_wa'    => 'boolean',
        'channel_email' => 'boolean',
        'notify_on'     => 'array',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'channel_wa'  => true,
            'channel_email' => false,
            'notify_on'   => ['complaint', 'question', 'escalation', 'schedule_change'],
        ]);
    }

    public function shouldNotifyFor(string $type): bool
    {
        return in_array($type, $this->notify_on ?? []);
    }
}
