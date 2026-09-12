<?php
namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelIncident;
use App\Models\SystemSetting;
use Illuminate\Console\Command;

class TrackChannelIncidents extends Command
{
    protected $signature = 'operations:track';

    protected $description = 'Abre y cierra incidentes de disponibilidad por canal';

    public function handle(): int
    {
        if (!SystemSetting::hasValue('operations.monitoring_started_at')) {
            SystemSetting::write('operations.monitoring_started_at', now()->toIso8601String());
        }

        $channels = Channel::query()
            ->where('published', true)
            ->where('astra_stream_id', '<>', '')
            ->get();

        $opened = 0;
        $closed = 0;

        foreach ($channels as $channel) {
            $open = ChannelIncident::query()
                ->where('channel_id', $channel->id)
                ->where('kind', 'offline')
                ->whereNull('ended_at')
                ->latest('started_at')
                ->first();

            if (!$channel->on_air) {
                if (!$open) {
                    $startedAt = $channel->offline_since ?: now();

                    ChannelIncident::create([
                        'channel_id' => $channel->id,
                        'kind' => 'offline',
                        'severity' => $startedAt->lte(now()->subMinutes(30)) ? 'critical' : 'warning',
                        'started_at' => $startedAt,
                        'details' => [
                            'astra_stream_id' => $channel->astra_stream_id,
                            'channel_number' => $channel->channel_number,
                            'outage_count' => (int) $channel->outage_count,
                        ],
                    ]);

                    $opened++;
                } else {
                    $open->update([
                        'severity' => $open->started_at && $open->started_at->lte(now()->subMinutes(30))
                            ? 'critical'
                            : 'warning',
                        'details' => array_merge($open->details ?? [], [
                            'outage_count' => (int) $channel->outage_count,
                        ]),
                    ]);
                }

                continue;
            }

            if ($open) {
                $endedAt = now();
                $seconds = $open->started_at
                    ? (int) round($open->started_at->diffInSeconds($endedAt))
                    : 0;

                $open->update([
                    'ended_at' => $endedAt,
                    'duration_seconds' => $seconds,
                    'details' => array_merge($open->details ?? [], [
                        'recovered_at' => $endedAt->toIso8601String(),
                    ]),
                ]);

                $closed++;
            }
        }

        $this->info(
            'Operacion IPTV OK | abiertos: ' . $opened
            . ' | cerrados: ' . $closed
            . ' | canales: ' . $channels->count()
        );

        return self::SUCCESS;
    }
}
