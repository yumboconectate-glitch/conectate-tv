<?php

namespace App\Console\Commands;

use App\Models\AstraServer;
use App\Models\Subscriber;
use App\Services\AstraClient;
use Illuminate\Console\Command;
use Throwable;

class AstraDetachSubscribers extends Command
{
    protected $signature = 'astra:detach-subscribers {--force : Elimina de Astra los usuarios generados por Conectate TV}';
    protected $description = 'Migra abonados desde auth interna de Astra al backend HTTP de Conectate TV';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->warn('Modo seguro: no se modificó Astra. Usa --force únicamente después de validar /astra-auth.');
            return self::SUCCESS;
        }

        $server = AstraServer::first();
        if (!$server) {
            $this->error('No existe servidor Astra configurado.');
            return self::FAILURE;
        }

        $client = new AstraClient($server);
        $ok = 0;
        $failed = 0;

        Subscriber::whereNull('astra_detached_at')->orderBy('id')->each(function (Subscriber $subscriber) use ($client, &$ok, &$failed) {
            try {
                $response = $client->removeUser($subscriber->astra_login);
                if (($response['set-user'] ?? null) !== 'ok') {
                    throw new \RuntimeException('Respuesta inesperada: '.json_encode($response));
                }

                $subscriber->update([
                    'astra_detached_at' => now(),
                    'astra_synced_at' => now(),
                    'astra_last_error' => null,
                ]);
                $this->line('OK  '.$subscriber->id.' · '.$subscriber->name);
                $ok++;
            } catch (Throwable $e) {
                $subscriber->update(['astra_last_error' => $e->getMessage()]);
                $this->error('ERR '.$subscriber->id.' · '.$subscriber->name.' · '.$e->getMessage());
                $failed++;
            }
        });

        $this->info("Migración a middleware: {$ok} OK, {$failed} con error.");
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
