<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierPatientPremierSigne extends Model
{
    protected $fillable = [
        'dossier_patient_id',
        'premier_signe_definition_id',
        'present',
        'value',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'present' => 'boolean',
            'value' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class, 'dossier_patient_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(PremierSigneDefinition::class, 'premier_signe_definition_id');
    }

    public function isAnswered(): bool
    {
        return $this->present !== null;
    }

    public function isComplete(): bool
    {
        return $this->isAnswered();
    }

    public function displaySummary(): string
    {
        if ($this->present === null) {
            return '—';
        }

        if (! $this->present) {
            return 'Non';
        }

        $parts = ['Oui'];

        if ($this->value !== null) {
            $label = $this->definition?->value_label ?? 'Valeur';
            $unit = $this->definition?->value_type?->unit();
            $valueText = $unit !== ''
                ? "{$this->value} {$unit}"
                : (string) $this->value;

            $parts[] = "{$label} : {$valueText}";
        }

        if (filled($this->comment)) {
            $parts[] = $this->comment;
        }

        return implode(' · ', $parts);
    }
}
