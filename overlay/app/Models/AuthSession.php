<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthSession extends Model
{
    protected $fillable = [
        'astra_session_id', 'subscriber_id', 'channel_id', 'client_ip',
        'user_agent', 'request_path', 'request_host', 'uptime',
        'active', 'first_seen_at', 'last_seen_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
