<?php

namespace App\Services\Consultation;

use App\Enums\ClinicalExamFieldType;
use App\Models\ClinicalExamFieldDefinition;
use App\Models\Consultation;
use App\Models\ConsultationClinicalExam;
use App\Models\ConsultationClinicalExamValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClinicalExamService
{
    /**
     * @return Collection<int, ClinicalExamFieldDefinition>
     */
    public function definitions(): Collection
    {
        return ClinicalExamFieldDefinition::query()->active()->get();
    }

    /**
     * @return Collection<string, Collection<int, ClinicalExamFieldDefinition>>
     */
    public function definitionsBySection(): Collection
    {
        return $this->definitions()->groupBy('section_key');
    }

    /**
     * @return array<int, array{key: string, label: string, fields: Collection<int, ClinicalExamFieldDefinition>}>
     */
    public function sections(): array
    {
        return $this->definitionsBySection()
            ->map(function (Collection $fields, string $sectionKey) {
                $first = $fields->first();

                return [
                    'key' => $sectionKey,
                    'label' => $first?->section_label ?? $sectionKey,
                    'fields' => $fields,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{examined_at: ?string, synthesis: string, fields: array<string, array<string, mixed>>}
     */
    public function toFormArray(Consultation $consultation): array
    {
        $consultation->loadMissing(['clinicalExam', 'clinicalExamValues.definition']);

        $answers = $consultation->clinicalExamValues
            ->keyBy(fn (ConsultationClinicalExamValue $value) => $value->definition->key);

        $fields = [];

        foreach ($this->definitions() as $definition) {
            $answer = $answers->get($definition->key);

            $fields[$definition->key] = [
                'present' => $answer?->present === null ? null : ($answer->present ? 1 : 0),
                'value_text' => (string) ($answer?->value_text ?? ''),
                'value_number' => $answer?->value_number,
                'note' => (string) ($answer?->note ?? ''),
            ];
        }

        return [
            'examined_at' => $consultation->clinicalExam?->examined_at?->format('Y-m-d'),
            'synthesis' => (string) ($consultation->clinicalExam?->synthesis ?? ''),
            'fields' => $fields,
        ];
    }

    /**
     * @return Collection<int, array{section: array{key: string, label: string}, rows: Collection<int, array{definition: ClinicalExamFieldDefinition, answer: ConsultationClinicalExamValue|null}>}>
     */
    public function presentationSections(Consultation $consultation): Collection
    {
        $consultation->loadMissing(['clinicalExam', 'clinicalExamValues.definition']);

        $answers = $consultation->clinicalExamValues
            ->keyBy(fn (ConsultationClinicalExamValue $value) => $value->definition->key);

        return collect($this->sections())->map(function (array $section) use ($answers) {
            $rows = $section['fields']->map(fn (ClinicalExamFieldDefinition $definition) => [
                'definition' => $definition,
                'answer' => $answers->get($definition->key),
            ])->filter(fn (array $row) => $row['answer']?->isAnswered() ?? false);

            return [
                'section' => ['key' => $section['key'], 'label' => $section['label']],
                'rows' => $rows,
            ];
        })->filter(fn (array $section) => $section['rows']->isNotEmpty());
    }

    /**
     * @return array{answered: int, total: int, percent: int}
     */
    public function progress(Consultation $consultation): array
    {
        $consultation->loadMissing(['clinicalExam', 'clinicalExamValues.definition']);

        $answers = $consultation->clinicalExamValues
            ->keyBy(fn (ConsultationClinicalExamValue $value) => $value->definition->key);

        $total = $this->definitions()->count();
        $answered = $this->definitions()->filter(function (ClinicalExamFieldDefinition $definition) use ($answers) {
            return $answers->get($definition->key)?->isAnswered() ?? false;
        })->count();

        $hasMeta = filled($consultation->clinicalExam?->synthesis) || filled($consultation->clinicalExam?->examined_at);

        return [
            'answered' => $answered + ($hasMeta ? 1 : 0),
            'total' => $total + 1,
            'percent' => $total > 0 ? (int) round((($answered + ($hasMeta ? 1 : 0)) / ($total + 1)) * 100) : 0,
        ];
    }

    public function hasData(Consultation $consultation): bool
    {
        $consultation->loadMissing(['clinicalExam', 'clinicalExamValues']);

        if (filled($consultation->clinicalExam?->synthesis) || filled($consultation->clinicalExam?->examined_at)) {
            return true;
        }

        return $consultation->clinicalExamValues->contains(fn (ConsultationClinicalExamValue $value) => $value->isAnswered());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function validationRules(): array
    {
        $rules = [
            'clinicalExamMeta.examined_at' => ['nullable', 'date'],
            'clinicalExamMeta.synthesis' => ['nullable', 'string', 'max:2000'],
            'clinicalExamForm' => ['nullable', 'array'],
        ];

        foreach ($this->definitions() as $definition) {
            $key = $definition->key;
            $rules["clinicalExamForm.{$key}"] = ['nullable', 'array'];
            $rules["clinicalExamForm.{$key}.present"] = ['nullable', 'in:0,1'];
            $rules["clinicalExamForm.{$key}.value_text"] = ['nullable', 'string', 'max:1000'];
            $rules["clinicalExamForm.{$key}.value_number"] = ['nullable', 'numeric', 'min:0'];
            $rules["clinicalExamForm.{$key}.note"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /**
     * @param  array{examined_at?: ?string, synthesis?: ?string, fields?: array<string, array<string, mixed>>}  $form
     */
    public function sync(Consultation $consultation, array $form): void
    {
        $definitions = $this->definitions()->keyBy('key');
        $fields = $form['fields'] ?? $form;

        DB::transaction(function () use ($consultation, $form, $definitions, $fields) {
            ConsultationClinicalExam::query()->updateOrCreate(
                ['consultation_id' => $consultation->id],
                [
                    'examined_at' => filled($form['examined_at'] ?? null) ? $form['examined_at'] : null,
                    'synthesis' => filled($form['synthesis'] ?? null) ? $form['synthesis'] : null,
                    'filled_by_user_id' => auth()->id(),
                ],
            );

            foreach ($definitions as $key => $definition) {
                $entry = $fields[$key] ?? [];
                $payload = $this->normalizeEntry($definition, $entry);

                if ($payload === null) {
                    ConsultationClinicalExamValue::query()
                        ->where('consultation_id', $consultation->id)
                        ->where('clinical_exam_field_definition_id', $definition->id)
                        ->delete();

                    continue;
                }

                ConsultationClinicalExamValue::query()->updateOrCreate(
                    [
                        'consultation_id' => $consultation->id,
                        'clinical_exam_field_definition_id' => $definition->id,
                    ],
                    $payload,
                );
            }

            $consultation->update([
                'examen_clinique' => $this->buildTextSummary($consultation->fresh(['clinicalExam', 'clinicalExamValues.definition'])),
            ]);
        });
    }

    public function toTextSummary(Consultation $consultation): string
    {
        $consultation->loadMissing(['clinicalExam.filledBy', 'clinicalExamValues.definition']);

        return $this->buildTextSummary($consultation);
    }

    private function buildTextSummary(Consultation $consultation): string
    {
        $lines = [];
        $exam = $consultation->clinicalExam;

        if ($exam?->examined_at) {
            $lines[] = 'Date examen : ' . $exam->examined_at->format('d/m/Y');
        }

        foreach ($this->sections() as $section) {
            $sectionLines = [];

            foreach ($section['fields'] as $definition) {
                $answer = $consultation->clinicalExamValues
                    ->firstWhere('clinical_exam_field_definition_id', $definition->id);

                if (! $answer?->isAnswered()) {
                    continue;
                }

                $sectionLines[] = '- ' . $definition->label . ' : ' . $answer->displaySummary();
            }

            if ($sectionLines !== []) {
                $lines[] = '';
                $lines[] = '=== ' . strtoupper($section['label']) . ' ===';
                array_push($lines, ...$sectionLines);
            }
        }

        if (filled($exam?->synthesis)) {
            $lines[] = '';
            $lines[] = '=== SYNTHÈSE MÉDICALE ===';
            $lines[] = $exam->synthesis;
        }

        if ($exam?->filledBy) {
            $lines[] = '';
            $lines[] = 'Rempli par : ' . $exam->filledBy->name;
        }

        return trim(implode("\n", $lines));
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>|null
     */
    private function normalizeEntry(ClinicalExamFieldDefinition $definition, array $entry): ?array
    {
        return match ($definition->field_type) {
            ClinicalExamFieldType::Boolean => $this->normalizeBoolean($entry),
            ClinicalExamFieldType::BooleanWithNote => $this->normalizeBooleanWithNote($entry),
            ClinicalExamFieldType::Text => filled($entry['value_text'] ?? null)
                ? ['present' => null, 'value_text' => (string) $entry['value_text'], 'value_number' => null, 'note' => null]
                : null,
            ClinicalExamFieldType::Number => $this->normalizeNumber($entry),
        };
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>|null
     */
    private function normalizeBoolean(array $entry): ?array
    {
        $presentRaw = $entry['present'] ?? null;

        if ($presentRaw === null || $presentRaw === '') {
            return null;
        }

        return [
            'present' => in_array($presentRaw, [1, '1', true], true),
            'value_text' => null,
            'value_number' => null,
            'note' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>|null
     */
    private function normalizeBooleanWithNote(array $entry): ?array
    {
        $presentRaw = $entry['present'] ?? null;

        if ($presentRaw === null || $presentRaw === '') {
            return null;
        }

        $present = in_array($presentRaw, [1, '1', true], true);

        return [
            'present' => $present,
            'value_text' => null,
            'value_number' => null,
            'note' => $present && filled($entry['note'] ?? null) ? (string) $entry['note'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>|null
     */
    private function normalizeNumber(array $entry): ?array
    {
        if (filled($entry['value_number'] ?? null)) {
            return [
                'present' => null,
                'value_text' => null,
                'value_number' => (float) $entry['value_number'],
                'note' => null,
            ];
        }

        if (filled($entry['value_text'] ?? null)) {
            return [
                'present' => null,
                'value_text' => (string) $entry['value_text'],
                'value_number' => null,
                'note' => null,
            ];
        }

        return null;
    }
}
