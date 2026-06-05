<?php

namespace App\Models;

use App\Models\Configs\Acte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laboratoire extends Model
{
    protected $fillable = [
        'note',
        'renseignement',
        'antibiotique',
        'statut',
        'date_heure_prelevemnt',
        'date_heure_validation',
        'commentaire',
        'user_id',
        'user_valideur_id',
        'consultation_id',
        'hopital_id',
    ];

    protected $casts = [
        'date_heure_prelevemnt' => 'datetime',
        'date_heure_validation' => 'datetime',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }


    public function actes()
    {
        return $this->belongsToMany(Acte::class)
            ->withPivot('resultat', 'valide', 'user_id', 'note');
    }

    public function userValideur()
    {
        return $this->belongsTo(User::class, 'user_valideur_id');
    }

    public function userPreleveur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
