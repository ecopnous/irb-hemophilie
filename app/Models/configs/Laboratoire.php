<?php

namespace App\Models\Configs;

use App\Models\consultation\ConsultInitiale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Laboratoire extends Model
{
    protected $fillable = [
        'consult_initiale_id',
        'enseignement',
        'antibiotique',
    ];

    // public function consultationUnitial(){
    //     return $this->belongsTo(ConsultInitiale::class, 'consult_initiale_id');
    // }

    // public function consultInitiale(){
    //     return $this->belongsTo(ConsultInitiale::class, 'consult_initiale_id');
    // }

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
        return $this->belongsTo(User::class, 'user_preleveur_id');
    }
}
