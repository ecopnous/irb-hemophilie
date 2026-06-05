<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Model;

class Categorisation extends Model
{
    protected $fillable = [
        'name',
        'pourcentage',
        'description',
    ];

    public function assurances()
    {
        return $this->hasMany(Assurance::class);
    }

    public function paquets()
    {
        return $this->hasMany(PacquetSoin::class);
    }
}
