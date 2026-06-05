<?php

namespace App\Models\liaison;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //

    public function acteConsultation()
    {
        return $this->belongsTo(ActeConsultation::class);
    }
}
