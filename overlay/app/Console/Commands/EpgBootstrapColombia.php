<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\EpgSource;
use App\Services\ActivityLogger;
use App\Services\EpgImporter;
use Illuminate\Console\Command;
use Throwable;

class EpgBootstrapColombia extends Command
{
    protected $signature = 'epg:bootstrap-colombia {--force-remap : Limpia únicamente mapeos automáticos previos}';
    protected $description = 'Configura e importa la guía XMLTV Colombia CO1';

    public function handle(
        EpgImporter $importer,
        ActivityLogger $logger
    ): int {
        $source = EpgSource::updateOrCreate(
            ['name' => 'EPG Colombia CO1'],
            [
                'url' => 'https://epgshare01.online/epgshare01/epg_ripper_CO1.xml.gz',
                'enabled' => true,
                'refresh_hours' => 6,
            ]
        );

        if ($this->option('force-remap')) {
            Channel::where('epg_source_id', $source->id)
                ->whereNotNull('epg_auto_mapped_at')
                ->update([
                    'epg_source_id' => null,
                    'epg_xmltv_id' => null,
                    'epg_auto_mapped_at' => null,
                ]);
        }

        $this->info('Fuente: '.$source->name);
        $this->info('Importando XMLTV Colombia...');

        try {
            $result = $importer->import($source);

            $valid = Channel::where('published', true)
                ->whereNotNull('astra_stream_id')
                ->where('astra_stream_id', '<>', '')
                ->count();

            $mapped = Channel::where('published', true)
                ->whereNotNull('astra_stream_id')
                ->where('astra_stream_id', '<>', '')
                ->whereNotNull('epg_xmltv_id')
                ->where('epg_xmltv_id', '<>', '')
                ->count();

            $this->newLine();
            $this->info('Canales XMLTV encontrados: '.$result['channelCount']);
            $this->info('Programas importados: '.$result['programmeCount']);
            $this->info('Nuevos auto-mapeados: '.$result['mapped']);
            $this->info("Parrilla con EPG: {$mapped}/{$valid}");

            $unmapped = Channel::where('published', true)
                ->whereNotNull('astra_stream_id')
                ->where('astra_stream_id', '<>', '')
                ->where(function ($q) {
                    $q->whereNull('epg_xmltv_id')->orWhere('epg_xmltv_id', '');
                })
                ->orderByRaw('channel_number IS NULL')
                ->orderBy('channel_number')
                ->limit(80)
                ->get(['channel_number', 'display_name', 'name', 'astra_stream_id']);

            if ($unmapped->isNotEmpty()) {
                $this->newLine();
                $this->warn('CANALES PENDIENTES DE MAPEAR:');

                foreach ($unmapped as $channel) {
                    $name = $channel->display_name ?: $channel->name;
                    $this->line(sprintf(
                        '%s | %s | %s',
                        $channel->channel_number ?: '-',
                        $name,
                        $channel->astra_stream_id
                    ));
                }
            }

            $logger->log(
                'epg.colombia_bootstrap',
                null,
                [
                    'source_id' => $source->id,
                    'xmltv_channels' => $result['channelCount'],
                    'programmes' => $result['programmeCount'],
                    'mapped_total' => $mapped,
                    'valid_channels' => $valid,
                ],
                'installer',
                null
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('No fue posible importar EPG: '.$e->getMessage());

            $logger->log(
                'epg.colombia_bootstrap_failed',
                null,
                [
                    'source_id' => $source->id,
                    'error' => $e->getMessage(),
                ],
                'installer',
                null
            );

            return self::FAILURE;
        }
    }
}
