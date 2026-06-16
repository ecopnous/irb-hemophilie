<?php

namespace App\Services;

use App\Models\Consultation;
use App\Services\Consultation\ClinicalExamService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConsultationContextBuilder
{
    public function __construct(
        private readonly ClinicalExamService $clinicalExamService,
    ) {}

    /**
     * @return array{context: string, has_data: bool}
     */
    public function build(Consultation $consultation): array
    {
        $consultation->loadMissing([
            'dossierPatient',
            'departement',
            'service',
            'user',
            'projet',
            'symptomeItems',
            'laboratoire',
            'imagerie',
            'prescription.medicaments',
            'clinicalExam',
            'clinicalExamValues.definition',
            'actes' => fn ($query) => $query->withPivot('resultat', 'note_clinique', 'commentaire', 'valide'),
        ]);

        $patient = $consultation->dossierPatient;
        $recentConsultations = $this->recentConsultations($consultation);
        $hasData = $this->hasClinicalData($consultation);

        $lines = [];

        $lines[] = '=== CONSULTATION EN COURS ===';
        $lines[] = 'Référence : ' . ($consultation->reference ?? '-');
        $lines[] = 'Date : ' . ($consultation->created_at?->format('d/m/Y H:i') ?? '-');
        $lines[] = 'Type : ' . ($consultation->type === 'depistage' ? 'Dépistage / Urgence' : 'Consultation');
        $lines[] = 'Type de fiche : ' . ($consultation->type_visite ?? '-');
        $lines[] = 'Service : ' . ($consultation->departement?->name ?? '-');
        $lines[] = 'Médecin : ' . ($consultation->user?->name ?? '-');
        $lines[] = 'Projet : ' . ($consultation->projet?->name ?? '-');
        $lines[] = 'Statut : ' . ($consultation->is_clore ? 'Clôturée' : 'En cours');
        $lines[] = '';

        if ($patient) {
            $lines[] = '=== PATIENT ===';
            $lines[] = 'Nom : ' . $patient->full_name;
            $lines[] = 'NIN : ' . ($patient->nin ?? '-');
            $lines[] = 'Âge : ' . ($patient->age ?? '-');
            $lines[] = 'Genre : ' . ($patient->genre ?? '-');
            $this->appendIfFilled($lines, 'Antécédents médicaux', $patient->antecedents_medicales);
            $this->appendIfFilled($lines, 'Antécédents chirurgicaux', $patient->antecedents_chirurgicaux);
            $this->appendIfFilled($lines, 'Antécédents familiaux', $patient->antecedents_familiaux);
            $this->appendIfFilled($lines, 'Antécédents hématologiques', $patient->antecedents_hematologiques);
            $this->appendIfFilled($lines, 'Antécédents cardiovasculaires', $patient->antecedents_cardiovasculaires);
            $this->appendIfFilled($lines, 'Antécédents neurologiques', $patient->antecedents_neurologiques);
            $this->appendIfFilled($lines, 'Antécédents supplémentaires', $patient->antecedents_supplementaires);
            $lines[] = '';
        }

        $lines[] = '=== CONSTANTES VITALES ===';
        $this->appendIfFilled($lines, 'Poids (kg)', $consultation->poids);
        $this->appendIfFilled($lines, 'Taille (cm)', $consultation->taille);
        $this->appendIfFilled($lines, 'Température (°C)', $consultation->temperature);
        if (filled($consultation->systolite)) {
            $lines[] = 'Tension artérielle : ' . $consultation->systolite . '/' . ($consultation->diastolique ?? '-');
        }
        $this->appendIfFilled($lines, 'FC (bpm)', $consultation->frequence_cardiaque);
        $this->appendIfFilled($lines, 'FR (/min)', $consultation->frequence_respiratoire);
        $this->appendIfFilled($lines, 'SpO2 (%)', $consultation->saturation_oxygene);
        $this->appendIfFilled($lines, 'Glycémie', $consultation->glycemie);
        $this->appendIfFilled($lines, 'Périmètre crânien', $consultation->perimetre_cranien);
        $this->appendIfFilled($lines, 'Périmètre brachial', $consultation->perimetre_brachial);
        $lines[] = '';

        $lines[] = '=== DONNÉES CLINIQUES DE LA CONSULTATION ===';

        if ($consultation->symptomeItems->isNotEmpty()) {
            $lines[] = 'Symptômes sélectionnés : ' . $consultation->symptomeItems->pluck('name')->implode(', ');
        }

        $this->appendIfFilled($lines, 'Symptômes (texte)', $consultation->symptomes);
        $this->appendIfFilled($lines, 'Antécédents (consultation)', $consultation->antecedents);
        $this->appendIfFilled($lines, 'Allergies', $consultation->allergies);
        $this->appendIfFilled($lines, 'Histoire de la maladie', $consultation->histoire_maladie);
        $this->appendIfFilled($lines, 'Complément anamnèse', $consultation->complement_anamnese);

        $clinicalExamSummary = $this->clinicalExamService->toTextSummary($consultation);
        if (filled($clinicalExamSummary)) {
            $lines[] = '';
            $lines[] = '=== EXAMEN CLINIQUE STRUCTURÉ ===';
            $lines[] = $clinicalExamSummary;
        } else {
            $this->appendIfFilled($lines, 'Examen clinique (texte libre)', $consultation->examen_clinique);
        }

        $this->appendIfFilled($lines, 'Diagnostic de présomption (déjà saisi)', $consultation->diagnostic_presomption);
        $this->appendIfFilled($lines, 'Diagnostic de certitude (déjà saisi)', $consultation->diagnostic_certitude);
        $this->appendIfFilled($lines, 'Plan de traitement / conduite (déjà saisi)', $consultation->plan_traitement_conduite);
        $this->appendIfFilled($lines, 'Prescription médicale', $consultation->prescription_medicale);
        $lines[] = '';

        if ($consultation->laboratoire || $consultation->actes->isNotEmpty()) {
            $lines[] = '=== EXAMENS LABORATOIRE ===';
            if ($consultation->laboratoire) {
                $this->appendIfFilled($lines, 'Renseignement labo', $consultation->laboratoire->renseignement);
                $this->appendIfFilled($lines, 'Note labo', $consultation->laboratoire->note);
                $this->appendIfFilled($lines, 'Commentaire labo', $consultation->laboratoire->commentaire);
            }
            foreach ($consultation->actes as $acte) {
                $pivot = $acte->pivot;
                $resultParts = array_filter([
                    $acte->name,
                    filled($pivot->resultat) ? 'Résultat : ' . $pivot->resultat : null,
                    filled($pivot->note_clinique) ? 'Note : ' . $pivot->note_clinique : null,
                    filled($pivot->commentaire) ? 'Commentaire : ' . $pivot->commentaire : null,
                ]);
                if ($resultParts !== []) {
                    $lines[] = '- ' . implode(' | ', $resultParts);
                }
            }
            $lines[] = '';
        }

        if ($consultation->imagerie) {
            $lines[] = '=== IMAGERIE ===';
            $this->appendIfFilled($lines, 'Renseignement', $consultation->imagerie->renseignement);
            $this->appendIfFilled($lines, 'Note', $consultation->imagerie->note);
            $lines[] = '';
        }

        if ($consultation->prescription?->medicaments?->isNotEmpty()) {
            $lines[] = '=== PRESCRIPTION EN COURS ===';
            foreach ($consultation->prescription->medicaments as $medicament) {
                $lines[] = '- ' . ($medicament->name ?? 'Médicament') . ' (qté : ' . ($medicament->pivot->quantiter ?? '-') . ')';
            }
            $lines[] = '';
        }

        if ($recentConsultations->isNotEmpty()) {
            $lines[] = '=== HISTORIQUE RÉCENT DU PATIENT (contexte) ===';
            foreach ($recentConsultations as $past) {
                $lines[] = sprintf(
                    '- %s | %s | Présomption : %s | Certitude : %s',
                    $past->created_at?->format('d/m/Y') ?? '-',
                    $past->departement?->name ?? '-',
                    Str::limit((string) ($past->diagnostic_presomption ?: '—'), 80),
                    Str::limit((string) ($past->diagnostic_certitude ?: '—'), 80),
                );
            }
        }

        return [
            'context' => implode("\n", $lines),
            'has_data' => $hasData,
        ];
    }

    /**
     * @return Collection<int, Consultation>
     */
    private function recentConsultations(Consultation $consultation, int $limit = 5): Collection
    {
        return Consultation::query()
            ->where('dossier_patient_id', $consultation->dossier_patient_id)
            ->where('id', '!=', $consultation->id)
            ->where('is_visite_program', false)
            ->with('departement:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();
    }

    private function hasClinicalData(Consultation $consultation): bool
    {
        return $this->clinicalExamService->hasData($consultation)
            || $consultation->symptomeItems->isNotEmpty()
            || filled($consultation->symptomes)
            || filled($consultation->antecedents)
            || filled($consultation->allergies)
            || filled($consultation->histoire_maladie)
            || filled($consultation->complement_anamnese)
            || filled($consultation->examen_clinique)
            || filled($consultation->poids)
            || filled($consultation->temperature)
            || filled($consultation->systolite)
            || filled($consultation->diagnostic_presomption);
    }

    /**
     * @param  list<string>  $lines
     */
    private function appendIfFilled(array &$lines, string $label, mixed $value): void
    {
        if (filled($value)) {
            $lines[] = $label . ' : ' . Str::limit(strip_tags((string) $value), 3000);
        }
    }
}
