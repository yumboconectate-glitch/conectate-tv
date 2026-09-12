<?php

namespace App\Services;

use App\Models\AstraServer;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AstraClient
{
    public function __construct(private readonly AstraServer $server) {}

    private function http(): PendingRequest
    {
        return Http::withBasicAuth($this->server->username, $this->server->password)
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 250);
    }

    private function baseUrl(): string
    {
        return sprintf('%s://%s:%d', $this->server->scheme, $this->server->host, $this->server->port);
    }

    public function systemStatus(): array
    {
        return $this->http()->get($this->baseUrl().'/api/system-status')->throw()->json() ?? [];
    }

    public function postControl(array $payload): array
    {
        return $this->http()
            ->withBody(json_encode($payload, JSON_THROW_ON_ERROR), 'application/json')
            ->post($this->baseUrl().'/control/')
            ->throw()
            ->json() ?? [];
    }

    public function control(string $command): array
    {
        return $this->postControl(['cmd' => $command]);
    }

    public function loadConfig(): array
    {
        return $this->control('load');
    }

    public function sessions(): array
    {
        return $this->control('sessions');
    }

    public function closeSession(string $sessionId): array
    {
        return $this->postControl([
            'cmd' => 'close-session',
            'id' => $sessionId,
        ]);
    }

    public function streamStatus(string $streamId): array
    {
        return $this->http()
            ->get($this->baseUrl().'/api/stream-status/'.rawurlencode($streamId))
            ->throw()
            ->json() ?? [];
    }

    public function setUser(string $login, array $user): array
    {
        return $this->postControl([
            'cmd' => 'set-user',
            'id' => $login,
            'user' => $user,
        ]);
    }

    public function getUser(string $login): array
    {
        return $this->postControl(['cmd' => 'get-user', 'id' => $login]);
    }

    public function removeUser(string $login): array
    {
        return $this->setUser($login, ['remove' => true]);
    }
}
