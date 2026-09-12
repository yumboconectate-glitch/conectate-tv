<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class V03Backfill extends Command
{
    protected $signature = 'conectate:v03-backfill';
    protected $description = 'Completa credenciales y campos nuevos de Conectate TV v0.3';

    public function handle(): int
    {
        $count = 0;

        Subscriber::orderBy('id')->chunkById(100, function ($subscribers) use (&$count) {
            foreach ($subscribers as $subscriber) {
                $updates = [];

                if (!$subscriber->username) {
                    $updates['username'] = 'ctv'.str_pad((string) $subscriber->id, 6, '0', STR_PAD_LEFT);
                }

                if (!$subscriber->access_password) {
                    $updates['access_password'] = Str::lower(Str::random(12));
                }

                if (!$subscriber->token) {
                    $updates['token'] = Str::lower(Str::random(40));
                }

                if ($updates !== []) {
                    $subscriber->update($updates);
                    $count++;
                }
            }
        });

        $this->info("Backfill v0.3 completado: {$count} clientes actualizados.");
        return self::SUCCESS;
    }
}
