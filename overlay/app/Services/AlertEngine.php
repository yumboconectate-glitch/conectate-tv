<?php
namespace App\Services;

use App\Models\AlertEvent;
use App\Models\AstraServer;
use App\Models\Channel;
use App\Models\EpgSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AlertEngine
{
    private array $seen = [];

    private array $managedTypes = [
        'astra_unreachable',
        'astra_error',
        'astra_cpu',
        'astra_memory',
        'channel_offline',
        'transport_errors',
        'epg_source_error',
        'epg_source_stale',
    ];

    public function __construct(
        private readonly AlertNotifier $notifier
    ) {
    }

    public function run(): array
    {
        $this->seen = [];

        $this->checkAstra();
        $this->checkChannels();
        $this->checkEpg();
        $resolved = $this->resolveClearedAlerts();

        return [
            'active' => AlertEvent::active()->count(),
            'critical' => AlertEvent::active()->where('severity', 'critical')->count(),
            'warning' => AlertEvent::active()->where('severity', 'warning')->count(),
            'resolved_now' => $resolved,
        ];
    }

    private function checkAstra(): void
    {
        $server = AstraServer::first();
        $staleMinutes = max(1, (int) config('conectate.alerts.astra_stale_minutes', 3));

        if (!$server) {
            $this->raise(
                'astra:none',
                'astra_unreachable',
                'critical',
                'Astra no registrado',
                'No existe un servidor Astra configurado en Conectate TV.',
                'astra_server',
                null
            );
            return;
        }

        $lastSeen = $server->last_seen_at ? Carbon::parse($server->last_seen_at) : null;

        if (!$lastSeen || $lastSeen->lt(now()->subMinutes($staleMinutes))) {
            $this->raise(
                'astra:stale:' . $server->id,
                'astra_unreachable',
                'critical',
                'Astra sin telemetria',
                $lastSeen
                    ? 'La ultima telemetria de Astra fue ' . $lastSeen->diffForHumans() . '.'
                    : 'Astra aun no tiene telemetria registrada.',
                'astra_server',
                $server->id,
                [
                    'host' => $server->host,
                    'port' => $server->port,
                    'last_seen_at' => $lastSeen?->toIso8601String(),
                ]
            );
        }

        if (trim((string) $server->last_error) !== '') {
            $this->raise(
                'astra:error:' . $server->id,
                'astra_error',
                'critical',
                'Astra reporta error',
                (string) $server->last_error,
                'astra_server',
                $server->id
            );
        }

        $status = $server->last_system_status;
        if (is_string($status)) {
            $status = json_decode($status, true) ?: [];
        }
        if (!is_array($status)) {
            $status = [];
        }

        $appCpu = (float) ($status['app_cpu_usage'] ?? 0);
        $sysCpu = (float) ($status['sys_cpu_usage'] ?? 0);
        $cpu = max($appCpu, $sysCpu);
        $cpuThreshold = (float) config('conectate.alerts.cpu_warning', 85);

        if ($cpu >= $cpuThreshold) {
            $this->raise(
                'astra:cpu:' . $server->id,
                'astra_cpu',
                $cpu >= 95 ? 'critical' : 'warning',
                'CPU alta en Astra',
                'CPU actual: ' . number_format($cpu, 1) . '%.',
                'astra_server',
                $server->id,
                ['app_cpu_usage' => $appCpu, 'sys_cpu_usage' => $sysCpu]
            );
        }

        $memory = (float) ($status['sys_mem_usage'] ?? 0);
        $memoryThreshold = (float) config('conectate.alerts.memory_warning', 85);

        if ($memory >= $memoryThreshold) {
            $this->raise(
                'astra:memory:' . $server->id,
                'astra_memory',
                $memory >= 95 ? 'critical' : 'warning',
                'RAM alta en servidor Astra',
                'Uso de RAM del servidor: ' . number_format($memory, 1) . '%.',
                'astra_server',
                $server->id,
                ['sys_mem_usage' => $memory]
            );
        }
    }

    private function checkChannels(): void
    {
        $offlineMinutes = max(1, (int) config('conectate.alerts.offline_minutes', 5));

        $offline = Channel::query()
            ->where('published', true)
            ->where('astra_stream_id', '<>', '')
            ->where('on_air', false)
            ->whereNotNull('offline_since')
            ->where('offline_since', '<=', now()->subMinutes($offlineMinutes))
            ->orderBy('offline_since')
            ->get();

        foreach ($offline as $channel) {
            $since = $channel->offline_since ? Carbon::parse($channel->offline_since) : null;
            $minutes = $since ? max(0, (int) $since->diffInMinutes(now())) : 0;

            $this->raise(
                'channel:offline:' . $channel->id,
                'channel_offline',
                $minutes >= 30 ? 'critical' : 'warning',
                'Canal fuera del aire: ' . $channel->publicName(),
                $since
                    ? 'Sin señal desde ' . $since->diffForHumans() . '.'
                    : 'Canal reportado fuera del aire.',
                'channel',
                $channel->id,
                [
                    'channel_number' => $channel->channel_number,
                    'astra_stream_id' => $channel->astra_stream_id,
                    'offline_since' => $since?->toIso8601String(),
                    'outage_count' => (int) $channel->outage_count,
                ]
            );
        }

        $errorChannels = Channel::query()
            ->where('published', true)
            ->where(function ($q) {
                $q->where('cc_errors', '>', 0)
                    ->orWhere('pes_errors', '>', 0)
                    ->orWhere('scrambling_errors', '>', 0);
            })
            ->orderByDesc('cc_errors')
            ->limit(40)
            ->get();

        $minimumChannels = max(1, (int) config('conectate.alerts.transport_channel_count', 5));

        if ($errorChannels->count() >= $minimumChannels) {
            $total = $errorChannels->sum(function ($c) {
                return (int) $c->cc_errors + (int) $c->pes_errors + (int) $c->scrambling_errors;
            });

            $top = $errorChannels->take(10)->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->publicName(),
                    'cc' => (int) $c->cc_errors,
                    'pes' => (int) $c->pes_errors,
                    'scrambling' => (int) $c->scrambling_errors,
                ];
            })->values()->all();

            $this->raise(
                'transport:aggregate',
                'transport_errors',
                $total >= 1000 ? 'critical' : 'warning',
                'Errores de transporte en la parrilla',
                $errorChannels->count() . ' canales reportan errores TS.',
                'channel_group',
                null,
                [
                    'channels_with_errors' => $errorChannels->count(),
                    'total_errors' => $total,
                    'top_channels' => $top,
                ]
            );
        }
    }

    private function checkEpg(): void
    {
        $sources = EpgSource::query()
            ->where('enabled', true)
            ->get();

        foreach ($sources as $source) {
            if (trim((string) $source->last_error) !== '') {
                $this->raise(
                    'epg:error:' . $source->id,
                    'epg_source_error',
                    'warning',
                    'Error en fuente EPG: ' . $source->name,
                    (string) $source->last_error,
                    'epg_source',
                    $source->id
                );
            }

            $lastSuccess = $source->last_success_at ? Carbon::parse($source->last_success_at) : null;
            $refreshHours = max(1, (int) ($source->refresh_hours ?: 6));
            $minimumStaleHours = max(1, (int) config('conectate.alerts.epg_min_stale_hours', 12));
            $staleHours = max($minimumStaleHours, $refreshHours * 2);

            if (!$lastSuccess || $lastSuccess->lt(now()->subHours($staleHours))) {
                $this->raise(
                    'epg:stale:' . $source->id,
                    'epg_source_stale',
                    'warning',
                    'EPG desactualizada: ' . $source->name,
                    $lastSuccess
                        ? 'Ultima importacion correcta ' . $lastSuccess->diffForHumans() . '.'
                        : 'La fuente nunca ha registrado una importacion correcta.',
                    'epg_source',
                    $source->id,
                    [
                        'refresh_hours' => $refreshHours,
                        'stale_after_hours' => $staleHours,
                        'last_success_at' => $lastSuccess?->toIso8601String(),
                    ]
                );
            }
        }
    }

    private function raise(
        string $fingerprint,
        string $type,
        string $severity,
        string $title,
        ?string $message = null,
        ?string $entityType = null,
        int|string|null $entityId = null,
        array $context = []
    ): void {
        $this->seen[$fingerprint] = true;

        $alert = AlertEvent::firstOrNew(['fingerprint' => $fingerprint]);
        $reactivated = $alert->exists && $alert->status === 'resolved';
        $newAlert = !$alert->exists;

        if ($newAlert || $reactivated) {
            $alert->first_seen_at = now();
            $alert->acknowledged_at = null;
        }

        $alert->fill([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'entity_type' => $entityType,
            'entity_id' => $entityId === null ? null : (string) $entityId,
            'context' => $context,
            'status' => 'active',
            'last_seen_at' => now(),
            'resolved_at' => null,
        ]);

        $alert->save();

        if ($newAlert || $reactivated) {
            $this->notifier->send('activated', $alert);
        }
    }

    private function resolveClearedAlerts(): int
    {
        $seen = array_keys($this->seen);

        $query = AlertEvent::query()
            ->where('status', 'active')
            ->whereIn('type', $this->managedTypes);

        if ($seen !== []) {
            $query->whereNotIn('fingerprint', $seen);
        }

        $alerts = $query->get();

        foreach ($alerts as $alert) {
            $alert->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'last_seen_at' => now(),
            ]);

            $this->notifier->send('resolved', $alert);
        }

        return $alerts->count();
    }
}
