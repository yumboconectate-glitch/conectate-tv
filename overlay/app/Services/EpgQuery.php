<?php
namespace App\Services;
use App\Models\Channel;
use App\Models\EpgProgramme;
class EpgQuery
{
    public function forChannel(Channel $channel, int $hours=24, int $limit=100)
    {
        if(!$channel->epg_source_id || !$channel->epg_xmltv_id) return collect();
        return EpgProgramme::where('epg_source_id',$channel->epg_source_id)->where('xmltv_id',$channel->epg_xmltv_id)
            ->where('stop_at','>',now()->subHours(3))->where('start_at','<',now()->addHours(max(1,$hours)))
            ->orderBy('start_at')->limit($limit)->get();
    }
}
