<?php

namespace App\Models;

use App\Enums\ClinicalExamFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationClinicalExamValue extends Model
{
    protected $fillable = [
        'consultation_id',
        'clinical_exam_field_definition_id',
        'present',
        'value_text',
        'value_number',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'present' => 'boolean',
            'value_number' => 'decimal:2',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ClinicalExamFieldDefinition::class, 'clinical_exam_field_definition_id');
    }

    public function isAnswered(): bool
    {
        $type = $this->definition?->field_type;

        return match ($type) {
            ClinicalExamFieldType::Boolean => $this->present !== null,
            ClinicalExamFieldType::BooleanWithNote => $this->present !== null,
            ClinicalExamFieldType::Number => $this->value_number !== null || filled($this->value_text),
            ClinicalExamFieldType::Text => filled($this->value_text),
            default => false,
        };
    }

    public function displaySummary(): string
    {
        if (! $this->isAnswered()) {
            return '—';
        }

        $type = $this->definition?->field_type;

        return match ($type) {
            ClinicalExamFieldType::Boolean => $this->present ? 'Oui' : 'Non',
            ClinicalExamFieldType::BooleanWithNote => $this->formatBooleanWithNote(),
            ClinicalExamFieldType::Number => $this->formatNumber(),
            ClinicalExamFieldType::Text => (string) $this->value_text,
            default => '—',
        };
    }

    private function formatBooleanWithNote(): string
    {
        if (! $this->present) {
            return 'Non';
        }

        $parts = ['Oui'];

        if (filled($this->note)) {
            $unit = $this->definition?->value_label;
            $parts[] = $unit ? "{$this->note} {$unit}" : $this->note;
        }

        return implode(' · ', $parts);
    }

    private function formatNumber(): string
    {
        if ($this->value_number !== null) {
            $unit = $this->definition?->value_label;

            return $unit
                ? rtrim(rtrim((string) $this->value_number, '0'), '.') . ' ' . $unit
                : (string) $this->value_number;
        }

        return (string) ($this->value_text ?? '—');
    }
}
