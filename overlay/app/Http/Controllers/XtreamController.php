<?php
namespace App\Http\Controllers;
use App\Models\Channel;
use App\Models\EpgProgramme;
use App\Models\Subscriber;
use App\Services\EpgQuery;
use Illuminate\Http\Request;
class XtreamController extends Controller
{
    public function __construct(private readonly EpgQuery $epg) {}
    public function playerApi(Request $request)
    {
        $subscriber=$this->authenticate($request);
        if(!$subscriber)return response()->json(['user_info'=>['auth'=>0,'status'=>'Disabled'],'server_info'=>$this->serverInfo()],200,['Cache-Control'=>'no-store']);
        $action=(string)$request->query('action','');
        if($action==='get_live_categories'){
            $categories=$this->channels($subscriber)->map(fn(Channel $c)=>$c->category)->filter()->unique('id')->sortBy('sort_order')->values()->map(fn($cat)=>['category_id'=>(string)$cat->id,'category_name'=>$cat->name,'parent_id'=>0]);
            if($categories->isEmpty())$categories=collect([['category_id'=>(string)$subscriber->plan->id,'category_name'=>$subscriber->plan->name,'parent_id'=>0]]);
            return $this->json($categories);
        }
        if($action==='get_live_streams'){
            return $this->json($this->channels($subscriber)->values()->map(fn(Channel $c,int $i)=>[
                'num'=>(int)($c->channel_number?:$i+1),'name'=>$c->publicName(),'stream_type'=>'live','stream_id'=>$c->id,'stream_icon'=>(string)($c->logo_url??''),
                'epg_channel_id'=>$c->epgId(),'added'=>(string)($c->created_at?->timestamp??time()),'category_id'=>(string)($c->category_id?:$subscriber->plan->id),
                'custom_sid'=>'','tv_archive'=>0,'direct_source'=>'','tv_archive_duration'=>0,
            ]));
        }
        if(in_array($action,['get_short_epg','get_simple_data_table'],true)){
            $streamId=(int)$request->query('stream_id',0); $channel=$this->channels($subscriber)->firstWhere('id',$streamId);
            if(!$channel)return $this->json(['epg_listings'=>[]]);
            $limit=max(1,min(100,(int)$request->query('limit',20))); $list=$this->epg->forChannel($channel,48,$limit)->map(fn($p)=>$this->epgRow($p));
            return $this->json(['epg_listings'=>$list]);
        }
        if(in_array($action,['get_vod_categories','get_vod_streams','get_series_categories','get_series'],true))return $this->json([]);
        return $this->json(['user_info'=>[
            'username'=>$subscriber->username,'password'=>$subscriber->access_password,'message'=>'Conectate TV v0.6','auth'=>1,'status'=>'Active',
            'exp_date'=>$subscriber->expires_at?(string)$subscriber->expires_at->timestamp:null,'is_trial'=>'0','active_cons'=>(string)$subscriber->authSessions()->where('active',true)->count(),
            'created_at'=>(string)$subscriber->created_at->timestamp,'max_connections'=>(string)max(1,(int)$subscriber->max_connections),'allowed_output_formats'=>['m3u8','ts'],
        ],'server_info'=>$this->serverInfo()]);
    }
    public function getPlaylist(Request $request)
    {
        $s=$this->authenticate($request); abort_unless($s,403,'Forbidden'); $ext=strtolower((string)$request->query('output','m3u8'))==='ts'?'ts':'m3u8'; $lines=['#EXTM3U'];
        foreach($this->channels($s) as $c){$name=str_replace(["\r","\n"],' ',$c->publicName());$group=str_replace(["\r","\n",'"'],' ',$c->category?->name?:$s->plan->name);$logo=str_replace('"','',(string)$c->logo_url);$number=$c->channel_number?' tvg-chno="'.$c->channel_number.'"':'';$lines[]='#EXTINF:-1 tvg-id="'.str_replace('"','',$c->epgId()).'" tvg-name="'.str_replace('"','',$name).'" tvg-logo="'.$logo.'"'.$number.' group-title="'.$group.'",'.$name;$lines[]=url('/live/'.rawurlencode($s->username).'/'.rawurlencode($s->access_password).'/'.$c->id.'.'.$ext);}
        return response(implode("\n",$lines)."\n",200,['Content-Type'=>'audio/x-mpegurl; charset=utf-8','Content-Disposition'=>'inline; filename="conectate-tv-xtream.m3u"','Cache-Control'=>'no-store']);
    }
    public function live(Request $request,string $username,string $password,int $streamId)
    {
        $s=$this->authenticateCredentials($username,$password);abort_unless($s,403,'Forbidden');$c=$this->channels($s)->firstWhere('id',$streamId);abort_unless($c,403,'Forbidden');$base=rtrim((string)env('ASTRA_STREAM_PUBLIC_URL',env('ASTRA_STREAM_BASE_URL','http://10.0.14.2:8000')),'/');return redirect()->away($base.'/play/'.$c->astra_stream_id.'/index.m3u8?token='.urlencode($s->token),302,['Cache-Control'=>'no-store']);
    }
    public function xmltv(Request $request)

    {

        $s=$this->authenticate($request);

        abort_unless($s,403,'Forbidden');



        $channels=$this->channels($s);



        // XMLTV requires unique channel IDs.

        // Several Conectate streams may intentionally share the same EPG ID

        // (for example RCN HD / RCN HD TLVVD), so emit each EPG channel once.

        $uniqueChannels=$channels

            ->groupBy(fn(Channel $c)=>$c->epgId())

            ->map(fn($group)=>$group->first())

            ->values();



        $xml=[

            '<?xml version="1.0" encoding="UTF-8"?>',

            '<tv generator-info-name="Conectate TV">'

        ];



        foreach($uniqueChannels as $c){

            $id=htmlspecialchars($c->epgId(),ENT_XML1|ENT_QUOTES,'UTF-8');

            $name=htmlspecialchars($c->publicName(),ENT_XML1|ENT_QUOTES,'UTF-8');



            $icon=trim((string)$c->logo_url)!==''

                ? '<icon src="'.htmlspecialchars($c->logo_url,ENT_XML1|ENT_QUOTES,'UTF-8').'"/>'

                : '';



            $xml[]='<channel id="'.$id.'"><display-name>'.$name.'</display-name>'.$icon.'</channel>';

        }



        // Generate programmes only once per unique EPG channel ID.

        foreach($uniqueChannels as $c){

            foreach($this->epg->forChannel($c,168,500) as $p){

                $id=htmlspecialchars($c->epgId(),ENT_XML1|ENT_QUOTES,'UTF-8');

                $start=$p->start_at->format('YmdHis O');

                $stop=$p->stop_at->format('YmdHis O');



                $xml[]='<programme start="'.$start.'" stop="'.$stop.'" channel="'.$id.'">'

                    .$this->xmlTag('title',$p->title)

                    .$this->xmlTag('sub-title',$p->subtitle)

                    .$this->xmlTag('desc',$p->description)

                    .$this->xmlTag('category',$p->category)

                    .'</programme>';

            }

        }



        $xml[]='</tv>';



        return response(

            implode("\n",$xml)."\n",

            200,

            [

                'Content-Type'=>'application/xml; charset=utf-8',

                'Cache-Control'=>'no-store, no-cache, must-revalidate, max-age=0'

            ]

        );

    }



    private function epgRow($p): array{return ['id'=>$p->id,'epg_id'=>$p->id,'title'=>base64_encode((string)$p->title),'lang'=>'es','start'=>$p->start_at->format('Y-m-d H:i:s'),'end'=>$p->stop_at->format('Y-m-d H:i:s'),'description'=>base64_encode((string)$p->description),'channel_id'=>$p->xmltv_id,'start_timestamp'=>$p->start_at->timestamp,'stop_timestamp'=>$p->stop_at->timestamp,'now_playing'=>$p->start_at->lte(now())&&$p->stop_at->gt(now())?1:0,'has_archive'=>0];}
    private function xmlTag(string $name,?string $v): string{if(!$v)return '';return '<'.$name.'>'.htmlspecialchars($v,ENT_XML1|ENT_QUOTES,'UTF-8').'</'.$name.'>';}
    private function authenticate(Request $r): ?Subscriber{return $this->authenticateCredentials((string)$r->query('username',''),(string)$r->query('password',''));}
    private function authenticateCredentials(string $u,string $p): ?Subscriber{if($u===''||$p==='')return null;$s=Subscriber::with(['plan.channels'=>fn($q)=>$q->with(['category','epgSource'])])->where('username',$u)->first();if(!$s||!$s->access_password||!$s->canWatch())return null;return hash_equals((string)$s->access_password,$p)?$s:null;}
    private function channels(Subscriber $s){return $s->plan->channels->filter(fn(Channel $c)=>$c->enabled&&$c->published&&trim((string)$c->astra_stream_id)!=='')->sortBy(fn(Channel $c)=>sprintf('%08d-%08d-%s',$c->category?->sort_order??9999,$c->channel_number??99999999,$c->publicName()));}
    private function serverInfo(): array{$url=parse_url((string)config('app.url'))?:[];$scheme=$url['scheme']??'http';return ['url'=>$url['host']??'10.1.19.245','port'=>(string)($url['port']??($scheme==='https'?443:8098)),'https_port'=>$scheme==='https'?(string)($url['port']??443):'','server_protocol'=>$scheme,'rtmp_port'=>'','timezone'=>config('app.timezone','America/Bogota'),'timestamp_now'=>time(),'time_now'=>now()->toDateTimeString()];}
    private function json($p){return response()->json($p,200,['Cache-Control'=>'no-store']);}
}
