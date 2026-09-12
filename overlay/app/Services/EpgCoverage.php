<?php
namespace App\Services;

use App\Models\EpgProgramme;
use Illuminate\Support\Collection;

class EpgCoverage
{
    public function analyse(Collection $channels): array
    {
        $futurePairs = EpgProgramme::query()
            ->where('stop_at', '>', now())
            ->select('epg_source_id', 'xmltv_id')
            ->distinct()
            ->get()
            ->mapWithKeys(function ($p) {
                return [$p->epg_source_id.'|'.$p->xmltv_id => true];
            });

        $states = [];
        $stats = [
            'total' => $channels->count(),
            'epg' => 0,
            'radio' => 0,
            'own' => 0,
            'test' => 0,
            'unavailable' => 0,
        ];

        foreach ($channels as $channel) {
            if (in_array($channel->epg_kind, ['radio', 'own', 'test'], true)) {
                $status = $channel->epg_kind;
            } else {
                $mapped = !empty($channel->epg_source_id)
                    && trim((string) $channel->epg_xmltv_id) !== '';

                $key = $mapped
                    ? $channel->epg_source_id.'|'.$channel->epg_xmltv_id
                    : null;

                $status = ($mapped && $key && $futurePairs->has($key))
                    ? 'epg'
                    : 'unavailable';
            }

            $states[$channel->id] = $status;

            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        $coveragePct = $stats['total'] > 0
            ? round(($stats['epg'] / $stats['total']) * 100, 1)
            : 0;

        return [
            'states' => $states,
            'stats' => $stats,
            'coverage_pct' => $coveragePct,
        ];
    }
}
