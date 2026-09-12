<?php
namespace App\Console\Commands;

use App\Services\AlertEngine;
use Illuminate\Console\Command;
use Throwable;

class CheckAlerts extends Command
{
    protected $signature = 'alerts:check';

    protected $description = 'Evalua Astra, canales y EPG y actualiza el Centro NOC';

    public function handle(AlertEngine $engine): int
    {
        try {
            $result = $engine->run();

            $this->info(
                'NOC OK | activas: ' . $result['active']
                . ' | criticas: ' . $result['critical']
                . ' | advertencias: ' . $result['warning']
                . ' | resueltas ahora: ' . $result['resolved_now']
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('NOC ERROR: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }
}
