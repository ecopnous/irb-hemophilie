<?php
namespace App\Models\Localisations;

use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    protected $fillable = ['name', 'code', 'province_id'];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function zonesSante()
    {
        return $this->hasMany(ZoneSante::class);
    }
}
