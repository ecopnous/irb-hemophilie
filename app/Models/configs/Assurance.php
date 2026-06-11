<?php

namespace App\Models\Configs;

use App\Models\Consultation;
use App\Models\DossierPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assurance extends Model
{
    protected $fillable = [
        'reference',
        'name',
        'description',
        'email',
        'type',
        'logo',
        'categorisation_id',
        'forfait_actif',
        'prix_patient',
    ];

    protected $casts = [
        'forfait_actif' => 'boolean',
        'prix_patient' => 'decimal:2',
    ];

    public function categorisation(): BelongsTo
    {
        return $this->belongsTo(Categorisation::class);
    }

    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(DossierPatient::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return '/storage/' . ltrim(str_replace('\\', '/', $this->logo), '/');
    }
}
