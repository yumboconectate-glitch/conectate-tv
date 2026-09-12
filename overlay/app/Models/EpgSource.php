<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class EpgSource extends Model
{
    protected $fillable = ['name','url','enabled','refresh_hours','last_import_at','last_success_at','last_error','last_channel_count','last_programme_count'];
    protected function casts(): array { return ['enabled'=>'boolean','last_import_at'=>'datetime','last_success_at'=>'datetime']; }
    public function epgChannels(): HasMany { return $this->hasMany(EpgChannel::class); }
    public function programmes(): HasMany { return $this->hasMany(EpgProgramme::class); }
}
