<?php
namespace App\Services;

use App\Models\AlertEvent;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlertNotifier
{
    public function __construct(
        private readonly TelegramSettings $telegram
    ) {
    }

    public function send(string $event, AlertEvent $alert): void
    {
        $this->sendWebhook($event, $alert);
        $this->sendTelegram($event, $alert);
    }

    private function sendWebhook(string $event, AlertEvent $alert): void
    {
        $url = trim((string) config('conectate.alerts.webhook_url', ''));

        if ($url === '') {
            return;
        }

        try {
            Http::timeout(5)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'event' => $event,
                    'source' => 'conectate-tv',
                    'version' => config('conectate.version', '0.8.0'),
                    'alert' => [
                        'id' => $alert->id,
                        'fingerprint' => $alert->fingerprint,
                        'type' => $alert->type,
                        'severity' => $alert->severity,
                        'title' => $alert->title,
                        'message' => $alert->message,
                        'entity_type' => $alert->entity_type,
                        'entity_id' => $alert->entity_id,
                        'status' => $alert->status,
                        'context' => $alert->context,
                        'first_seen_at' => optional($alert->first_seen_at)?->toIso8601String(),
                        'last_seen_at' => optional($alert->last_seen_at)?->toIso8601String(),
                        'resolved_at' => optional($alert->resolved_at)?->toIso8601String(),
                    ],
                ])
                ->throw();
        } catch (Throwable $e) {
            Log::warning('Conectate TV alert webhook failed', [
                'event' => $event,
                'fingerprint' => $alert->fingerprint,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendTelegram(string $event, AlertEvent $alert): void
    {
        try {
            if (!SystemSetting::readBool('telegram.enabled', false)) {
                return;
            }

            $prefix = $event === 'resolved' ? 'RESUELTA' : 'ALERTA';
            $text = '[' . $prefix . '] ' . strtoupper($alert->severity)
                . "\n" . $alert->title;

            if ($alert->message) {
                $text .= "\n" . $alert->message;
            }

            $this->telegram->send($text);
        } catch (Throwable $e) {
            Log::warning('Conectate TV Telegram alert failed', [
                'event' => $event,
                'fingerprint' => $alert->fingerprint,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
