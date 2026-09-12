<?php
namespace App\Services;

use App\Models\ChannelIncident;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function metrics(Collection $channels): array
    {
        $now = now();
        $startedAt = $this->monitoringStartedAt($now);

        $ids = $channels->pluck('id')->filter()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $oldestWindow = $now->copy()->subDays(30);

        $incidents = ChannelIncident::query()
            ->whereIn('channel_id', $ids)
            ->where('kind', 'offline')
            ->where('started_at', '<=', $now)
            ->where(function ($q) use ($oldestWindow) {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $oldestWindow);
            })
            ->orderBy('started_at')
            ->get()
            ->groupBy('channel_id');

        $result = [];

        foreach ($channels as $channel) {
            $channelIncidents = $incidents->get($channel->id, collect());

            $sevenDayStart = $now->copy()->subDays(7);
            $effectiveSevenDayStart = $sevenDayStart->gt($startedAt)
                ? $sevenDayStart
                : $startedAt->copy();

            $result[$channel->id] = [
                'uptime_24h' => $this->calculateWindow($channelIncidents, $now, $startedAt, 1),
                'uptime_7d' => $this->calculateWindow($channelIncidents, $now, $startedAt, 7),
                'uptime_30d' => $this->calculateWindow($channelIncidents, $now, $startedAt, 30),
                'downtime_7d' => $this->downtimeSeconds($channelIncidents, $now, $effectiveSevenDayStart),
                'incidents_7d' => $channelIncidents
                    ->filter(fn ($i) => $i->started_at && $i->started_at->gte($effectiveSevenDayStart))
                    ->count(),
            ];
        }

        return $result;
    }

    public function forChannel(int $channelId): array
    {
        $channel = \App\Models\Channel::findOrFail($channelId);

        return $this->metrics(collect([$channel]))[$channelId];
    }

    private function calculateWindow(
        Collection $incidents,
        Carbon $now,
        Carbon $monitoringStartedAt,
        int $days
    ): array {
        $requestedStart = $now->copy()->subDays($days);
        $effectiveStart = $requestedStart->gt($monitoringStartedAt)
            ? $requestedStart
            : $monitoringStartedAt->copy();

        $observed = max(1, $effectiveStart->diffInSeconds($now));
        $downtime = $this->downtimeSeconds($incidents, $now, $effectiveStart);
        $uptime = max(0.0, min(100.0, (($observed - $downtime) / $observed) * 100));

        return [
            'percent' => round($uptime, 3),
            'downtime_seconds' => $downtime,
            'observed_seconds' => $observed,
            'effective_start' => $effectiveStart->toIso8601String(),
            'full_window' => $effectiveStart->lte($requestedStart),
        ];
    }

    private function downtimeSeconds(Collection $incidents, Carbon $now, Carbon $windowStart): int
    {
        $seconds = 0;

        foreach ($incidents as $incident) {
            if (!$incident->started_at) {
                continue;
            }

            $start = $incident->started_at->gt($windowStart)
                ? $incident->started_at->copy()
                : $windowStart->copy();

            $end = $incident->ended_at
                ? ($incident->ended_at->lt($now) ? $incident->ended_at->copy() : $now->copy())
                : $now->copy();

            if ($end->gt($start)) {
                $seconds += (int) round($start->diffInSeconds($end));
            }
        }

        return $seconds;
    }

    private function monitoringStartedAt(Carbon $now): Carbon
    {
        $value = SystemSetting::read('operations.monitoring_started_at');

        if (!$value) {
            return $now->copy();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $now->copy();
        }
    }
}
