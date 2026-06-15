<?php

namespace App\Services\Patient;

use App\Models\DossierPatient;
use App\Models\DossierPatientPremierSigne;
use App\Models\PremierSigneDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
class PremierSigneService
{
    /**
     * @return Collection<int, PremierSigneDefinition>
     */
    public function definitions(): Collection
    {
        return PremierSigneDefinition::query()->active()->get();
    }

    /**
     * @return array<string, array{present: bool|null, value: int|null, comment: string}>
     */
    public function toFormArray(DossierPatient $patient): array
    {
        $answers = $patient->premierSignes
            ->loadMissing('definition')
            ->keyBy(fn (DossierPatientPremierSigne $signe) => $signe->definition->key);

        $form = [];

        foreach ($this->definitions() as $definition) {
            $answer = $answers->get($definition->key);

            $form[$definition->key] = [
                'present' => $answer?->present === null ? null : ($answer->present ? 1 : 0),
                'value' => $answer?->value,
                'comment' => (string) ($answer?->comment ?? ''),
            ];
        }

        return $form;
    }

    /**
     * @return Collection<int, array{definition: PremierSigneDefinition, answer: DossierPatientPremierSigne|null}>
     */
    public function presentationRows(DossierPatient $patient): Collection
    {
        $answers = $patient->premierSignes
            ->loadMissing('definition')
            ->keyBy(fn (DossierPatientPremierSigne $signe) => $signe->definition->key);

        return $this->definitions()->map(fn (PremierSigneDefinition $definition) => [
            'definition' => $definition,
            'answer' => $answers->get($definition->key),
        ]);
    }

    public function isIncomplete(DossierPatient $patient): bool
    {
        $progress = $this->progress($patient);

        return $progress['answered'] < $progress['total'];
    }

    /**
     * @return array{answered: int, total: int, percent: int}
     */
    public function progress(DossierPatient $patient): array
    {
        $answers = $patient->premierSignes
            ->loadMissing('definition')
            ->keyBy(fn (DossierPatientPremierSigne $signe) => $signe->definition->key);

        $total = $this->definitions()->count();
        $answered = $this->definitions()->filter(function (PremierSigneDefinition $definition) use ($answers) {
            $answer = $answers->get($definition->key);

            return $answer?->isAnswered() ?? false;
        })->count();

        return [
            'answered' => $answered,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($answered / $total) * 100) : 0,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function validationRules(): array
    {
        $rules = [
            'premierSignesForm' => ['nullable', 'array'],
            'premier_signes_supplementaires' => ['nullable', 'string', 'max:1000'],
        ];

        foreach ($this->definitions() as $definition) {
            $key = $definition->key;
            $rules["premierSignesForm.{$key}"] = ['nullable', 'array'];
            $rules["premierSignesForm.{$key}.present"] = ['nullable', 'in:0,1'];
            $rules["premierSignesForm.{$key}.value"] = ['nullable', 'integer', 'min:0'];
            $rules["premierSignesForm.{$key}.comment"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * @param  array<string, array{present: bool|null, value: int|null, comment?: string|null}>  $form
     */
    public function sync(DossierPatient $patient, array $form): void
    {
        $definitions = $this->definitions()->keyBy('key');

        DB::transaction(function () use ($patient, $form, $definitions) {
            foreach ($definitions as $key => $definition) {
                $entry = $form[$key] ?? [];
                $presentRaw = $entry['present'] ?? null;

                if ($presentRaw === null || $presentRaw === '') {
                    continue;
                }

                $present = in_array($presentRaw, [1, '1', true], true);
                $value = filled($entry['value'] ?? null) ? (int) $entry['value'] : null;
                $comment = filled($entry['comment'] ?? null) ? (string) $entry['comment'] : null;

                DossierPatientPremierSigne::query()->updateOrCreate(
                    [
                        'dossier_patient_id' => $patient->id,
                        'premier_signe_definition_id' => $definition->id,
                    ],
                    [
                        'present' => $present,
                        'value' => $present ? $value : null,
                        'comment' => $comment,
                    ],
                );
            }
        });
    }
}
