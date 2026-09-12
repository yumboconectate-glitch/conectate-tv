<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Services\ActivityLogger;
use App\Services\AstraSessionService;
use Illuminate\Console\Command;
use Throwable;

class SubscribersExpire extends Command
{
    protected $signature = 'subscribers:expire';
    protected $description = 'Cierra sesiones de abonados vencidos y registra el vencimiento';

    public function handle(
        AstraSessionService $sessions,
        ActivityLogger $logger
    ): int {
        $expired = Subscriber::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('expired_processed_at')
            ->get();

        foreach ($expired as $subscriber) {
            foreach ($subscriber->authSessions()->where('active', true)->get() as $session) {
                try {
                    $sessions->close($session->astra_session_id);
                } catch (Throwable $e) {
                    report($e);
                }
            }

            $subscriber->update(['expired_processed_at' => now()]);

            $logger->log('subscriber.expired', $subscriber, [
                'expires_at' => $subscriber->expires_at?->toIso8601String(),
            ], 'scheduler', null);
        }

        $this->info('Vencimientos procesados: '.$expired->count());

        return self::SUCCESS;
    }
}
