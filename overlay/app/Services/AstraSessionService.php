<?php

namespace App\Services;

use App\Models\AstraServer;
use App\Models\AuthSession;
use App\Models\Channel;
use App\Models\Device;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class AstraSessionService
{
    public function __construct(
        private readonly DeviceFingerprintService $fingerprints,
    ) {}

    public function sync(): Collection
    {
        $server = AstraServer::first();

        if (!$server) {
            throw new RuntimeException('No existe un servidor Astra configurado.');
        }

        $client = new AstraClient($server);
        $payload = $client->sessions();
        $rows = collect($payload['sessions'] ?? []);
        $seen = [];

        foreach ($rows as $row) {
            $sessionId = trim((string) ($row['client_id'] ?? ''));
            if ($sessionId === '') {
                continue;
            }

            $seen[] = $sessionId;

            $channelStreamId = trim((string) ($row['channel_id'] ?? ''));
            $channel = $channelStreamId !== ''
                ? Channel::where('astra_stream_id', $channelStreamId)->first()
                : null;

            $login = trim((string) ($row['login'] ?? ''));
            $subscriber = $login !== ''
                ? Subscriber::where('astra_login', $login)->first()
                : null;

            $ip = trim((string) ($row['addr'] ?? ''));
            $ua = trim((string) ($row['ua'] ?? ''));
            $uptime = max(0, (int) ($row['uptime'] ?? 0));

            $existing = AuthSession::where('astra_session_id', $sessionId)->first();

            if (!$existing) {
                $existing = AuthSession::where('active', true)
                    ->when($subscriber, fn ($q) => $q->where('subscriber_id', $subscriber->id))
                    ->when($ip !== '', fn ($q) => $q->where('client_ip', $ip))
                    ->when($channel, fn ($q) => $q->where('channel_id', $channel->id))
                    ->where('last_seen_at', '>=', now()->subMinutes(3))
                    ->orderByDesc('last_seen_at')
                    ->first();

                if ($existing) {
                    $existing->astra_session_id = $sessionId;
                    $existing->save();
                }
            }

            $values = [
                'subscriber_id' => $existing?->subscriber_id ?: $subscriber?->id,
                'channel_id' => $existing?->channel_id ?: $channel?->id,
                'client_ip' => $existing?->client_ip ?: ($ip !== '' ? $ip : null),
                'user_agent' => $existing?->user_agent ?: ($ua !== '' ? $ua : null),
                'uptime' => $uptime,
                'active' => true,
                'last_seen_at' => now(),
                'closed_at' => null,
            ];

            if (!$existing) {
                $values['astra_session_id'] = $sessionId;
                $values['first_seen_at'] = now()->subSeconds($uptime);
                $existing = AuthSession::create($values);
            } else {
                $existing->update($values);
            }

            if ($subscriber) {
                $deviceKey = $this->fingerprints->key(
                    $subscriber->id,
                    $ip !== '' ? $ip : null,
                    $ua !== '' ? $ua : null,
                );

                $device = Device::firstOrNew([
                    'subscriber_id' => $subscriber->id,
                    'device_key' => $deviceKey,
                ]);

                if (!$device->exists) {
                    $device->first_seen_at = now()->subSeconds($uptime);
                    $device->request_count = 1;
                }

                $device->fill([
                    'device_name' => $this->fingerprints->name($ua),
                    'client_ip' => $ip !== '' ? $ip : null,
                    'user_agent' => $ua !== '' ? $ua : null,
                    'last_channel_id' => $channel?->id,
                    'last_seen_at' => now(),
                ]);

                $device->save();

                // Bloqueo inmediato: si el dispositivo o su IP están bloqueados,
                // cerramos la sesión en Astra aunque haya entrado por una reconexión.
                $ipBlocked = $ip !== '' && Device::where('subscriber_id', $subscriber->id)
                    ->where('client_ip', $ip)
                    ->where('ip_blocked', true)
                    ->exists();

                if ($device->blocked || $ipBlocked || !$subscriber->canWatch()) {
                    $this->closeQuietly($client, $sessionId);
                    $existing->update(['active' => false, 'closed_at' => now()]);
                }
            }
        }

        $query = AuthSession::where('active', true);
        if ($seen !== []) {
            $query->whereNotIn('astra_session_id', $seen);
        }

        $query->update([
            'active' => false,
            'closed_at' => now(),
        ]);

        $this->enforceConnectionLimits($client);

        return AuthSession::with(['subscriber.plan', 'channel'])
            ->where('active', true)
            ->orderByDesc('last_seen_at')
            ->get();
    }

    private function enforceConnectionLimits(AstraClient $client): void
    {
        $subscriberIds = AuthSession::where('active', true)
            ->whereNotNull('subscriber_id')
            ->distinct()
            ->pluck('subscriber_id');

        foreach ($subscriberIds as $subscriberId) {
            $subscriber = Subscriber::find($subscriberId);
            if (!$subscriber) {
                continue;
            }

            $limit = max(1, (int) $subscriber->max_connections);

            $sessions = AuthSession::where('subscriber_id', $subscriber->id)
                ->where('active', true)
                ->orderBy('first_seen_at')
                ->orderBy('id')
                ->get();

            // Conserva las primeras conexiones permitidas y cierra las extras.
            foreach ($sessions->slice($limit) as $extra) {
                $this->closeQuietly($client, $extra->astra_session_id);
                $extra->update(['active' => false, 'closed_at' => now()]);
            }
        }
    }

    private function closeQuietly(AstraClient $client, string $sessionId): void
    {
        try {
            $client->closeSession($sessionId);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function close(string $sessionId): void
    {
        $server = AstraServer::first();
        if (!$server) {
            throw new RuntimeException('No existe un servidor Astra configurado.');
        }

        (new AstraClient($server))->closeSession($sessionId);

        AuthSession::where('astra_session_id', $sessionId)->update([
            'active' => false,
            'closed_at' => now(),
        ]);
    }
}
