<?php

namespace App\Console\Commands;

use App\Models\Channel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AstraMiddlewareProbe extends Command
{
    protected $signature = 'astra:middleware-probe';
    protected $description = 'Comprueba que Astra consulte el backend HTTP de Conectate TV antes de retirar usuarios internos';

    public function handle(): int
    {
        $channel = Channel::where('enabled', true)
            ->where('on_air', true)
            ->whereNotNull('astra_stream_id')
            ->where('astra_stream_id', '<>', '')
            ->first();

        if (!$channel) {
            $this->error('No hay un canal ON AIR disponible para la prueba segura.');
            return self::FAILURE;
        }

        $base = rtrim((string) env(
            'ASTRA_STREAM_BASE_URL',
            env('ASTRA_STREAM_PUBLIC_URL', 'http://10.0.14.2:8000')
        ), '/');
        $probe = 'ctv-v03-probe-'.Str::lower(Str::random(20));
        $url = $base.'/play/'.$channel->astra_stream_id.'/index.m3u8?token='.urlencode($probe);

        try {
            $response = Http::timeout(8)->get($url);
            $this->line('Canal de prueba: '.$channel->astra_stream_id.' · '.$channel->name);
            $this->line('HTTP Astra: '.$response->status());

            if ($response->status() === 403) {
                $this->info('BACKEND_ENFORCED: Astra rechazó el token desconocido. Es seguro migrar usuarios CTV al middleware.');
                return self::SUCCESS;
            }

            $this->error('No se obtuvo 403. No se retirarán usuarios internos de Astra automáticamente.');
            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Probe falló: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
