<?php
namespace App\Console\Commands;
use App\Models\AstraServer;
use App\Models\Channel;
use App\Services\ActivityLogger;
use App\Services\AstraClient;
use Illuminate\Console\Command;
use Throwable;
class AstraSync extends Command
{
    protected $signature='astra:sync {--status-only}';
    protected $description='Sincroniza Astra Cesbo con Conectate TV';
    public function handle(ActivityLogger $logger): int
    {
        $server=AstraServer::first();
        if(!$server){
            $password=env('ASTRA_PASSWORD'); if(!$password){$this->error('ASTRA_PASSWORD no está configurada.');return self::FAILURE;}
            $server=AstraServer::create(['name'=>env('ASTRA_NAME','Astra Principal'),'scheme'=>env('ASTRA_SCHEME','http'),'host'=>env('ASTRA_HOST','10.0.14.2'),'port'=>(int)env('ASTRA_PORT',8000),'username'=>env('ASTRA_USER','admin'),'password'=>$password,'enabled'=>true]);
        }
        $client=new AstraClient($server);
        try{
            $system=$client->systemStatus();
            $server->update(['last_seen_at'=>now(),'last_system_status'=>$system,'last_error'=>null]);
            if(!$this->option('status-only')){
                $config=$client->loadConfig();
                foreach(($config['make_stream']??[]) as $stream){
                    $id=trim((string)($stream['id']??'')); if($id==='') continue;
                    Channel::updateOrCreate(['astra_server_id'=>$server->id,'astra_stream_id'=>$id],[
                        'name'=>(string)($stream['name']??$id),'type'=>(string)($stream['type']??''),'enabled'=>(bool)($stream['enable']??false),
                        'input_count'=>count($stream['input']??[]),'output_count'=>count($stream['output']??[]),'last_synced_at'=>now()
                    ]);
                }
            }
            foreach($server->channels()->where('astra_stream_id','<>','')->get() as $channel){
                $previous=$channel->on_air;
                try{
                    $status=$client->streamStatus($channel->astra_stream_id);
                    $onAir=(bool)($status['onair']??$status['active']??false);
                    $updates=[
                        'on_air'=>$onAir,'bitrate'=>$status['bitrate']??null,'sessions'=>$status['sessions']??0,
                        'cc_errors'=>$status['cc_error']??0,'pes_errors'=>$status['pes_error']??0,'scrambling_errors'=>$status['sc_error']??0,
                        'status_payload'=>$status,'last_synced_at'=>now()
                    ];
                    if($onAir){
                        $updates['last_on_air_at']=now(); $updates['offline_since']=null;
                        if($previous===false){ $updates['health_changed_at']=now(); $logger->log('channel.recovered',null,['channel_id'=>$channel->id,'name'=>$channel->publicName(),'astra_stream_id'=>$channel->astra_stream_id],'scheduler',null); }
                    } else {
                        if(!$channel->offline_since) $updates['offline_since']=now();
                        if($previous===true){ $updates['health_changed_at']=now(); $updates['outage_count']=(int)$channel->outage_count+1; $logger->log('channel.offline',null,['channel_id'=>$channel->id,'name'=>$channel->publicName(),'astra_stream_id'=>$channel->astra_stream_id],'scheduler',null); }
                    }
                    $channel->update($updates);
                }catch(Throwable $e){
                    $updates=['on_air'=>false,'status_payload'=>['error'=>$e->getMessage()],'last_synced_at'=>now()];
                    if(!$channel->offline_since)$updates['offline_since']=now();
                    if($previous===true){$updates['health_changed_at']=now();$updates['outage_count']=(int)$channel->outage_count+1;$logger->log('channel.offline',null,['channel_id'=>$channel->id,'name'=>$channel->publicName(),'error'=>$e->getMessage()],'scheduler',null);}
                    $channel->update($updates);
                }
            }
            $this->info('Astra sincronizado: '.$server->channels()->count().' canales.'); return self::SUCCESS;
        }catch(Throwable $e){$server->update(['last_error'=>$e->getMessage()]);$this->error($e->getMessage());return self::FAILURE;}
    }
}
