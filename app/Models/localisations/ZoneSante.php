<?php
namespace App\Models\Localisations;

use Illuminate\Database\Eloquent\Model;

class ZoneSante extends Model
{
    protected $table = 'zones_sante';
    protected $fillable = ['name', 'code', 'description', 'commune_id'];

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }
}
