<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Channel extends Model
{
    protected $fillable = [
        'astra_server_id','astra_stream_id','name','display_name','channel_number','logo_url','category_id','published',
        'epg_source_id','epg_xmltv_id','epg_auto_mapped_at','epg_kind','type','enabled','input_count','output_count','on_air','offline_since',
        'last_on_air_at','health_changed_at','outage_count','bitrate','sessions','cc_errors','pes_errors','scrambling_errors','status_payload','last_synced_at'
    ];
    protected function casts(): array { return [
        'enabled'=>'boolean','published'=>'boolean','on_air'=>'boolean','status_payload'=>'array','last_synced_at'=>'datetime',
        'epg_auto_mapped_at'=>'datetime','offline_since'=>'datetime','last_on_air_at'=>'datetime','health_changed_at'=>'datetime'
    ]; }
    public function astraServer(): BelongsTo { return $this->belongsTo(AstraServer::class); }
    public function plans(): BelongsToMany { return $this->belongsToMany(Plan::class)->withTimestamps(); }
    public function category(): BelongsTo { return $this->belongsTo(ChannelCategory::class, 'category_id'); }
    public function epgSource(): BelongsTo { return $this->belongsTo(EpgSource::class, 'epg_source_id'); }
    public function publicName(): string { return trim((string)$this->display_name) !== '' ? $this->display_name : $this->name; }
    public function epgId(): string { return trim((string)$this->epg_xmltv_id) !== '' ? $this->epg_xmltv_id : 'ctv-'.$this->id; }
}
