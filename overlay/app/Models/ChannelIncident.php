<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelIncident extends Model
{
    protected $fillable = [
        'channel_id',
        'kind',
        'severity',
        'started_at',
        'ended_at',
        'duration_seconds',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'details' => 'array',
            'duration_seconds' => 'integer',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }
}
