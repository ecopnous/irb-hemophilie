<?php

namespace App\Models;

use App\Enums\AllergyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allergy extends Model
{
    protected $fillable = [
        'type',
        'autre',
        'symptome',
        'solution',
        'description',
        'date_debut',
        'date_fin',
        'dossier_patient_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => AllergyType::class,
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class, 'dossier_patient_id');
    }

    public function typeLabel(): string
    {
        return $this->type?->label() ?? '—';
    }

    public function displayName(): string
    {
        if ($this->type === AllergyType::Autre && filled($this->autre)) {
            return $this->autre;
        }

        return $this->typeLabel();
    }

    public function isActive(): bool
    {
        return $this->date_fin === null || $this->date_fin->isFuture();
    }
}
