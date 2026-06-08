<?php

namespace App\Services;

use App\Models\Configs\Acte;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;

class ConsultationImportCreator
{
    public function create(array $attributes): Consultation
    {
        $acteIds = $attributes['acte_ids'] ?? [];
        $teamUserIds = $attributes['team_user_ids'] ?? [];
        $createdAt = $attributes['created_at'] ?? null;
        $useProjectPeriod = (bool) ($attributes['use_project_period'] ?? false);

        unset($attributes['acte_ids'], $attributes['team_user_ids'], $attributes['created_at'], $attributes['use_project_period']);

        return DB::transaction(function () use ($attributes, $acteIds, $teamUserIds, $createdAt, $useProjectPeriod) {
            $selectedActes = Acte::query()
                ->with('departement')
                ->whereIn('id', $acteIds)
                ->get()
                ->keyBy('id');

            $consultation = Consultation::createWithPeriodContext(
                $attributes,
                ['use_project_period' => $useProjectPeriod],
            );

            $attachData = collect($acteIds)
                ->mapWithKeys(function ($acteId) use ($selectedActes) {
                    $acte = $selectedActes->get((int) $acteId);

                    return [
                        $acteId => [
                            'ref' => $acte?->departement?->ref ?? 'GEN',
                            'montant' => (float) ($acte?->montant ?? 0),
                        ],
                    ];
                })
                ->toArray();

            $consultation->actes()->sync($attachData);

            if ($teamUserIds !== []) {
                $consultation->users()->sync($teamUserIds);
            }

            $facturationId = DB::table('facturations')->insertGetId([
                'consultation_id' => $consultation->id,
                'dossier_patient_id' => $consultation->dossier_patient_id,
                'hopital_id' => $consultation->hopital_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $consultation->update(['facturation_id' => $facturationId]);

            if ($createdAt) {
                $consultation->update(['created_at' => $createdAt, 'updated_at' => $createdAt]);
            }

            return $consultation->fresh();
        });
    }
}
