<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Subscriber;

class ActivityLogger
{
    public function log(
        string $action,
        ?Subscriber $subscriber = null,
        array $meta = [],
        ?string $actor = null,
        ?string $ip = null,
    ): void {
        ActivityLog::create([
            'subscriber_id' => $subscriber?->id,
            'action' => $action,
            'actor' => $actor ?: request()?->server('PHP_AUTH_USER') ?: 'system',
            'ip' => $ip ?: request()?->ip(),
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
