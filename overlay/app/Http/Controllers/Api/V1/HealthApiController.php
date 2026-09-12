<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\AstraServer;
use App\Models\Channel;
class HealthApiController extends Controller
{
    public function __invoke()
    {
        $server=AstraServer::first();
        $base=Channel::where('published',true)->where('astra_stream_id','<>','');
        $offline=(clone $base)->where('on_air',false)->get(['id','channel_number','display_name','name','astra_stream_id','offline_since','outage_count']);
        return response()->json(['ok'=>true,'data'=>[
            'astra'=>['online'=>(bool)$server?->last_seen_at && !$server?->last_error,'last_seen_at'=>$server?->last_seen_at?->toIso8601String(),'last_error'=>$server?->last_error],
            'channels'=>['published'=>(clone $base)->count(),'on_air'=>(clone $base)->where('on_air',true)->count(),'offline'=>$offline->count()],
            'offline'=>$offline->map(fn($c)=>['id'=>$c->id,'number'=>$c->channel_number,'name'=>$c->publicName(),'astra_stream_id'=>$c->astra_stream_id,'offline_since'=>$c->offline_since?->toIso8601String(),'outage_count'=>$c->outage_count]),
        ]],200,['Cache-Control'=>'no-store']);
    }
}
