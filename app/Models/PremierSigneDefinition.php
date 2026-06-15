<?php

namespace App\Models;

use App\Enums\PremierSigneValueType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PremierSigneDefinition extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'value_type',
        'value_label',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value_type' => PremierSigneValueType::class,
            'is_active' => 'boolean',
        ];
    }

    public function patientSignes(): HasMany
    {
        return $this->hasMany(DossierPatientPremierSigne::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
