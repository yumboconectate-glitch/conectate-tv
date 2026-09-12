<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EpgProgramme extends Model
{
    protected $fillable = ['epg_source_id','xmltv_id','start_at','stop_at','title','subtitle','description','category','icon_url'];
    protected function casts(): array { return ['start_at'=>'datetime','stop_at'=>'datetime']; }
    public function source(): BelongsTo { return $this->belongsTo(EpgSource::class, 'epg_source_id'); }
}
