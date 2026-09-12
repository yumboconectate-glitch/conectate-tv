<?php

namespace App\Http\Controllers;

use App\Models\AuthSession;
use App\Models\Channel;
use App\Models\Device;
use App\Models\Subscriber;
use App\Services\DeviceFingerprintService;
use Illuminate\Http\Request;
use Throwable;

class AstraAuthController extends Controller
{
    public function __invoke(Request $request, DeviceFingerprintService $fingerprints)
    {
        $token = trim((string) $request->query('token', ''));
        $channelStreamId = trim((string) $request->header('X-Channel-ID', ''));

        if ($token === '' || $channelStreamId === '') {
            return $this->deny('missing_credentials');
        }

        $subscriber = Subscriber::with('plan.channels')
            ->where('token', $token)
            ->first();

        if (!$subscriber || !$subscriber->canWatch()) {
            return $this->deny('subscriber_not_allowed');
        }

        $channel = $subscriber->plan->channels->first(
            fn (Channel $item) => $item->enabled
                && trim((string) $item->astra_stream_id) !== ''
                && $item->astra_stream_id === $channelStreamId
        );

        if (!$channel) {
            return $this->deny('channel_not_in_plan');
        }

        $ip = trim((string) $request->header('X-Real-IP', $request->ip()));
        $ua = trim((string) $request->header('X-Real-UA', $request->userAgent() ?? ''));
        $deviceKey = $fingerprints->key($subscriber->id, $ip, $ua);

        if ($this->ipIsBlocked($subscriber, $ip)) {
            return $this->deny('ip_blocked');
        }

        $knownDevice = Device::where('subscriber_id', $subscriber->id)
            ->where('device_key', $deviceKey)
            ->first();

        if ($knownDevice?->blocked) {
            return $this->deny('device_blocked');
        }

        if (!$knownDevice && !$this->withinDeviceLimit($subscriber)) {
            return $this->deny('device_limit');
        }

        // Astra no siempre envía X-Session-ID al backend. Si no viene,
        // correlacionamos por IP + User-Agent para no dejar un hueco en connlimit.
        $sessionId = trim((string) $request->header('X-Session-ID', ''));

        if (!$this->withinConnectionLimit($subscriber, $sessionId, $ip, $ua)) {
            return $this->deny('connection_limit');
        }

        $this->recordDevice($subscriber, $channel, $ip, $ua, $fingerprints);

        if ($sessionId !== '') {
            $this->recordSession($request, $subscriber, $channel, $sessionId, $ip, $ua);
        }

        return response('OK', 200, [
            'X-Session-Name' => $subscriber->username ?: $subscriber->name,
            'Cache-Control' => 'no-store',
        ]);
    }

    private function ipIsBlocked(Subscriber $subscriber, string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        return Device::where('subscriber_id', $subscriber->id)
            ->where('client_ip', $ip)
            ->where('ip_blocked', true)
            ->exists();
    }

    private function withinDeviceLimit(Subscriber $subscriber): bool
    {
        $limit = max(1, (int) ($subscriber->max_devices ?: 5));

        $known = Device::where('subscriber_id', $subscriber->id)
            ->where('blocked', false)
            ->distinct('device_key')
            ->count('device_key');

        return $known < $limit;
    }

    private function withinConnectionLimit(
        Subscriber $subscriber,
        string $sessionId,
        string $ip,
        string $ua
    ): bool {
        $ttl = max(60, (int) env('AUTH_SESSION_TTL_SECONDS', 150));

        AuthSession::where('active', true)
            ->where('last_seen_at', '<', now()->subSeconds($ttl))
            ->update(['active' => false, 'closed_at' => now()]);

        if ($sessionId !== '') {
            $alreadyKnown = AuthSession::where('astra_session_id', $sessionId)
                ->where('subscriber_id', $subscriber->id)
                ->where('active', true)
                ->exists();

            if ($alreadyKnown) {
                return true;
            }
        }

        // Permite refrescos del mismo dispositivo/sesión que Astra ya reportó.
        $samePlayback = AuthSession::where('subscriber_id', $subscriber->id)
            ->where('active', true)
            ->when($ip !== '', fn ($q) => $q->where('client_ip', $ip))
            ->when($ua !== '', fn ($q) => $q->where('user_agent', $ua))
            ->exists();

        if ($samePlayback) {
            return true;
        }

        $limit = max(1, (int) $subscriber->max_connections);
        $active = AuthSession::where('subscriber_id', $subscriber->id)
            ->where('active', true)
            ->count();

        return $active < $limit;
    }

    private function recordDevice(
        Subscriber $subscriber,
        Channel $channel,
        string $ip,
        string $ua,
        DeviceFingerprintService $fingerprints,
    ): void {
        try {
            $deviceKey = $fingerprints->key($subscriber->id, $ip, $ua);
            $device = Device::firstOrNew([
                'subscriber_id' => $subscriber->id,
                'device_key' => $deviceKey,
            ]);

            if (!$device->exists) {
                $device->first_seen_at = now();
                $device->request_count = 0;
            }

            $device->fill([
                'device_name' => $fingerprints->name($ua),
                'client_ip' => $ip !== '' ? $ip : null,
                'user_agent' => $ua !== '' ? $ua : null,
                'last_channel_id' => $channel->id,
                'last_seen_at' => now(),
            ]);

            $device->request_count = ((int) $device->request_count) + 1;
            $device->save();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function recordSession(
        Request $request,
        Subscriber $subscriber,
        Channel $channel,
        string $sessionId,
        string $ip,
        string $ua,
    ): void {
        try {
            $session = AuthSession::firstOrNew(['astra_session_id' => $sessionId]);

            if (!$session->exists) {
                $session->first_seen_at = now();
            }

            $session->fill([
                'subscriber_id' => $subscriber->id,
                'channel_id' => $channel->id,
                'client_ip' => $ip !== '' ? $ip : null,
                'user_agent' => $ua !== '' ? $ua : null,
                'request_path' => trim((string) $request->header('X-Real-Path', '')) ?: null,
                'request_host' => trim((string) $request->header('X-Real-Host', '')) ?: null,
                'active' => true,
                'last_seen_at' => now(),
                'closed_at' => null,
            ]);

            $session->save();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function deny(string $reason)
    {
        return response('Forbidden', 403, [
            'Cache-Control' => 'no-store',
            'X-Conectate-Deny' => $reason,
        ]);
    }
}
