<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EpgChannel extends Model
{
    protected $fillable = ['epg_source_id','xmltv_id','display_name','normalized_name','icon_url','last_seen_at'];
    protected function casts(): array { return ['last_seen_at'=>'datetime']; }
    public function source(): BelongsTo { return $this->belongsTo(EpgSource::class, 'epg_source_id'); }
}
