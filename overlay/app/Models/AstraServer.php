<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AstraServer extends Model
{
    protected $fillable = [
        'name', 'scheme', 'host', 'port', 'username', 'password',
        'enabled', 'last_seen_at', 'last_system_status', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'enabled' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_system_status' => 'array',
        ];
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }
}
