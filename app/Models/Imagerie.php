<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Imagerie extends Model
{
    protected $fillable = [
        'renseignement',
        'antibiotique',
        'statut',
        'note',
        'consultation_id',
        'hopital_id',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
