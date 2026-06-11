<?php

namespace App\Services;

use App\Models\Configs\Assurance;
use App\Models\Configs\Hopital;
use App\Models\Configs\Projet;
use App\Models\Consultation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AssuranceInvoiceService
{
    public function build(
        Assurance $assurance,
        ?int $hopitalId,
        ?Carbon $periodStart = null,
        ?Carbon $periodEnd = null,
        ?int $projetId = null,
    ): array {
        $periodStart ??= now()->startOfMonth();
        $periodEnd ??= now()->endOfMonth();

        $assurance->loadMissing('categorisation');

        $consultations = Consultation::query()
            ->with([
                'dossierPatient',
                'projet',
                'departement',
                'actes.departement',
            ])
            ->where('assurance_id', $assurance->id)
            ->when($hopitalId, fn ($query) => $query->whereHopitalId($hopitalId))
            ->when($projetId, fn ($query) => $query->where('projet_id', $projetId))
            ->whereBetween('created_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->whereHas('actes')
            ->orderBy('created_at')
            ->get();

        $defaultCoverage = (float) ($assurance->categorisation?->pourcentage ?? 0);
        $projets = [];
        $patientIds = [];
        $totalGeneral = 0.0;
        $totalForfaitise = 0.0;
        $totalAPayer = 0.0;

        foreach ($consultations->groupBy(fn (Consultation $consultation) => $consultation->projet_id ?: 0) as $groupProjetId => $projetConsultations) {
            $projet = $groupProjetId > 0
                ? $projetConsultations->first()?->projet
                : null;

            $projetKey = (string) $groupProjetId;
            $projets[$projetKey] = [
                'id' => $projet?->id,
                'name' => $projet?->name ?? 'Sans projet',
                'reference' => $projet?->reference ?? '—',
                'patients' => [],
                'total' => 0.0,
                'a_payer' => 0.0,
            ];

            foreach ($projetConsultations->groupBy('dossier_patient_id') as $patientConsultations) {
                $patient = $patientConsultations->first()?->dossierPatient;
                if (! $patient) {
                    continue;
                }

                $patientIds[$patient->id] = true;
                $lines = [];
                $patientTotal = 0.0;
                $patientAPayer = 0.0;

                foreach ($patientConsultations as $consultation) {
                    foreach ($consultation->actes as $acte) {
                        $montant = (float) ($acte->pivot->montant ?? $acte->montant ?? 0);
                        $coverage = (float) ($acte->pivot->prise_en_charge ?? 0);
                        if ($coverage <= 0) {
                            $coverage = $defaultCoverage;
                        }

                        $forfaitise = $coverage >= 100;
                        $montantAssurance = $forfaitise
                            ? $montant
                            : round($montant * ($coverage / 100), 2);

                        $patientTotal += $montant;
                        $patientAPayer += $montantAssurance;
                        $totalGeneral += $montant;
                        $totalForfaitise += $montantAssurance;
                        $totalAPayer += $montantAssurance;

                        $lines[] = [
                            'reference' => $acte->pivot->ref ?: ($consultation->reference ?: $acte->code ?: '—'),
                            'consultation_reference' => $consultation->reference,
                            'acte' => $acte->name,
                            'departement' => $acte->departement?->name ?? $consultation->departement?->name ?? '—',
                            'date' => $consultation->created_at,
                            'prix' => $montant,
                            'forfaitise' => $forfaitise,
                            'coverage' => $coverage,
                            'a_payer' => $montantAssurance,
                        ];
                    }
                }

                $projets[$projetKey]['patients'][] = [
                    'id' => $patient->id,
                    'reference' => $patient->nin ?: $patient->ins ?: ('P-' . $patient->id),
                    'name' => trim(implode(' ', array_filter([
                        strtoupper((string) $patient->nom),
                        strtoupper((string) $patient->postnom),
                        ucfirst((string) $patient->prenom),
                    ]))),
                    'lines' => $lines,
                    'total' => $patientTotal,
                    'a_payer' => $patientAPayer,
                ];

                $projets[$projetKey]['total'] += $patientTotal;
                $projets[$projetKey]['a_payer'] += $patientAPayer;
            }
        }

        $patientsCount = count($patientIds);
        $prixPatient = (float) ($assurance->prix_patient ?? 0);
        $estimationForfait = $assurance->forfait_actif && $prixPatient > 0
            ? round($prixPatient * $patientsCount, 2)
            : 0.0;

        if ($estimationForfait > 0) {
            $totalAPayer = max($totalAPayer, $estimationForfait);
        }

        return [
            'assurance' => $assurance,
            'period' => [
                'start' => $periodStart->copy(),
                'end' => $periodEnd->copy(),
                'label' => $periodStart->translatedFormat('F Y'),
            ],
            'meta' => [
                'forfait_disponible' => (bool) $assurance->forfait_actif,
                'categorie' => $assurance->categorisation?->name ?? 'N/A',
                'pourcentage' => $defaultCoverage,
                'patients_count' => $patientsCount,
                'consultations_count' => $consultations->count(),
                'prix_patient' => $prixPatient,
                'estimation_forfait' => $estimationForfait,
            ],
            'projets' => collect($projets)->values()->all(),
            'totals' => [
                'general' => round($totalGeneral, 2),
                'forfaitise' => round($totalForfaitise, 2),
                'a_payer' => round($totalAPayer, 2),
            ],
        ];
    }

    public function statsForAssurance(Assurance $assurance, ?int $hopitalId, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $periodStart ??= now()->startOfMonth();
        $periodEnd ??= now()->endOfMonth();

        $consultations = Consultation::query()
            ->where('assurance_id', $assurance->id)
            ->when($hopitalId, fn ($query) => $query->whereHopitalId($hopitalId))
            ->whereBetween('created_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->whereHas('actes')
            ->with('actes')
            ->get();

        $montant = 0.0;
        $patientIds = [];

        foreach ($consultations as $consultation) {
            $patientIds[$consultation->dossier_patient_id] = true;
            foreach ($consultation->actes as $acte) {
                $montant += (float) ($acte->pivot->montant ?? $acte->montant ?? 0);
            }
        }

        return [
            'consultations' => $consultations->count(),
            'patients' => count($patientIds),
            'montant' => round($montant, 2),
        ];
    }

    public function hopitalHeader(?int $hopitalId): array
    {
        $hopital = $hopitalId
            ? Hopital::query()->find($hopitalId)
            : null;

        $address = collect([
            trim(implode(' ', array_filter([$hopital?->avenue, $hopital?->numero]))),
            $hopital?->quartier,
            $hopital?->code_postal,
        ])->filter()->implode(', ');

        return [
            'name' => strtoupper((string) ($hopital?->name ?? current_hopital_nom() ?? 'Établissement')),
            'address' => $address ?: 'Adresse non renseignée',
            'reference' => $hopital?->reference ?? '—',
            'site_web' => $hopital?->site_web,
            'devise' => $hopital?->devise ?? 'USD',
        ];
    }

    public function availableProjets(Assurance $assurance, ?int $hopitalId): Collection
    {
        return Projet::query()
            ->where('assurance_id', $assurance->id)
            ->where(function ($query) {
                $query->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->when($hopitalId, function ($query) use ($assurance, $hopitalId) {
                $query->whereHas('consultations', function ($consultationQuery) use ($hopitalId) {
                    $consultationQuery->whereHopitalId($hopitalId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'reference']);
    }
}
