<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorisation extends Model
{
    protected $fillable = [
        'name',
        'pourcentage',
        'description',
    ];

    protected $casts = [
        'pourcentage' => 'float',
    ];

    public function assurances(): HasMany
    {
        return $this->hasMany(Assurance::class);
    }

    public function paquets(): HasMany
    {
        return $this->hasMany(PacquetSoin::class);
    }
}
