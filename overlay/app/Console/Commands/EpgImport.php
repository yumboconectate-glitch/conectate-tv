<?php
namespace App\Console\Commands;
use App\Models\EpgSource;
use App\Services\ActivityLogger;
use App\Services\EpgImporter;
use Illuminate\Console\Command;
use Throwable;
class EpgImport extends Command
{
    protected $signature = 'epg:import {source? : ID de fuente} {--force}';
    protected $description = 'Importa fuentes XMLTV y auto-mapea canales';
    public function handle(EpgImporter $importer, ActivityLogger $logger): int
    {
        $query = EpgSource::where('enabled',true);
        if ($this->argument('source')) $query->whereKey((int)$this->argument('source'));
        $sources = $query->get();
        if ($sources->isEmpty()) { $this->info('No hay fuentes EPG para importar.'); return self::SUCCESS; }
        $fail = false;
        foreach ($sources as $source) {
            if (!$this->option('force') && !$this->argument('source') && $source->last_success_at && $source->last_success_at->gt(now()->subHours(max(1,$source->refresh_hours)))) continue;
            try {
                $r = $importer->import($source);
                $this->info($source->name.': '.$r['channelCount'].' canales, '.$r['programmeCount'].' programas, '.$r['mapped'].' auto-mapeados.');
                $logger->log('epg.imported', null, ['source_id'=>$source->id]+$r, 'scheduler', null);
            } catch (Throwable $e) {
                $fail = true; $this->error($source->name.': '.$e->getMessage());
                $logger->log('epg.import_failed', null, ['source_id'=>$source->id,'error'=>$e->getMessage()], 'scheduler', null);
            }
        }
        return $fail ? self::FAILURE : self::SUCCESS;
    }
}
