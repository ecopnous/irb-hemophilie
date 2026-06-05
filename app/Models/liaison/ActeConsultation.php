<?php

namespace App\Models\liaison;

use App\Models\Configs\Acte;
use App\Models\Consultation;
use Illuminate\Database\Eloquent\Model;

class ActeConsultation extends Model
{
    protected $table = 'acte_consultation';

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function acte()
    {
        return $this->belongsTo(Acte::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }
}
