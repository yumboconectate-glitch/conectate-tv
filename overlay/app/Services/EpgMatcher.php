<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\EpgChannel;
use App\Models\EpgSource;

class EpgMatcher
{
    public function autoMap(EpgSource $source): int
    {
        $epgChannels = EpgChannel::where('epg_source_id', $source->id)->get();

        $index = [];

        foreach ($epgChannels as $epgChannel) {
            foreach (EpgName::variants($epgChannel->display_name) as $variant) {
                $index[$variant] ??= [];
                $index[$variant][] = $epgChannel;
            }

            foreach (EpgName::variants($epgChannel->xmltv_id) as $variant) {
                $index[$variant] ??= [];
                $index[$variant][] = $epgChannel;
            }
        }

        $mapped = 0;

        $channels = Channel::query()
            ->where('published', true)
            ->whereNotNull('astra_stream_id')
            ->where('astra_stream_id', '<>', '')
            ->where(function ($q) {
                $q->whereNull('epg_xmltv_id')->orWhere('epg_xmltv_id', '');
            })
            ->get();

        foreach ($channels as $channel) {
            $selected = null;

            foreach (EpgName::variants($channel->publicName()) as $variant) {
                $matches = collect($index[$variant] ?? [])
                    ->unique('id')
                    ->values();

                if ($matches->count() === 1) {
                    $selected = $matches->first();
                    break;
                }
            }

            if (!$selected) {
                continue;
            }

            $channel->update([
                'epg_source_id' => $source->id,
                'epg_xmltv_id' => $selected->xmltv_id,
                'epg_auto_mapped_at' => now(),
                'logo_url' => $channel->logo_url ?: $selected->icon_url,
            ]);

            $mapped++;
        }

        return $mapped;
    }
}
