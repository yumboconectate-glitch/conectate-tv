<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use Illuminate\Console\Command;

class V04Backfill extends Command
{
    protected $signature = 'conectate:v04-backfill';
    protected $description = 'Completa valores por defecto introducidos en Conectate TV v0.4';

    public function handle(): int
    {
        Subscriber::whereNull('max_devices')->orWhere('max_devices', '<', 1)
            ->update(['max_devices' => 5]);

        $this->info('Backfill v0.4 completado.');
        return self::SUCCESS;
    }
}
