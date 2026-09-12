<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\AstraSessionService;
use App\Services\SubscriberAstraService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class SubscriberApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => Subscriber::with('plan')
                ->orderBy('id')
                ->get()
                ->map(fn ($s) => $this->payload($s)),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function status(string $document)
    {
        $subscriber = Subscriber::with('plan')
            ->where('document', $document)
            ->first();

        if (!$subscriber) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->payload($subscriber),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function store(
        Request $request,
        SubscriberAstraService $astra,
        ActivityLogger $logger
    ) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'document' => ['required', 'string', 'max:80', 'unique:subscribers,document'],
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
            'document' => $data['document'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'plan_id' => $plan->id,
            'expires_at' => $data['expires_at'] ?? null,
            'expired_processed_at' => null,
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
        } catch (Throwable $e) {
            report($e);
        }

        $logger->log('api.subscriber.created', $subscriber, [
            'plan_id' => $subscriber->plan_id,
        ], 'api');

        return response()->json([
            'ok' => true,
            'data' => $this->payload($subscriber->fresh('plan')),
        ], 201, ['Cache-Control' => 'no-store']);
    }

    public function activate(
        string $document,
        SubscriberAstraService $astra,
        ActivityLogger $logger
    ) {
        $subscriber = $this->byDocument($document);
        $subscriber->update(['active' => true]);

        try {
            $astra->sync($subscriber->fresh());
        } catch (Throwable $e) {
            report($e);
        }

        $logger->log('api.subscriber.activated', $subscriber, [], 'api');

        return $this->ok($subscriber);
    }

    public function suspend(
        string $document,
        SubscriberAstraService $astra,
        AstraSessionService $sessions,
        ActivityLogger $logger
    ) {
        $subscriber = $this->byDocument($document);
        $subscriber->update(['active' => false]);

        foreach ($subscriber->authSessions()->where('active', true)->get() as $session) {
            try {
                $sessions->close($session->astra_session_id);
            } catch (Throwable $e) {
                report($e);
            }
        }

        try {
            $astra->sync($subscriber->fresh());
        } catch (Throwable $e) {
            report($e);
        }

        $logger->log('api.subscriber.suspended', $subscriber, [], 'api');

        return $this->ok($subscriber);
    }

    public function renew(
        Request $request,
        string $document,
        SubscriberAstraService $astra,
        ActivityLogger $logger
    ) {
        $data = $request->validate([
            'expires_at' => ['nullable', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        if (empty($data['expires_at']) && empty($data['days'])) {
            return response()->json([
                'ok' => false,
                'error' => 'expires_at_or_days_required',
            ], 422);
        }

        $subscriber = $this->byDocument($document);

        $expiresAt = !empty($data['expires_at'])
            ? \Illuminate\Support\Carbon::parse($data['expires_at'])
            : now()->addDays((int) $data['days']);

        $subscriber->update([
            'expires_at' => $expiresAt,
            'expired_processed_at' => null,
            'active' => true,
        ]);

        try {
            $astra->sync($subscriber->fresh());
        } catch (Throwable $e) {
            report($e);
        }

        $logger->log('api.subscriber.renewed', $subscriber, [
            'expires_at' => $expiresAt->toIso8601String(),
        ], 'api');

        return $this->ok($subscriber);
    }

    public function changePlan(
        Request $request,
        string $document,
        SubscriberAstraService $astra,
        ActivityLogger $logger
    ) {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $subscriber = $this->byDocument($document);
        $subscriber->update(['plan_id' => $data['plan_id']]);

        try {
            $astra->sync($subscriber->fresh());
        } catch (Throwable $e) {
            report($e);
        }

        $logger->log('api.subscriber.plan_changed', $subscriber, [
            'plan_id' => (int) $data['plan_id'],
        ], 'api');

        return $this->ok($subscriber);
    }

    private function byDocument(string $document): Subscriber
    {
        return Subscriber::with('plan')->where('document', $document)->firstOrFail();
    }

    private function ok(Subscriber $subscriber)
    {
        return response()->json([
            'ok' => true,
            'data' => $this->payload($subscriber->fresh('plan')),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    private function payload(Subscriber $s): array
    {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'document' => $s->document,
            'phone' => $s->phone,
            'plan' => $s->plan ? [
                'id' => $s->plan->id,
                'name' => $s->plan->name,
            ] : null,
            'status' => $s->statusLabel(),
            'can_watch' => $s->canWatch(),
            'expires_at' => $s->expires_at?->toIso8601String(),
            'max_connections' => (int) $s->max_connections,
            'max_devices' => (int) ($s->max_devices ?? 5),
            'active_connections' => $s->authSessions()->where('active', true)->count(),
            'devices' => $s->devices()->count(),
            'username' => $s->username,
        ];
    }
}
