<?php

namespace App\Models\Configs;

use App\Models\DossierPatient;
use App\Models\hospitalisation\Hospitalisation;
use App\Models\hospitalisation\HospService;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hopital extends Model
{
    protected $fillable = [
        'reference',
        'name',
        'type',
        'devise',
        'code_postal',
        'is_actif',
        'site_web',
        'numero_licence',
        'autorite_regulation',
        'description',
        'quartier',
        'avenue',
        'numero',
        'country_id',
        'province_id',
        'ville_id',
        'commune_id',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(DossierPatient::class);
    }

    public function hospitalisationServices(): HasMany
    {
        return $this->hasMany(HospService::class);
    }

    public function hospitalisations(): HasMany
    {
        return $this->hasMany(Hospitalisation::class);
    }

    public function groupes(): BelongsToMany
    {
        return $this->belongsToMany(GroupeHopital::class, 'groupe_hopital_hopital')->withTimestamps();
    }
}
