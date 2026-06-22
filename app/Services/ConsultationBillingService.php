<?php

namespace App\Services;

use App\Models\Configs\Assurance;
use App\Models\Consultation;
use Illuminate\Support\Collection;

class ConsultationBillingService
{
    public function resolveAssurance(Consultation $consultation): ?Assurance
    {
        $consultation->loadMissing(['projet.assurance.categorisation', 'assurance.categorisation']);

        return $consultation->projet?->assurance ?? $consultation->assurance;
    }

    public function defaultCoverageRate(Consultation $consultation): float
    {
        return (float) ($this->resolveAssurance($consultation)?->categorisation?->pourcentage ?? 0);
    }

    public function coverageCategoryName(Consultation $consultation): string
    {
        return (string) ($this->resolveAssurance($consultation)?->categorisation?->name ?? 'N/A');
    }

    public function coverageLabel(Consultation $consultation): string
    {
        $consultation->loadMissing('projet');

        if ($consultation->projet) {
            return $consultation->projet->name;
        }

        $assurance = $this->resolveAssurance($consultation);

        return $assurance?->name ?? 'Paiement direct';
    }

    public function assuranceName(Consultation $consultation): string
    {
        return $this->resolveAssurance($consultation)?->name ?? 'Paiement direct';
    }

    public function hasCoverage(Consultation $consultation): bool
    {
        return $this->resolveAssurance($consultation) !== null;
    }

    /**
     * @return Collection<int, array{acte: mixed, amount: float, coverage: float, assurance_amount: float, patient_amount: float}>
     */
    public function billingLines(Consultation $consultation): Collection
    {
        $defaultCoverage = $this->defaultCoverageRate($consultation);
        $actes = $consultation->actes ?? collect();

        return $actes->map(function ($acte) use ($defaultCoverage) {
            $amount = (float) ($acte->pivot->montant ?? 0);
            $coverage = (float) ($acte->pivot->prise_en_charge ?? 0);

            if ($coverage <= 0) {
                $coverage = $defaultCoverage;
            }

            $coverage = max(0, min(100, $coverage));
            $assuranceAmount = round($amount * $coverage / 100, 2);
            $patientAmount = max(0, round($amount - $assuranceAmount, 2));

            return [
                'acte' => $acte,
                'amount' => $amount,
                'coverage' => $coverage,
                'assurance_amount' => $assuranceAmount,
                'patient_amount' => $patientAmount,
            ];
        });
    }

    /**
     * @return array{gross: float, assurance: float, patient: float}
     */
    public function totals(Consultation $consultation): array
    {
        $lines = $this->billingLines($consultation);

        return [
            'gross' => round((float) $lines->sum('amount'), 2),
            'assurance' => round((float) $lines->sum('assurance_amount'), 2),
            'patient' => round((float) $lines->sum('patient_amount'), 2),
        ];
    }
}
