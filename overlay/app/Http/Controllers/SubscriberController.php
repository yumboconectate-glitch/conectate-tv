<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscriber;
use App\Services\SubscriberAstraService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class SubscriberController extends Controller
{
    public function index()
    {
        $subscribers = Subscriber::with(['plan', 'devices'])
            ->withCount([
                'authSessions as active_sessions_count' => fn ($q) => $q->where('active', true),
                'devices as devices_count',
            ])
            ->latest()
            ->get();

        $plans = Plan::where('active', true)->withCount('channels')->orderBy('name')->get();

        return view('subscribers', compact('subscribers', 'plans'));
    }

    public function show(Subscriber $subscriber)
    {
        $subscriber->load(['plan.channels', 'devices.lastChannel']);

        $activeSessions = $subscriber->authSessions()
            ->with('channel')
            ->where('active', true)
            ->orderByDesc('last_seen_at')
            ->get();

        $history = $subscriber->authSessions()
            ->with('channel')
            ->where('active', false)
            ->orderByDesc('closed_at')
            ->limit(100)
            ->get();

        return view('subscriber-show', compact('subscriber', 'activeSessions', 'history'));
    }

    public function store(Request $request, SubscriberAstraService $astra)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'document' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:160'],
            'plan_id' => ['required', 'exists:plans,id'],
            'expires_at' => ['nullable', 'date'],
            'max_connections' => ['nullable', 'integer', 'min:1', 'max:20'],
            'max_devices' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $middlewareMode = strtolower((string) env('ASTRA_AUTH_MODE', 'middleware')) === 'middleware';

        $subscriber = Subscriber::create([
            'name' => $data['name'],
            'document' => $data['document'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'plan_id' => $plan->id,
            'expires_at' => $data['expires_at'] ?? null,
            'max_connections' => ($data['max_connections'] ?? null) ?: $plan->max_connections,
            'max_devices' => ($data['max_devices'] ?? null) ?: 5,
            'active' => true,
            'astra_login' => 'ctv-'.Str::lower(Str::random(10)),
            'token' => Str::lower(Str::random(40)),
            'access_password' => Str::lower(Str::random(12)),
            'astra_detached_at' => $middlewareMode ? now() : null,
        ]);

        $subscriber->update([
            'username' => 'ctv'.str_pad((string) $subscriber->id, 6, '0', STR_PAD_LEFT),
        ]);

        try {
            $astra->sync($subscriber->fresh());
            return back()->with('ok', $middlewareMode
                ? 'Cliente creado. Conectate TV es la autoridad de acceso.'
                : 'Cliente creado y sincronizado con Astra.');
        } catch (Throwable $e) {
            return back()->with('warn', 'Cliente creado, pero Astra respondió con error: '.$e->getMessage());
        }
    }

    public function edit(Subscriber $subscriber)
    {
        $subscriber->load('plan');
        $plans = Plan::orderBy('name')->get();

        return view('subscriber-edit', compact('subscriber', 'plans'));
    }

    public function update(Request $request, Subscriber $subscriber, SubscriberAstraService $astra, ActivityLogger $logger)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'document' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:160'],
            'plan_id' => ['required', 'exists:plans,id'],
            'expires_at' => ['nullable', 'date'],
            'max_connections' => ['required', 'integer', 'min:1', 'max:20'],
            'max_devices' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $subscriber->update(array_merge($data, ['expired_processed_at' => null]));
        $logger->log('subscriber.updated', $subscriber, ['plan_id' => $subscriber->plan_id]);

        try {
            $astra->sync($subscriber->fresh());
            return redirect()->route('subscribers.show', $subscriber)->with('ok', 'Cliente actualizado.');
        } catch (Throwable $e) {
            return redirect()->route('subscribers.show', $subscriber)->with('warn', 'Cliente actualizado localmente; Astra: '.$e->getMessage());
        }
    }

    public function setState(Subscriber $subscriber, string $state, SubscriberAstraService $astra, ActivityLogger $logger)
    {
        abort_unless(in_array($state, ['activate', 'suspend'], true), 404);

        $subscriber->update(['active' => $state === 'activate']);
        $logger->log($state === 'activate' ? 'subscriber.activated' : 'subscriber.suspended', $subscriber);

        try {
            $astra->sync($subscriber->fresh());
            return back()->with('ok', $state === 'activate' ? 'Cliente activado.' : 'Cliente suspendido.');
        } catch (Throwable $e) {
            return back()->with('warn', 'Estado guardado localmente, pero Astra respondió: '.$e->getMessage());
        }
    }

    public function sync(Subscriber $subscriber, SubscriberAstraService $astra)
    {
        try {
            $astra->sync($subscriber->fresh());
            return back()->with('ok', 'Cliente resincronizado.');
        } catch (Throwable $e) {
            return back()->with('warn', 'No fue posible sincronizar: '.$e->getMessage());
        }
    }

    public function rotateCredentials(Subscriber $subscriber, SubscriberAstraService $astra, ActivityLogger $logger)
    {
        $subscriber->update([
            'token' => Str::lower(Str::random(40)),
            'access_password' => Str::lower(Str::random(12)),
        ]);
        $logger->log('subscriber.credentials_rotated', $subscriber);

        try {
            $astra->sync($subscriber->fresh());
            return back()->with('ok', 'Token y contraseña IPTV renovados. Las credenciales anteriores dejaron de ser válidas.');
        } catch (Throwable $e) {
            return back()->with('warn', 'Credenciales renovadas localmente; Astra: '.$e->getMessage());
        }
    }

    public function playlist(string $token)
    {
        $subscriber = Subscriber::with(['plan.channels' => fn ($q) => $q
            ->with(['category','epgSource'])
            ->where('enabled', true)
            ->where('published', true)
            ->whereNotNull('astra_stream_id')
            ->where('astra_stream_id', '<>', '')])
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($subscriber->canWatch(), 403, 'Suscripción inactiva o vencida.');

        $base = rtrim((string) env(
            'ASTRA_STREAM_PUBLIC_URL',
            env('ASTRA_STREAM_BASE_URL', 'http://10.0.14.2:8000')
        ), '/');

        $lines = ['#EXTM3U'];

        foreach ($subscriber->plan->channels
            ->sortBy(fn ($c) => sprintf('%08d-%s', $c->channel_number ?? 99999999, $c->publicName())) as $channel) {
            $name = str_replace(["\r", "\n"], ' ', $channel->publicName());
            $group = str_replace(["\r", "\n", '"'], ' ', $channel->category?->name ?: $subscriber->plan->name);
            $logo = str_replace('"', '', (string) $channel->logo_url);
            $number = $channel->channel_number ? ' tvg-chno="'.$channel->channel_number.'"' : '';
            $lines[] = '#EXTINF:-1 tvg-id="'.str_replace('"', '', $channel->epgId()).'" tvg-name="'.str_replace('"', '', $name).'" tvg-logo="'.$logo.'"'.$number.' group-title="'.$group.'",'.$name;
            $lines[] = $base.'/play/'.$channel->astra_stream_id.'/index.m3u8?token='.urlencode($subscriber->token);
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'audio/x-mpegurl; charset=utf-8',
            'Content-Disposition' => 'inline; filename="conectate-tv.m3u"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
