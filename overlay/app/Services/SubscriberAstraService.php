<?php

namespace App\Services;

use App\Models\AstraServer;
use App\Models\Subscriber;
use RuntimeException;
use Throwable;

class SubscriberAstraService
{
    public function sync(Subscriber $subscriber): void
    {
        $mode = strtolower((string) env('ASTRA_AUTH_MODE', 'middleware'));

        if ($mode === 'middleware' && $subscriber->astra_detached_at) {
            $subscriber->update([
                'astra_synced_at' => now(),
                'astra_last_error' => null,
            ]);
            return;
        }

        $server = AstraServer::first();

        if (!$server) {
            throw new RuntimeException('No existe un servidor Astra configurado.');
        }

        $client = new AstraClient($server);

        $payload = [
            'enable' => $subscriber->canWatch(),
            'type' => 3,
            'comment' => 'Conectate TV · '.$subscriber->name,
            'token' => $subscriber->token,
            'expire' => $subscriber->expires_at?->timestamp ?? 0,
            'connlimit' => max(1, (int) $subscriber->max_connections),
        ];

        try {
            $response = $client->setUser($subscriber->astra_login, $payload);

            if (($response['set-user'] ?? null) !== 'ok') {
                throw new RuntimeException('Astra no confirmó set-user: '.json_encode($response));
            }

            $subscriber->update([
                'astra_synced_at' => now(),
                'astra_last_error' => null,
            ]);
        } catch (Throwable $e) {
            $subscriber->update(['astra_last_error' => $e->getMessage()]);
            throw $e;
        }
    }
}
