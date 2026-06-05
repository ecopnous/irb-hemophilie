<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allergy extends Model
{
    //

    public function patients()
    {
        return $this->belongsTo(DossierPatient::class);
    }
}
