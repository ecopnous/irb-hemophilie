<?php

namespace App\Services;

use App\Models\Consultation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PatientEvolutionContextBuilder
{
    public function __construct(
        private readonly PatientEvolutionMetricsService $metricsService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function build(int $patientId, array $filters = []): string
    {
        $metrics = $this->metricsService->dashboard($patientId, $filters);
        $consultations = $this->loadConsultations($patientId, $filters);

        return $this->format($metrics, $consultations);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Consultation>
     */
    private function loadConsultations(int $patientId, array $filters): Collection
    {
        return $this->metricsService->consultationsForAnalysis($patientId, $filters);
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  Collection<int, Consultation>  $consultations
     */
    private function format(array $metrics, Collection $consultations): string
    {
        $patient = $metrics['patient'];
        $kpi = $metrics['kpis'];
        $lines = [];

        $lines[] = '=== DOSSIER PATIENT ===';
        $lines[] = 'Nom : ' . ($patient['full_name'] ?? '-');
        $lines[] = 'NIN : ' . ($patient['nin'] ?? '-');
        $lines[] = 'Âge : ' . ($patient['age'] ?? '-');
        $lines[] = 'Genre : ' . ($patient['genre'] ?? '-');
        $lines[] = 'Période analysée : ' . ($metrics['periodLabel'] ?? '-');
        $lines[] = '';

        $lines[] = '=== INDICATEURS DE SUIVI ===';
        $lines[] = 'Total consultations : ' . ($kpi['total_consultations'] ?? 0);
        $lines[] = 'Première visite : ' . ($kpi['first_visit'] ?? '-');
        $lines[] = 'Dernière visite : ' . ($kpi['last_visit'] ?? '-');
        $lines[] = 'Jours depuis dernière visite : ' . ($kpi['days_since_last_visit'] ?? '-');
        $lines[] = 'Hospitalisations : ' . ($kpi['hospitalizations'] ?? 0);
        $lines[] = 'Examens laboratoire : ' . ($kpi['lab_exams'] ?? 0);
        $lines[] = 'Imagerie : ' . ($kpi['imaging_exams'] ?? 0);
        $lines[] = 'Prescriptions : ' . ($kpi['prescriptions'] ?? 0);
        $lines[] = 'Poids actuel (kg) : ' . ($kpi['latest_weight'] ?? '-');
        $lines[] = 'Tension SYS (mmHg) : ' . ($kpi['latest_systolic'] ?? '-');
        $lines[] = 'Température (°C) : ' . ($kpi['latest_temperature'] ?? '-');
        $lines[] = 'Glycémie : ' . ($kpi['latest_glycemia'] ?? '-');
        $lines[] = '';

        if (! empty($metrics['insights']['trends'])) {
            $lines[] = '=== TENDANCES DÉTECTÉES (SYSTÈME) ===';
            foreach ($metrics['insights']['trends'] as $trend) {
                $lines[] = '- ' . $trend;
            }
            $lines[] = '';
        }

        if (! empty($metrics['insights']['alerts'])) {
            $lines[] = '=== ALERTES SYSTÈME ===';
            foreach ($metrics['insights']['alerts'] as $alert) {
                $lines[] = '- ' . $alert;
            }
            $lines[] = '';
        }

        if (! empty($metrics['comparison']['rows'])) {
            $lines[] = '=== COMPARAISON CONSTANTES (' . ($metrics['comparison']['label'] ?? '') . ') ===';
            $lines[] = 'Du ' . ($metrics['comparison']['first_date'] ?? '-') . ' au ' . ($metrics['comparison']['last_date'] ?? '-');
            foreach ($metrics['comparison']['rows'] as $row) {
                $delta = $row['delta'] !== null ? (($row['delta'] > 0 ? '+' : '') . $row['delta']) : 'N/A';
                $lines[] = sprintf(
                    '- %s : %s → %s (Δ %s)',
                    $row['metric'],
                    $row['first'] ?? '—',
                    $row['last'] ?? '—',
                    $delta,
                );
            }
            $lines[] = '';
        }

        if (! empty($metrics['clinical']['top_diagnostics'])) {
            $lines[] = '=== DIAGNOSTICS FRÉQUENTS ===';
            foreach ($metrics['clinical']['top_diagnostics'] as $diag) {
                $lines[] = '- ' . $diag['label'] . ' (' . $diag['value'] . ' fois)';
            }
            $lines[] = '';
        }

        if (! empty($metrics['exams']['top_actes'])) {
            $lines[] = '=== ACTES / EXAMENS FRÉQUENTS ===';
            foreach ($metrics['exams']['top_actes'] as $acte) {
                $lines[] = '- ' . $acte['label'] . ' (' . $acte['value'] . ' fois)';
            }
            $lines[] = '';
        }

        $lines[] = '=== HISTORIQUE DÉTAILLÉ DES CONSULTATIONS ===';
        $lines[] = 'Nombre de consultations transmises : ' . $consultations->count();
        $lines[] = '';

        foreach ($consultations as $consultation) {
            $lines[] = $this->formatConsultation($consultation);
            $lines[] = '---';
        }

        return implode("\n", $lines);
    }

    private function formatConsultation(Consultation $consultation): string
    {
        $fields = [
            'Date' => $consultation->created_at?->format('d/m/Y H:i'),
            'Référence' => $consultation->reference,
            'Type' => $consultation->type === 'depistage' ? 'Dépistage / Urgence' : 'Consultation',
            'Service' => $consultation->departement?->name,
            'Médecin' => $consultation->user?->name,
            'Statut' => $consultation->is_clore ? 'Clôturée' : 'Ouverte',
            'Symptômes' => $consultation->symptomes,
            'Antécédents' => $consultation->antecedents,
            'Allergies' => $consultation->allergies,
            'Histoire de la maladie' => $consultation->histoire_maladie,
            'Examen clinique' => $consultation->examen_clinique,
            'Complément anamnèse' => $consultation->complement_anamnese,
            'Diagnostic présomption' => $consultation->diagnostic_presomption,
            'Diagnostic certitude' => $consultation->diagnostic_certitude,
            'Plan de traitement / conduite' => $consultation->plan_traitement_conduite,
            'Prescription médicale' => $consultation->prescription_medicale,
            'Issue' => $consultation->issue,
            'Cause issue' => $consultation->cause_issue,
            'Poids (kg)' => $consultation->poids,
            'Taille (cm)' => $consultation->taille,
            'Température (°C)' => $consultation->temperature,
            'Tension SYS/DIA' => filled($consultation->systolite)
                ? $consultation->systolite . '/' . ($consultation->diastolique ?? '-')
                : null,
            'FC (bpm)' => $consultation->frequence_cardiaque,
            'FR (/min)' => $consultation->frequence_respiratoire,
            'SpO2 (%)' => $consultation->saturation_oxygene,
            'Glycémie' => $consultation->glycemie,
        ];

        $block = ['[Consultation]'];
        foreach ($fields as $label => $value) {
            if (filled($value)) {
                $block[] = $label . ' : ' . Str::limit(strip_tags((string) $value), 2000);
            }
        }

        return implode("\n", $block);
    }
}
