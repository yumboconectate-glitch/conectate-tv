<?php

namespace App\Console\Commands;

use App\Services\AstraSessionService;
use Illuminate\Console\Command;
use Throwable;

class AstraSessionsSync extends Command
{
    protected $signature = 'astra:sessions-sync';
    protected $description = 'Sincroniza sesiones HTTP/HLS activas desde Astra';

    public function handle(AstraSessionService $sessions): int
    {
        try {
            $active = $sessions->sync();
            $this->info('Sesiones Astra activas: '.$active->count());
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
