<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'departement_id',
    ];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function actes(): HasMany
    {
        return $this->hasMany(Acte::class);
    }
}
