<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'subscriber_id', 'device_key', 'device_name', 'custom_name',
        'blocked', 'ip_blocked', 'blocked_at', 'block_reason',
        'client_ip', 'user_agent', 'last_channel_id', 'first_seen_at',
        'last_seen_at', 'request_count',
    ];

    protected function casts(): array
    {
        return [
            'blocked' => 'boolean',
            'ip_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function lastChannel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'last_channel_id');
    }

    public function displayName(): string
    {
        return trim((string) $this->custom_name) !== ''
            ? $this->custom_name
            : $this->device_name;
    }
}
