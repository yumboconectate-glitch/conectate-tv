<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertEvent extends Model
{
    protected $fillable = [
        'fingerprint',
        'type',
        'severity',
        'title',
        'message',
        'entity_type',
        'entity_id',
        'context',
        'status',
        'first_seen_at',
        'last_seen_at',
        'acknowledged_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
