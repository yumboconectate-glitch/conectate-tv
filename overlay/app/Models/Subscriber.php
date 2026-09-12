<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriber extends Model
{
    protected $fillable = [
        'name', 'document', 'phone', 'email', 'astra_login', 'token',
        'username', 'access_password', 'plan_id', 'expires_at', 'expired_processed_at',
        'max_connections', 'max_devices', 'active', 'astra_synced_at',
        'astra_last_error', 'astra_detached_at',
    ];

    protected $hidden = ['token', 'access_password'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'expires_at' => 'datetime',
            'expired_processed_at' => 'datetime',
            'astra_synced_at' => 'datetime',
            'astra_detached_at' => 'datetime',
            'access_password' => 'encrypted',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function authSessions(): HasMany
    {
        return $this->hasMany(AuthSession::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function canWatch(): bool
    {
        return $this->active && !$this->isExpired() && (bool) $this->plan?->active;
    }

    public function statusLabel(): string
    {
        if (!$this->active) {
            return 'SUSPENDIDO';
        }

        if ($this->isExpired()) {
            return 'VENCIDO';
        }

        if (!$this->plan?->active) {
            return 'PLAN INACTIVO';
        }

        return 'ACTIVO';
    }

    public function statusClass(): string
    {
        return $this->canWatch() ? 'on' : 'off';
    }
}
