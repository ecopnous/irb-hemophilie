<?php

namespace App\Models;

use App\Enums\ClinicalExamFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalExamFieldDefinition extends Model
{
    protected $fillable = [
        'section_key',
        'section_label',
        'key',
        'label',
        'description',
        'field_type',
        'value_label',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'field_type' => ClinicalExamFieldType::class,
            'is_active' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(ConsultationClinicalExamValue::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
