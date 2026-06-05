<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use App\Models\Consultation;
use App\Models\Imagerie;
use App\Models\Laboratoire;
use App\Models\prescription\Medicament;
use App\Models\prescription\Prescription;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche de consultation')] class extends Component {
    public Consultation $consultation;
    public ?string $currentSection = null;

    public string $textValue = '';
    public ?string $issueValue = null;
    public string $autreIssue = '';
    public string $causeIssue = '';
    public array $vitalsForm = [];
    public array $laboratoireForm = [];
    public array $imagerieForm = [];
    public array $laboratoireActeIds = [];
    public array $imagerieActeIds = [];
    public ?int $selectedLaboratoireActeId = null;
    public ?int $pendingDeleteLaboratoireActeId = null;
    public ?string $selectedLaboratoireActeName = null;
    public string $laboratoireActeNote = '';
    public string $prescriptionNote = '';
    public ?int $prescriptionMedicamentId = null;
    public ?int $prescriptionQty = 1;
    public ?string $prescriptionPosologie = null;
    public ?int $rendezVousEditId = null;
    public ?int $pendingDeleteRendezVousId = null;
    public string $rendezVousDate = '';
    public string $rendezVousHeure = '08:00';
    public string $rendezVousMotif = '';
    public array $symptome_ids = [];

    public function mount(int $id): void
    {
        $this->loadConsultation($id);
    }

    public function loadConsultation(int $id): void
    {
        $this->consultation = Consultation::query()
            ->with(['dossierPatient', 'departement', 'service', 'user', 'assurance', 'projet', 'laboratoire', 'imagerie', 'prescription.medicaments', 'actes.departement', 'actes.service', 'consultationSource', 'symptomeItems'])
            ->findOrFail($id);

        $this->syncIssueForm();
    }

    public function syncIssueForm(): void
    {
        $this->issueValue = $this->consultation->issue;
        $this->autreIssue = (string) ($this->consultation->autre_issue ?? '');
        $this->causeIssue = (string) ($this->consultation->cause_issue ?? '');
    }

    public function issueOptions(): array
    {
        return [['label' => 'Ambulatoire', 'value' => 'ambulatoire'], ['label' => 'Hospitalisation', 'value' => 'hospitalisation'], ['label' => 'Suivi medical / Referencement', 'value' => 'suivi_medical'], ['label' => 'Transfert', 'value' => 'transfert'], ['label' => 'Deces', 'value' => 'deces'], ['label' => 'Autres', 'value' => 'autres']];
    }

    public function issueLabel(?string $issue = null): ?string
    {
        $issue ??= $this->consultation->issue;

        if (!$issue) {
            return null;
        }

        return collect($this->issueOptions())->firstWhere('value', $issue)['label'] ?? ucfirst($issue);
    }

    public function issueRequiresAutre(): bool
    {
        return $this->issueValue === 'autres';
    }

    public function canCloseConsultation(): bool
    {
        if ($this->consultation->is_clore || !$this->consultation->issue) {
            return false;
        }

        if ($this->consultation->issue === 'autres') {
            return filled($this->consultation->autre_issue);
        }

        return true;
    }

    public function saveIssue(): void
    {
        if ($this->consultation->is_clore) {
            return;
        }

        $validated = $this->validate($this->issueValidationRules());

        $this->consultation->update([
            'issue' => $validated['issueValue'],
            'autre_issue' => $validated['issueValue'] === 'autres' ? ($validated['autreIssue'] ?: null) : null,
            'cause_issue' => $validated['causeIssue'] ?: null,
        ]);

        $this->loadConsultation($this->consultation->id);
        $this->dispatch('issue-saved');
    }

    public function confirmCloseConsultation(): void
    {
        $this->validate($this->issueValidationRules());
        $this->dispatch('consultation-close-open');
    }

    public function closeConsultation(): void
    {
        if ($this->consultation->is_clore) {
            return;
        }

        $validated = $this->validate($this->issueValidationRules());

        $this->consultation->update([
            'issue' => $validated['issueValue'],
            'autre_issue' => $validated['issueValue'] === 'autres' ? ($validated['autreIssue'] ?: null) : null,
            'cause_issue' => $validated['causeIssue'] ?: null,
            'is_clore' => true,
        ]);

        $this->loadConsultation($this->consultation->id);
        $this->dispatch('consultation-closed');
    }

    protected function issueValidationRules(): array
    {
        return [
            'issueValue' => ['required', 'in:ambulatoire,hospitalisation,suivi_medical,transfert,deces,autres'],
            'autreIssue' => ['required_if:issueValue,autres', 'nullable', 'string', 'max:255'],
            'causeIssue' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function openEditor(string $section): void
    {
        $this->resetValidation();
        $this->currentSection = $section;
        $this->textValue = '';
        $this->prescriptionNote = (string) ($this->consultation->prescription_medicale ?? '');
        $this->prescriptionMedicamentId = null;
        $this->prescriptionQty = 1;
        $this->prescriptionPosologie = null;

        $this->vitalsForm = [
            'poids' => $this->consultation->poids,
            'temperature' => $this->consultation->temperature,
            'taille' => $this->consultation->taille,
            'systolite' => $this->consultation->systolite,
            'diastolique' => $this->consultation->diastolique,
            'frequence_cardiaque' => $this->consultation->frequence_cardiaque,
            'frequence_respiratoire' => $this->consultation->frequence_respiratoire,
            'saturation_oxygene' => $this->consultation->saturation_oxygene,
            'glycemie' => $this->consultation->glycemie,
            'perimetre_cranien' => $this->consultation->perimetre_cranien,
            'perimetre_brachial' => $this->consultation->perimetre_brachial,
            'mois' => $this->consultation->mois,
        ];

        $this->laboratoireForm = [
            'renseignement' => (string) ($this->consultation->laboratoire?->renseignement ?? ''),
            'note' => (string) ($this->consultation->laboratoire?->note ?? ''),
            'antibiotique' => (string) ($this->consultation->laboratoire?->antibiotique ?? ''),
            'commentaire' => (string) ($this->consultation->laboratoire?->commentaire ?? ''),
            'hopital_id' => current_hopital_id(),
        ];
        $this->laboratoireActeIds = $this->laboratoireActes()->pluck('id')->map(fn($id) => (string) $id)->all();

        $this->imagerieForm = [
            'renseignement' => (string) ($this->consultation->imagerie?->renseignement ?? ''),
            'note' => (string) ($this->consultation->imagerie?->note ?? ''),
            'antibiotique' => (string) ($this->consultation->imagerie?->antibiotique ?? ''),
        ];
        $this->imagerieActeIds = $this->imagerieActes()->pluck('id')->map(fn($id) => (string) $id)->all();

        $fieldMap = $this->sectionFieldMap();

        if (array_key_exists($section, $fieldMap)) {
            $field = $fieldMap[$section];
            $this->textValue = (string) ($this->consultation->{$field} ?? '');
        }

        if ($section === 'symptomes') {
            $this->symptome_ids = $this->consultation->symptomeItems->pluck('id')->map(fn($id) => (string) $id)->all();
        }
    }

    public function isLaboratoireActeValid($acte): bool
    {
        return (bool) ($acte->pivot->valide ?? false);
    }

    public function isLaboratoireActeIdValidated(int $acteId): bool
    {
        $acte = $this->consultation->actes->firstWhere('id', $acteId);

        return $acte && $this->isLaboratoireActeValid($acte);
    }

    public function validatedLaboratoireActeIds(): array
    {
        return $this->laboratoireActes()->filter(fn($acte) => $this->isLaboratoireActeValid($acte))->pluck('id')->map(fn($id) => (string) $id)->all();
    }

    public function updatedLaboratoireActeIds(): void
    {
        $this->laboratoireActeIds = collect($this->laboratoireActeIds)->merge($this->validatedLaboratoireActeIds())->unique()->values()->all();
    }

    public function openLaboratoireActeNoteModal(int $acteId): void
    {
        $acte = $this->consultation->actes->firstWhere('id', $acteId);

        if (!$acte || $this->isLaboratoireActeValid($acte)) {
            return;
        }

        $this->selectedLaboratoireActeId = $acteId;
        $this->selectedLaboratoireActeName = $acte->name;
        $this->laboratoireActeNote = (string) ($acte->pivot->note_clinique ?? '');

        $this->dispatch('laboratoire-acte-note-open');
    }

    public function saveLaboratoireActeNote(): void
    {
        $validated = $this->validate([
            'laboratoireActeNote' => ['nullable', 'string', 'max:255'],
        ]);

        if (!$this->selectedLaboratoireActeId) {
            return;
        }

        $this->consultation->actes()->updateExistingPivot($this->selectedLaboratoireActeId, [
            'note_clinique' => $validated['laboratoireActeNote'] ?: null,
        ]);

        $this->loadConsultation($this->consultation->id);
        $this->dispatch('laboratoire-acte-note-saved');
    }

    public function availableMedicaments(): Collection
    {
        return Medicament::query()->where('is_active', true)->orderBy('name')->limit(500)->get();
    }

    public function prescriptionItems(): Collection
    {
        return $this->consultation->prescription?->medicaments ?? collect();
    }

    public function addPrescriptionItem(): void
    {
        $validated = $this->validate([
            'prescriptionMedicamentId' => ['required', 'integer', 'exists:medicaments,id'],
            'prescriptionQty' => ['required', 'integer', 'gt:0'],
            'prescriptionPosologie' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated) {
            $prescription =
                $this->consultation->prescription ?:
                Prescription::query()->create([
                    'consultation_id' => $this->consultation->id,
                    'hopital_id' => $this->consultation->hopital_id,
                    'dossier_patient_id' => $this->consultation->dossier_patient_id,
                    'status' => 'draft',
                ]);

            $existing = $prescription->medicaments()->where('medicament_id', $validated['prescriptionMedicamentId'])->first();

            if ($existing) {
                $prescription->medicaments()->updateExistingPivot($validated['prescriptionMedicamentId'], [
                    'nbr' => (int) $existing->pivot->nbr + (int) $validated['prescriptionQty'],
                    'posologie' => $validated['prescriptionPosologie'] ?: $existing->pivot->posologie,
                ]);
            } else {
                $prescription->medicaments()->attach($validated['prescriptionMedicamentId'], [
                    'qte_jour' => 1,
                    'nbr' => (int) $validated['prescriptionQty'],
                    'qte_servie' => 0,
                    'posologie' => $validated['prescriptionPosologie'],
                ]);
            }
        });

        $this->loadConsultation($this->consultation->id);
        $this->currentSection = 'prescription';
        $this->prescriptionMedicamentId = null;
        $this->prescriptionQty = 1;
        $this->prescriptionPosologie = null;
    }

    public function removePrescriptionItem(int $medicamentId): void
    {
        $prescription = $this->consultation->prescription;
        if (!$prescription) {
            return;
        }

        $prescription->medicaments()->detach($medicamentId);
        $this->loadConsultation($this->consultation->id);
        $this->currentSection = 'prescription';
    }

    public function confirmDeleteLaboratoireActe(int $acteId): void
    {
        $acte = $this->consultation->actes->firstWhere('id', $acteId);

        if (!$acte || $this->isLaboratoireActeValid($acte)) {
            return;
        }

        $this->pendingDeleteLaboratoireActeId = $acteId;
        $this->selectedLaboratoireActeName = $acte->name;
        $this->dispatch('laboratoire-acte-delete-open');
    }

    public function deleteLaboratoireActe(): void
    {
        if (!$this->pendingDeleteLaboratoireActeId) {
            return;
        }

        if ($this->isLaboratoireActeIdValidated($this->pendingDeleteLaboratoireActeId)) {
            return;
        }

        $this->consultation->actes()->detach($this->pendingDeleteLaboratoireActeId);
        $this->pendingDeleteLaboratoireActeId = null;
        $this->selectedLaboratoireActeName = null;

        $this->loadConsultation($this->consultation->id);
        $this->dispatch('laboratoire-acte-deleted');
    }

    public function saveEditor(): void
    {
        if (!$this->currentSection) {
            return;
        }

        $fieldMap = $this->sectionFieldMap();

        if (array_key_exists($this->currentSection, $fieldMap)) {
            $validated = $this->validate([
                'textValue' => ['nullable', 'string', 'max:5000'],
            ]);

            $this->consultation->update([
                $fieldMap[$this->currentSection] => $validated['textValue'] ?: null,
            ]);
        }

        if ($this->currentSection === 'vitals') {
            $validated = $this->validate([
                'vitalsForm.poids' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.temperature' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.taille' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.systolite' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.diastolique' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.frequence_cardiaque' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.frequence_respiratoire' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.saturation_oxygene' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.glycemie' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.perimetre_cranien' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.perimetre_brachial' => ['nullable', 'numeric', 'min:0'],
                'vitalsForm.mois' => ['nullable', 'string', 'max:255'],
            ]);

            $this->consultation->update($validated['vitalsForm']);
        }

        if ($this->currentSection === 'laboratoire') {
            $this->laboratoireForm['hopital_id'] = current_hopital_id();

            $validated = $this->validate([
                'laboratoireForm.renseignement' => ['nullable', 'string', 'max:255'],
                'laboratoireForm.note' => ['nullable', 'string', 'max:255'],
                'laboratoireForm.antibiotique' => ['nullable', 'string', 'max:255'],
                'laboratoireForm.commentaire' => ['nullable', 'string', 'max:255'],
                'laboratoireForm.hopital_id' => ['required', 'integer', 'exists:hopitals,id'],
                'laboratoireActeIds' => ['required', 'array', 'min:1'],
                'laboratoireActeIds.*' => ['exists:actes,id'],
            ]);

            $laboratoire = Laboratoire::query()->updateOrCreate(
                ['consultation_id' => $this->consultation->id],
                array_merge(
                    [
                        'renseignement' => (string) ($validated['laboratoireForm']['renseignement'] ?? ''),
                        'note' => $validated['laboratoireForm']['note'] ?: null,
                        'antibiotique' => $validated['laboratoireForm']['antibiotique'] ?: null,
                        'commentaire' => $validated['laboratoireForm']['commentaire'] ?: null,
                        'hopital_id' => current_hopital_id(),
                        'user_id' => auth()->id(),
                        'statut' => $this->consultation->laboratoire?->statut ?? 'en attente',
                    ],
                    ['consultation_id' => $this->consultation->id],
                ),
            );

            $this->consultation->update([
                'laboratoire_id' => $laboratoire->id,
            ]);

            $this->syncSectionActes('laboratoire', $validated['laboratoireActeIds']);
        }

        if ($this->currentSection === 'imagerie') {
            $validated = $this->validate([
                'imagerieForm.renseignement' => ['nullable', 'string', 'max:255'],
                'imagerieForm.note' => ['nullable', 'string', 'max:255'],
                'imagerieForm.antibiotique' => ['nullable', 'string', 'max:255'],
                'imagerieActeIds' => ['required', 'array', 'min:1'],
                'imagerieActeIds.*' => ['exists:actes,id'],
            ]);

            $imagerie = Imagerie::query()->updateOrCreate(
                ['consultation_id' => $this->consultation->id],
                array_merge(
                    [
                        'renseignement' => (string) ($validated['imagerieForm']['renseignement'] ?? ''),
                        'note' => $validated['imagerieForm']['note'] ?: null,
                        'antibiotique' => $validated['imagerieForm']['antibiotique'] ?: null,
                        'hopital_id' => current_hopital_id(),
                        'statut' => $this->consultation->imagerie?->statut ?? 'en attente',
                    ],
                    ['consultation_id' => $this->consultation->id],
                ),
            );

            $this->consultation->update([
                'imagerie_id' => $imagerie->id,
            ]);

            $this->syncSectionActes('imagerie', $validated['imagerieActeIds']);
        }

        if ($this->currentSection === 'prescription') {
            $validated = $this->validate([
                'prescriptionNote' => ['nullable', 'string', 'max:5000'],
            ]);

            $this->consultation->update([
                'prescription_medicale' => $validated['prescriptionNote'] ?: null,
            ]);

            Prescription::query()->firstOrCreate(['consultation_id' => $this->consultation->id], ['dossier_patient_id' => $this->consultation->dossier_patient_id]);
        }

        if ($this->currentSection === 'symptomes') {
            $validated = $this->validate([
                'symptome_ids' => ['nullable', 'array'],
                'symptome_ids.*' => ['exists:symptomes,id'],
            ]);

            $this->consultation->symptomeItems()->sync(
                collect($validated['symptome_ids'] ?? [])
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all(),
            );
        }

        $this->loadConsultation($this->consultation->id);
        $this->dispatch('consultation-section-saved');
    }

    public function sectionFieldMap(): array
    {
        return [
            'complement_anamnese' => 'complement_anamnese',
            'examen_physique' => 'examen_physique',
            'diagnostic_presomption' => 'diagnostic_presomption',
            'plan_traitement_conduite' => 'plan_traitement_conduite',
        ];
    }

    public function narrativeSections(): array
    {
        return [['key' => 'symptomes', 'title' => 'Symptômes', 'preview' => null], ['key' => 'complement_anamnese', 'title' => 'Complement d\'anamnese', 'preview' => $this->consultation->complement_anamnese], ['key' => 'examen_physique', 'title' => 'Examen physique', 'preview' => $this->consultation->examen_physique], ['key' => 'diagnostic_presomption', 'title' => 'Diagnostic de presomption', 'preview' => $this->consultation->diagnostic_presomption], ['key' => 'diagnostic_certitude', 'title' => 'Diagnostic de certitude', 'preview' => $this->consultation->diagnostic_certitude], ['key' => 'plan_traitement_conduite', 'title' => 'Plan de traitement et conduite a tenir', 'preview' => $this->consultation->plan_traitement_conduite]];
    }

    public function hasSymptomes(): bool
    {
        return $this->consultation->symptomeItems->isNotEmpty();
    }

    public function sectionHasContent(array $section): bool
    {
        if ($section['key'] === 'symptomes') {
            return $this->hasSymptomes();
        }

        return $this->hasContent($section['preview']);
    }

    public function rendezVousOwnerId(): int
    {
        if ($this->consultation->is_visite_program && $this->consultation->consultation_source_id) {
            return (int) $this->consultation->consultation_source_id;
        }

        return $this->consultation->id;
    }

    public function programmedRendezVousList(): Collection
    {
        $ownerId = $this->rendezVousOwnerId();

        return Consultation::query()
            ->with(['departement', 'service', 'user', 'assurance', 'projet'])
            ->where('hopital_id', $this->consultation->hopital_id)
            ->programmed()
            ->where('id', '!=', $this->consultation->id)
            ->where(function ($query) use ($ownerId) {
                $query->where('consultation_source_id', $ownerId)->orWhere(function ($legacy) use ($ownerId) {
                    $legacy->whereNull('consultation_source_id')->where('dossier_patient_id', $this->consultation->dossier_patient_id)->where('id', '>', $ownerId);
                });
            })
            ->orderBy('created_at')
            ->get();
    }

    public function hasRendezVous(): bool
    {
        return $this->programmedRendezVousList()->isNotEmpty();
    }

    public function openRendezVousModal(?int $rendezVousId = null): void
    {
        $this->resetValidation();
        $this->rendezVousEditId = $rendezVousId;
        $this->rendezVousDate = '';
        $this->rendezVousHeure = '08:00';
        $this->rendezVousMotif = '';

        if ($rendezVousId) {
            $rdv = $this->programmedRendezVousList()->firstWhere('id', $rendezVousId);

            if (!$rdv) {
                return;
            }

            $this->rendezVousDate = $rdv->created_at?->format('Y-m-d') ?? '';
            $this->rendezVousHeure = $rdv->created_at?->format('H:i') ?? '08:00';
            $this->rendezVousMotif = (string) ($rdv->rendez_vous_medical ?? '');
        }

        $this->dispatch('rendez-vous-modal-open');
    }

    public function saveRendezVous(): void
    {
        $validated = $this->validate([
            'rendezVousDate' => ['required', 'date'],
            'rendezVousHeure' => ['required', 'date_format:H:i'],
            'rendezVousMotif' => ['nullable', 'string', 'max:255'],
        ]);

        $scheduledAt = Carbon::parse($validated['rendezVousDate'] . ' ' . $validated['rendezVousHeure']);
        $ownerId = $this->rendezVousOwnerId();
        $owner = $this->consultation->is_visite_program && $this->consultation->consultation_source_id ? Consultation::query()->findOrFail($ownerId) : $this->consultation;

        if ($this->rendezVousEditId) {
            $rdv = $this->programmedRendezVousList()->firstWhere('id', $this->rendezVousEditId);

            if (!$rdv) {
                return;
            }

            $rdv->update([
                'rendez_vous_medical' => $validated['rendezVousMotif'] ?: null,
                'created_at' => $scheduledAt,
                'updated_at' => $scheduledAt,
            ]);
        } else {
            $programmed = Consultation::createWithPeriodContext(
                [
                    'type' => 'consultation',
                    'type_fichier' => $owner->type_fichier,
                    'dossier_patient_id' => $owner->dossier_patient_id,
                    'departement_id' => $owner->departement_id,
                    'projet_id' => $owner->projet_id,
                    'assurance_id' => $owner->assurance_id,
                    'service_id' => $owner->service_id,
                    'hopital_id' => $owner->hopital_id,
                    'user_id' => $owner->user_id,
                    'is_visite_program' => true,
                    'consultation_source_id' => $ownerId,
                    'rendez_vous_medical' => $validated['rendezVousMotif'] ?: null,
                    'created_at' => $scheduledAt,
                    'updated_at' => $scheduledAt,
                ],
                [
                    'use_project_period' => (bool) $owner->is_project_period,
                ],
            );

            $facturationId = DB::table('facturations')->insertGetId([
                'consultation_id' => $programmed->id,
                'dossier_patient_id' => $owner->dossier_patient_id,
                'hopital_id' => $owner->hopital_id,
                'created_at' => $scheduledAt,
                'updated_at' => $scheduledAt,
            ]);

            $programmed->update([
                'facturation_id' => $facturationId,
            ]);
        }

        $this->rendezVousEditId = null;
        $this->loadConsultation($this->consultation->id);
        $this->dispatch('rendez-vous-modal-saved');
    }

    public function confirmDeleteRendezVous(int $rendezVousId): void
    {
        if (!$this->programmedRendezVousList()->firstWhere('id', $rendezVousId)) {
            return;
        }

        $this->pendingDeleteRendezVousId = $rendezVousId;
        $this->dispatch('rendez-vous-delete-open');
    }

    public function deleteRendezVous(): void
    {
        if (!$this->pendingDeleteRendezVousId) {
            return;
        }

        $rdv = $this->programmedRendezVousList()->firstWhere('id', $this->pendingDeleteRendezVousId);

        if (!$rdv) {
            return;
        }

        if ($rdv->facturation_id) {
            DB::table('facturations')->where('id', $rdv->facturation_id)->delete();
        }

        $rdv->delete();

        $this->pendingDeleteRendezVousId = null;
        $this->loadConsultation($this->consultation->id);
        $this->dispatch('rendez-vous-deleted');
    }

    public function rendezVousStatus(?Consultation $rdv): array
    {
        if (!$rdv?->created_at) {
            return [
                'label' => 'Non planifie',
                'badge' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            ];
        }

        $date = $rdv->created_at->copy()->startOfDay();
        $today = Carbon::today();

        return match (true) {
            $date->lt($today) => [
                'label' => 'Passe',
                'badge' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
            ],
            $date->equalTo($today) => [
                'label' => 'Aujourd\'hui',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
            ],
            default => [
                'label' => 'A venir',
                'badge' => 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300',
            ],
        };
    }

    public function rendezVousDetailRows(?Consultation $rdv): array
    {
        if (!$rdv) {
            return [];
        }

        return [['label' => 'Reference', 'value' => $rdv->reference], ['label' => 'Type de fiche', 'value' => ucfirst((string) $rdv->type_fichier)], ['label' => 'Departement', 'value' => $rdv->departement?->name ?: '-'], ['label' => 'Service', 'value' => $rdv->service?->name ?: '-'], ['label' => 'Medecin', 'value' => $rdv->user?->name ?: 'Non assigne'], ['label' => 'Projet', 'value' => $rdv->projet?->name ?: '-'], ['label' => 'Prise en charge', 'value' => $rdv->assurance?->name ?: 'Paiement direct'], ['label' => 'Periode', 'value' => $rdv->mois ?: '-']];
    }

    public function hasContent(?string $value): bool
    {
        return filled($value);
    }

    public function patientIdentity(): string
    {
        $patient = $this->consultation->dossierPatient;

        if (!$patient) {
            return 'Patient inconnu';
        }

        return trim(sprintf('%s %s %s (%s)', strtoupper((string) $patient->nom), strtoupper((string) $patient->postnom), ucfirst((string) $patient->prenom), $patient->age ?: 'age indisponible'));
    }

    public function infoRows(): array
    {
        return [['label' => 'Reference', 'value' => $this->consultation->reference], ['label' => 'Type', 'value' => $this->consultation->type === 'consultation' ? 'Visite' : 'Examen'], ['label' => 'Fiche', 'value' => ucfirst((string) $this->consultation->type_fichier)], ['label' => 'Periode', 'value' => $this->consultation->mois ?: '-'], ['label' => 'Departement', 'value' => $this->consultation->departement?->name ?: '-'], ['label' => 'Service', 'value' => $this->consultation->service?->name ?: '-'], ['label' => 'Medecin', 'value' => $this->consultation->user?->name ?: 'Non assigne'], ['label' => 'Projet', 'value' => $this->consultation->projet?->name ?: '-'], ['label' => 'Prise en charge', 'value' => $this->consultation->assurance?->name ?: 'Paiement direct'], ['label' => 'Date creation', 'value' => optional($this->consultation->created_at)->format('d/m/Y H:i') ?: '-']];
    }

    public function vitalRows(): array
    {
        return [
            ['label' => 'Poids', 'value' => $this->valueWithUnit($this->consultation->poids, 'kg')],
            ['label' => 'Temperature', 'value' => $this->valueWithUnit($this->consultation->temperature, '°C')],
            ['label' => 'Taille', 'value' => $this->valueWithUnit($this->consultation->taille, 'cm')],
            ['label' => 'Tension arterielle', 'value' => $this->bloodPressureValue()],
            ['label' => 'Frequence cardiaque', 'value' => $this->valueWithUnit($this->consultation->frequence_cardiaque, 'bpm')],
            ['label' => 'Frequence respiratoire', 'value' => $this->valueWithUnit($this->consultation->frequence_respiratoire, 'cpm')],
            ['label' => 'Saturation oxygene', 'value' => $this->valueWithUnit($this->consultation->saturation_oxygene, '%')],
            ['label' => 'Glycemie', 'value' => $this->valueWithUnit($this->consultation->glycemie, 'mg/dL')],
            ['label' => 'Perimetre cranien', 'value' => $this->valueWithUnit($this->consultation->perimetre_cranien, 'cm')],
            ['label' => 'Perimetre brachial', 'value' => $this->valueWithUnit($this->consultation->perimetre_brachial, 'cm')],
        ];
    }

    public function valueWithUnit($value, string $unit): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return trim($value . ' ' . $unit);
    }

    public function bloodPressureValue(): string
    {
        if (!$this->consultation->systolite && !$this->consultation->diastolique) {
            return '-';
        }

        return ($this->consultation->systolite ?? '-') . '/' . ($this->consultation->diastolique ?? '-');
    }

    public function imc(): ?float
    {
        if (!$this->consultation->poids || !$this->consultation->taille) {
            return null;
        }

        $tailleMetre = ((float) $this->consultation->taille) / 100;

        if ($tailleMetre <= 0) {
            return null;
        }

        return round(((float) $this->consultation->poids) / ($tailleMetre * $tailleMetre), 1);
    }

    public function imcLabel(): string
    {
        $imc = $this->imc();

        if ($imc === null) {
            return 'Non calcule';
        }

        return match (true) {
            $imc < 18.5 => 'Insuffisance ponderale',
            $imc < 25 => 'Corpulence normale',
            $imc < 30 => 'Surpoids',
            default => 'Obesite',
        };
    }

    public function laboratoirePreview(): ?string
    {
        $segments = [];

        if ($this->consultation->laboratoire) {
            $segments[] = Arr::join(array_filter([$this->consultation->laboratoire->renseignement, $this->consultation->laboratoire->note, $this->consultation->laboratoire->commentaire]), ' | ');
        }

        if ($this->laboratoireActes()->isNotEmpty()) {
            $segments[] = 'Examens: ' . $this->laboratoireActes()->pluck('name')->implode(', ');
        }

        return $segments === [] ? null : Arr::join(array_filter($segments), ' | ');
    }

    public function imageriePreview(): ?string
    {
        $segments = [];

        if ($this->consultation->imagerie) {
            $segments[] = Arr::join(array_filter([$this->consultation->imagerie->renseignement, $this->consultation->imagerie->note]), ' | ');
        }

        if ($this->imagerieActes()->isNotEmpty()) {
            $segments[] = 'Examens: ' . $this->imagerieActes()->pluck('name')->implode(', ');
        }

        return $segments === [] ? null : Arr::join(array_filter($segments), ' | ');
    }

    public function acteBelongsToSection($acte, string $section): bool
    {
        $departement = $acte->departement;

        if (!$departement) {
            return false;
        }

        return match ($section) {
            'laboratoire' => str_contains(strtolower((string) $departement->name), 'laboratoire') || strtolower((string) $departement->ref) === 'labo',
            'imagerie' => str_contains(strtolower((string) $departement->name), 'imagerie') || strtolower((string) $departement->ref) === 'img',
            default => false,
        };
    }

    public function laboratoireActes()
    {
        return $this->consultation->actes->filter(fn($acte) => $this->acteBelongsToSection($acte, 'laboratoire'))->values();
    }

    public function imagerieActes()
    {
        return $this->consultation->actes->filter(fn($acte) => $this->acteBelongsToSection($acte, 'imagerie'))->values();
    }

    protected function laboratoireDepartement(): ?Departement
    {
        return Departement::query()->where('name', 'like', '%laboratoire%')->orWhere('ref', 'labo')->first();
    }

    public function availableLaboratoireActes()
    {
        $departementId = $this->laboratoireDepartement()?->id;

        if (!$departementId) {
            return collect();
        }

        return Acte::query()
            ->with(['departement', 'service'])
            ->where('departement_id', $departementId)
            ->orderBy('name')
            ->get();
    }

    protected function imagerieDepartement(): ?Departement
    {
        return Departement::query()->where('name', 'like', '%imagerie%')->orWhere('ref', 'img')->first();
    }

    protected function syncSectionActes(string $section, array $selectedIds): void
    {
        $selectedIds = collect($selectedIds)->map(fn($id) => (int) $id)->unique()->values()->all();

        if ($section === 'laboratoire') {
            $lockedIds = $this->laboratoireActes()->filter(fn($acte) => $this->isLaboratoireActeValid($acte))->pluck('id')->map(fn($id) => (int) $id)->all();

            $selectedIds = collect($selectedIds)->merge($lockedIds)->unique()->values()->all();
        }

        $existingActesPayload = $this->consultation->actes
            ->mapWithKeys(function ($acte) {
                return [
                    (int) $acte->id => [
                        'ref' => $acte->pivot->ref,
                        'montant' => (float) ($acte->pivot->montant ?? ($acte->montant ?? 0)),
                        'prise_en_charge' => (float) ($acte->pivot->prise_en_charge ?? 0),
                        'payer' => (bool) ($acte->pivot->payer ?? false),
                        'valide' => (bool) ($acte->pivot->valide ?? false),
                        'user_id' => $acte->pivot->user_id,
                        'note_clinique' => $acte->pivot->note_clinique,
                        'commentaire' => $acte->pivot->commentaire,
                        'resultat' => $acte->pivot->resultat,
                        'clinique' => $acte->pivot->clinique,
                        'protocole' => $acte->pivot->protocole,
                        'cloture' => $acte->pivot->cloture,
                    ],
                ];
            })
            ->toArray();

        $existingOtherActes = $this->consultation->actes->reject(fn($acte) => $this->acteBelongsToSection($acte, $section))->mapWithKeys(fn($acte) => [(int) $acte->id => $existingActesPayload[(int) $acte->id]])->toArray();

        $selectedActes = Acte::query()->with('departement')->whereIn('id', $selectedIds)->get()->keyBy('id');

        $selectedSectionActes = collect($selectedIds)
            ->mapWithKeys(function (int $acteId) use ($selectedActes, $existingActesPayload) {
                /** @var \App\Models\Configs\Acte|null $acte */
                $acte = $selectedActes->get($acteId);
                $existingPayload = $existingActesPayload[$acteId] ?? [];

                return [
                    $acteId => array_merge($existingPayload, [
                        'ref' => $acte?->departement?->ref ?? ($existingPayload['ref'] ?? 'GEN'),
                        'montant' => (float) ($existingPayload['montant'] ?? ($acte?->montant ?? 0)),
                    ]),
                ];
            })
            ->toArray();

        $this->consultation->actes()->sync($existingOtherActes + $selectedSectionActes);
    }
};
?>

<x-pages::consultation.layout :patient="$this->consultation->dossierPatient">
    <div class="mx-auto max-w-[1600px] space-y-6 pt-4">
        <section
            class="overflow-hidden rounded-[1.75rem] border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">Fiche clinique</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                            @if ($this->consultation->is_visite_program)
                                Rendez-vous médical
                            @else
                                Visite médicale
                            @endif
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $this->patientIdentity() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="rounded-full bg-slate-200 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            @if ($consultation->type === 'consultation')
                                Visite
                            @else
                                Examen
                            @endif
                        </span>
                        <span
                            class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                            {{ $consultation->mois ?: 'Periode non definie' }}
                        </span>
                        @if ($consultation->is_clore)
                            <span
                                class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">
                                Cloturee
                            </span>
                        @else
                            <span
                                class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">
                                En cours
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-0 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($this->infoRows() as $row)
                    <div class="border-b border-r border-slate-200 px-5 py-4 dark:border-slate-800">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $row['label'] }}
                        </p>
                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $row['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.45fr,1fr]">
            <div class="space-y-6">
                @if ($this->consultation->type == 'consultation')
                    {{-- Données du prelevement --}}
                    <section
                        class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div
                            class="rounded-md border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">
                                        Constantes
                                    </p>
                                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Signes vitaux
                                    </h3>
                                </div>
                                <x-button wire:click="openEditor('vitals')"
                                    x-on:click="$tsui.open.modal('consultation-section-modal')" icon="pencil-square">
                                    Mettre a jour
                                </x-button>
                            </div>
                        </div>

                        <div class="grid gap-0 sm:grid-cols-2">
                            @foreach ($this->vitalRows() as $row)
                                <div class="border-b border-r border-slate-200 px-5 py-4 dark:border-slate-800">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
                                        {{ $row['label'] }}</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900 dark:text-white">
                                        {{ $row['value'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                            <div
                                class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                <p
                                    class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                                    Indice de masse corporelle</p>
                                <div class="mt-2 flex items-center justify-between gap-4">
                                    <p class="text-2xl font-black text-emerald-900 dark:text-emerald-100">
                                        {{ $this->imc() !== null ? number_format($this->imc(), 1, ',', ' ') : '--' }}
                                    </p>
                                    <span
                                        class="rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-slate-900 dark:text-emerald-300">
                                        {{ $this->imcLabel() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Renseignement sur le patient --}}
                    <section
                        class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div
                            class="rounded-md rounded-t border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">
                                        Narratif
                                        clinique</p>
                                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Sections
                                        medicales
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($this->narrativeSections() as $section)
                                <div class="px-5 py-5">
                                    <div>
                                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="h-2.5 w-2.5 rounded-full {{ $this->sectionHasContent($section) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                                <h4
                                                    class="text-sm font-black uppercase tracking-[0.18em] text-slate-700 dark:text-slate-200">
                                                    {{ $section['title'] }}
                                                </h4>
                                            </div>
                                            <div>
                                                <x-button wire:click="openEditor('{{ $section['key'] }}')" sm
                                                    x-on:click="$tsui.open.modal('consultation-section-modal')"
                                                    icon="pencil-square">
                                                    {{ $this->sectionHasContent($section) ? 'Editer' : 'Ajouter' }}
                                                </x-button>
                                            </div>
                                        </div>
                                        <div>
                                            @if ($section['key'] === 'symptomes')
                                                @if ($this->hasSymptomes())
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($this->consultation->symptomeItems as $symptome)
                                                            <span wire:key="symptome-{{ $symptome->id }}"
                                                                class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-800 dark:border-rose-500/25 dark:bg-rose-500/10 dark:text-rose-300">
                                                                {{ $symptome->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <i class="text-gray-500 text-sm">
                                                        Aucun symptome enregistre.
                                                    </i>
                                                @endif
                                            @elseif (blank($section['preview']))
                                                <i class="text-gray-500 text-sm">
                                                    Aucune information enregistrée.
                                                </i>
                                            @else
                                                {{ $section['preview'] }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    @php
                        $rendezVousList = $this->programmedRendezVousList();
                    @endphp
                    <section
                        class="overflow-hidden rounded-md border border-violet-200 bg-white shadow-sm dark:border-violet-500/25 dark:bg-slate-950/70">
                        <div
                            class="border-b border-violet-100 bg-gradient-to-r from-violet-50 to-white px-5 py-4 dark:border-violet-500/20 dark:from-violet-950/40 dark:to-slate-950/70">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                                        <flux:icon.calendar-days class="size-6" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-[11px] font-black uppercase tracking-[0.22em] text-violet-600 dark:text-violet-400">
                                            Suivi programme
                                        </p>
                                        <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                                            Rendez-vous planifies
                                        </h3>
                                        @if ($this->consultation->is_visite_program)
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Fiche du rendez-vous en cours. Les autres passages planifies sont
                                                listes ci-dessous.
                                            </p>
                                            @if ($this->consultation->consultationSource)
                                                <a href="{{ route('consultation.show', $this->consultation->consultation_source_id) }}"
                                                    wire:navigate
                                                    class="mt-2 inline-flex text-xs font-bold text-violet-700 hover:text-violet-900 dark:text-violet-300">
                                                    Retour a la consultation d'origine
                                                    ({{ $this->consultation->consultationSource->reference }})
                                                </a>
                                            @endif
                                        @else
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Planifiez un ou plusieurs rendez-vous de suivi pour ce patient.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-800 dark:bg-violet-500/15 dark:text-violet-300">
                                        {{ $rendezVousList->count() }}
                                    </span>
                                    <x-button wire:click="openRendezVousModal" sm icon="plus"
                                        x-on:click="$tsui.open.modal('rendez-vous-modal')">
                                        Ajouter un RDV
                                    </x-button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 px-5 py-5">
                            @forelse ($rendezVousList as $rdv)
                                @php
                                    $rdvStatus = $this->rendezVousStatus($rdv);
                                @endphp
                                <article wire:key="rdv-{{ $rdv->id }}"
                                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/40">
                                    <div
                                        class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/80 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-900/60">
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-200">
                                                <span class="text-lg font-black leading-none">
                                                    {{ $rdv->created_at->format('d') }}
                                                </span>
                                                <span class="text-[10px] font-bold uppercase">
                                                    {{ $rdv->created_at->format('M') }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-black text-slate-900 dark:text-white">
                                                        {{ $rdv->created_at->format('d/m/Y') }} a
                                                        {{ $rdv->created_at->format('H:i') }}
                                                    </p>
                                                    <span
                                                        class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $rdvStatus['badge'] }}">
                                                        {{ $rdvStatus['label'] }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $rdv->reference }} ·
                                                    {{ $rdv->departement?->name ?: 'Departement non defini' }}
                                                </p>
                                                <p
                                                    class="mt-1 text-xs font-semibold text-violet-700 dark:text-violet-300">
                                                    {{ $rdv->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('consultation.show', $rdv->id) }}" wire:navigate
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 dark:border-violet-500/25 dark:bg-violet-500/10 dark:text-violet-300">
                                                Ouvrir
                                            </a>
                                            <x-button wire:click="openRendezVousModal({{ $rdv->id }})" sm
                                                icon="pencil-square" x-on:click="$tsui.open.modal('rendez-vous-modal')">
                                                Modifier
                                            </x-button>
                                            <flux:button size="sm" variant="danger" icon="trash"
                                                wire:click="confirmDeleteRendezVous({{ $rdv->id }})">
                                                Supprimer
                                            </flux:button>
                                        </div>
                                    </div>

                                    <div class="grid gap-0 sm:grid-cols-2">
                                        @foreach ($this->rendezVousDetailRows($rdv) as $row)
                                            <div
                                                class="border-b border-r border-slate-100 px-4 py-2.5 dark:border-slate-800">
                                                <p
                                                    class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
                                                    {{ $row['label'] }}
                                                </p>
                                                <p
                                                    class="mt-0.5 text-sm font-medium text-slate-800 dark:text-slate-200">
                                                    {{ $row['value'] }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if (filled($rdv->rendez_vous_medical))
                                        <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
                                                Motif / instructions
                                            </p>
                                            <p
                                                class="mt-1 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">
                                                {{ $rdv->rendez_vous_medical }}
                                            </p>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div
                                    class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-5 py-8 text-center dark:border-slate-700 dark:bg-slate-900/30">
                                    <flux:icon.calendar class="mx-auto size-8 text-slate-400" />
                                    <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        Aucun autre rendez-vous planifie
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        @if ($this->consultation->is_visite_program)
                                            Ce passage est le rendez-vous actuellement consulte.
                                        @else
                                            Ajoutez le premier rendez-vous de suivi pour ce patient.
                                        @endif
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endif


                {{-- examen de laboratoire --}}
                <section
                    class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div
                        class="rounded-md border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Demandes
                                </p>
                                <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Laboratoire</h3>
                            </div>
                            <x-button wire:click="openEditor('laboratoire')"
                                x-on:click="$tsui.open.modal('consultation-section-modal')" icon="beaker">
                                Demandé examen
                            </x-button>
                        </div>
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Renseignement</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">
                                {{ $this->consultation->laboratoire?->renseignement ?: 'Aucun renseignement de laboratoire saisi.' }}
                            </p>
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Examens demandés</p>
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ $this->laboratoireActes()->count() }}
                                </span>
                            </div>
                            <div class="overflow-hidden border border-gray-200 rounded-lg shadow-sm">
                                <table class="min-w-full divide-y divide-gray-200 bg-white text-sm">
                                    <!-- En-tête d'actions -->
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th colspan="5" class="px-4 py-3 text-right">
                                                <a href="#"
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 font-semibold transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                                        </path>
                                                    </svg>
                                                    Imprimer le Bon de laboratoire
                                                </a>
                                            </th>
                                        </tr>
                                    </thead>

                                    <!-- En-tête des colonnes -->
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th
                                                class="px-4 py-3 font-semibold text-gray-700 border-b border-r border-gray-200 text-left">
                                                #</th>
                                            <th
                                                class="px-4 py-3 font-semibold text-gray-700 border-b border-r border-gray-200 text-left">
                                                Examens</th>
                                            <th
                                                class="px-4 py-3 font-semibold text-center text-gray-700 border-b border-r border-gray-200">
                                                Résultat</th>
                                            <th
                                                class="px-4 py-3 font-semibold text-gray-700 border-b border-r border-gray-200 text-center">
                                                Valeur normale</th>
                                            <th
                                                class="px-4 py-3 font-semibold text-gray-700 border-b border-r border-gray-200 text-right">
                                                Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-200">
                                        @forelse ($this->laboratoireActes() as $acte)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 text-xs py-3 text-gray-500 border-r border-gray-200">
                                                    {{ $loop->iteration }}</td>
                                                <td
                                                    class="px-4 py-3 text-xs font-medium text-gray-900 border-r border-gray-200">
                                                    {{ $acte->name }}</td>
                                                <td class="px-4 text-xs py-3 border-r text-center border-gray-200">
                                                    @if ($acte->pivot->resultat && $acte->pivot->valide)
                                                        <span
                                                            class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">
                                                            {{ $acte->pivot->resultat }}
                                                        </span>
                                                    @else
                                                        <span class="italic text-gray-400">En attente</span>
                                                    @endif
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-xs text-center text-gray-600 border-r border-gray-200">
                                                    <span
                                                        class="font-mono text-xs">{{ $acte->valeur_normale ?? '[-]' }}</span>
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-xs flex gap-2 justify-end border-r border-gray-200">
                                                    <flux:button size="xs" variant="primary" color="indigo"
                                                        wire:click="openLaboratoireActeNoteModal({{ $acte->id }})"
                                                        :icon="$acte->pivot->valide ? 'lock-closed' : 'pencil-square'"
                                                        :disabled="$acte->pivot->valide">
                                                        Note
                                                    </flux:button>
                                                    <flux:button size="xs" variant="danger"
                                                        wire:click="confirmDeleteLaboratoireActe({{ $acte->id }})"
                                                        :icon="$acte->pivot->valide ? 'lock-closed' : 'trash'"
                                                        :disabled="$acte->pivot->valide">
                                                        Supprimer
                                                    </flux:button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6"
                                                    class="px-4 py-8 text-center text-gray-400 italic bg-gray-50">
                                                    Aucun examen demandé.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Examin d'imagerie --}}
                <section
                    class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div
                        class="rounded-md border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Demandes
                                </p>
                                <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Imagerie</h3>
                            </div>
                            <x-button wire:click="openEditor('imagerie')"
                                x-on:click="$tsui.open.modal('consultation-section-modal')" icon="photo">
                                Demander / modifier
                            </x-button>
                        </div>
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Renseignement</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">
                                {{ $this->consultation->imagerie?->renseignement ?: 'Aucun renseignement d imagerie saisi.' }}
                            </p>
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Examens demandes</p>
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ $this->imagerieActes()->count() }}
                                </span>
                            </div>
                            <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                                <table class="min-w-full border-collapse bg-white text-sm dark:bg-slate-950/40">
                                    <thead class="bg-slate-50 dark:bg-slate-900/70">
                                        <tr
                                            class="text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                            <th class="px-4 py-3">Examen</th>
                                            <th class="px-4 py-3">Service</th>
                                            <th class="px-4 py-3 text-center">Etat</th>
                                            <th class="px-4 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                        @forelse ($this->imagerieActes() as $acte)
                                            @php
                                                $isDocumented =
                                                    filled($acte->pivot->clinique ?? null) ||
                                                    filled($acte->pivot->protocole ?? null) ||
                                                    filled($acte->pivot->cloture ?? null);
                                            @endphp
                                            <tr
                                                class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-900/40">
                                                <td class="px-4 py-3">
                                                    <div>
                                                        <p class="font-semibold text-slate-900 dark:text-white">
                                                            {{ $acte->name }}</p>
                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                            {{ $this->consultation->reference }}
                                                        </p>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                    {{ $acte->service?->name ?: ($acte->departement?->name ?: 'Imagerie') }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span
                                                        class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $isDocumented ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                                                        {{ $isDocumented ? 'Renseigne' : 'A completer' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <a href="{{ route('imagerie.show', ['id' => $this->consultation->id, 'acte' => $acte->id]) }}"
                                                        wire:navigate
                                                        class="inline-flex items-center gap-2 rounded-xl border border-fuchsia-200 bg-fuchsia-50 px-3 py-1.5 text-xs font-bold text-fuchsia-700 transition hover:border-fuchsia-300 hover:bg-fuchsia-100 dark:border-fuchsia-500/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-300">
                                                        {{ $isDocumented ? 'Ouvrir le compte rendu' : 'Renseigner cet examen' }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4"
                                                    class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                                    Aucun examen d imagerie demande pour cette consultation.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                @if ($this->consultation->type == 'consultation')
                    {{-- prescription medicale --}}
                    <section
                        class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div
                            class="rounded-md border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Cure
                                        et
                                        traitement
                                    </p>
                                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Préscription
                                        médicale
                                    </h3>
                                </div>
                                <x-button wire:click="openEditor('prescription')"
                                    x-on:click="$tsui.open.modal('consultation-section-modal')" icon="document-plus">
                                    Demander prescription
                                </x-button>
                            </div>
                        </div>

                        <div class="space-y-4 px-5 py-5">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Note medicale</p>
                                <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">
                                    {{ $this->consultation->prescription_medicale ?: 'Aucune note de prescription enregistree.' }}
                                </p>
                            </div>

                            <div>
                                <div class="mb-3 flex items-center justify-between">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Medicaments prescrits</p>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ $this->prescriptionItems()->count() }}
                                    </span>
                                </div>
                                <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                                    <table class="min-w-full border-collapse bg-white text-sm dark:bg-slate-950/40">
                                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                                            <tr class="text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                                <th class="px-4 py-3">Medicament</th>
                                                <th class="px-4 py-3 text-center">Qte</th>
                                                <th class="px-4 py-3 text-center">Servie</th>
                                                <th class="px-4 py-3">Posologie</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                            @forelse($this->prescriptionItems() as $item)
                                                <tr>
                                                    <td class="px-4 py-3">
                                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $item->name }}</p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item->reference }}</p>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">{{ (int) $item->pivot->nbr }}</td>
                                                    <td class="px-4 py-3 text-center">{{ (int) $item->pivot->qte_servie }}</td>
                                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $item->pivot->posologie ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                                        Aucune ligne de prescription pour cette consultation.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                {{-- issue de la consultation --}}
                <section @class([
                    'overflow-hidden rounded-md border bg-white shadow-sm dark:bg-slate-950/70',
                    'border-emerald-200 dark:border-emerald-500/25' => $consultation->is_clore,
                    'border-slate-300 dark:border-slate-800' => !$consultation->is_clore,
                ])>
                    <div @class([
                        'border-b px-5 py-4',
                        'border-emerald-100 bg-gradient-to-r from-emerald-50 to-white dark:border-emerald-500/20 dark:from-emerald-950/40 dark:to-slate-950/70' =>
                            $consultation->is_clore,
                        'border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/80' => !$consultation->is_clore,
                    ])>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-4">
                                <div @class([
                                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' =>
                                        $consultation->is_clore,
                                    'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => !$consultation->is_clore,
                                ])>
                                    <flux:icon.clipboard-document-check class="size-6" />
                                </div>
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">
                                        Conclusion
                                    </p>
                                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                                        Issue de la consultation
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        @if ($consultation->is_clore)
                                            Consultation cloturee le
                                            {{ $consultation->updated_at?->format('d/m/Y a H:i') }}.
                                        @else
                                            Definissez l'issue puis cloturez la consultation pour finaliser le dossier.
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <span @class([
                                'rounded-full px-3 py-1 text-xs font-bold',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' =>
                                    $consultation->is_clore,
                                'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' => !$consultation->is_clore,
                            ])>
                                {{ $consultation->is_clore ? 'Cloturee' : 'En cours' }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-5 px-5 py-5">
                        @if (filled($consultation->issue))
                            <div
                                class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
                                    Issue enregistree
                                </p>
                                <p class="mt-2 text-lg font-black text-slate-900 dark:text-white">
                                    {{ $this->issueLabel() }}
                                </p>
                                @if (filled($consultation->autre_issue))
                                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                        <span class="font-semibold">Precision :</span>
                                        {{ $consultation->autre_issue }}
                                    </p>
                                @endif
                                @if (filled($consultation->cause_issue))
                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                        <span class="font-semibold">Cause / commentaire :</span>
                                        {{ $consultation->cause_issue }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if (!$consultation->is_clore)
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <x-select.styled label="Issue de la consultation *"
                                        placeholder="Choisir une issue..." wire:model.live="issueValue"
                                        :options="$this->issueOptions()" />
                                    @error('issueValue')
                                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                @if ($this->issueRequiresAutre())
                                    <div class="md:col-span-2">
                                        <x-input wire:model="autreIssue" label="Preciser l'issue (autres) *"
                                            placeholder="Decrivez l'issue" />
                                        @error('autreIssue')
                                            <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                                <div class="md:col-span-2">
                                    <x-textarea wire:model="causeIssue" label="Cause ou commentaire (optionnel)"
                                        rows="3" maxlength="1000" count
                                        placeholder="Motif de l'hospitalisation, structure de reference, etc." />
                                    @error('causeIssue')
                                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <flux:button variant="ghost" wire:click="saveIssue" icon="bookmark">
                                    Enregistrer l'issue
                                </flux:button>
                                <flux:button variant="primary" color="emerald" icon="check-circle"
                                    wire:click="confirmCloseConsultation">
                                    Cloturer la consultation
                                </flux:button>
                            </div>
                        @else
                            <div
                                class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-950/30 dark:text-emerald-200">
                                Cette consultation est cloturee. L'issue ne peut plus etre modifiee.
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>

    <x-modal id="consultation-section-modal" :title="collect($this->narrativeSections())->firstWhere('key', $currentSection)['title'] ??
        match ($currentSection) {
            'vitals' => 'Signes vitaux',
            'symptomes' => 'Symptomes',
            'laboratoire' => 'Demande laboratoire',
            'imagerie' => 'Demande imagerie',
            default => 'Edition de la consultation',
        }" size="6xl" center z-index="z-20" persistent
        x-on:consultation-section-saved.window="$tsui.close.modal('consultation-section-modal')">
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                <p class="font-semibold">{{ $this->patientIdentity() }}</p>
                <p class="mt-1 text-xs">Reference consultation: {{ $consultation->reference }}</p>
            </div>

            <flux:icon.loading wire:loading wire:target="currentSection" />

            <div wire:loading.remove>
                @if (in_array($currentSection, array_keys($this->sectionFieldMap()), true))
                    <x-textarea wire:model="textValue" label="Contenu" rows="10" maxlength="5000" count />
                @endif

                @if ($currentSection === 'symptomes')
                    <x-select.styled label="Symptomes" wire:model="symptome_ids" :request="route('api.symptomes')"
                        select="label:name|value:id" multiple hint="Selectionnez un ou plusieurs symptomes" />
                    @error('symptome_ids')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('symptome_ids.*')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                @endif

                @if ($currentSection === 'vitals')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-number wire:model="vitalsForm.poids" label="Poids (kg)" step="0.1" />
                        <x-number wire:model="vitalsForm.temperature" label="Temperature" step="0.1" />
                        <x-number wire:model="vitalsForm.taille" label="Taille (cm)" step="0.1" />
                        <x-number wire:model="vitalsForm.systolite" label="Systolique" />
                        <x-number wire:model="vitalsForm.diastolique" label="Diastolique" />
                        <x-number wire:model="vitalsForm.frequence_cardiaque" label="Frequence cardiaque" />
                        <x-number wire:model="vitalsForm.frequence_respiratoire" label="Frequence respiratoire" />
                        <x-number wire:model="vitalsForm.saturation_oxygene" label="Saturation oxygene" />
                        <x-number wire:model="vitalsForm.glycemie" label="Glycemie" />
                        <x-number wire:model="vitalsForm.perimetre_cranien" label="Perimetre cranien" />
                        <x-number wire:model="vitalsForm.perimetre_brachial" label="Perimetre brachial" />
                        <x-input wire:model="vitalsForm.mois" label="Periode / mois" />
                    </div>
                @endif

                @if ($currentSection === 'laboratoire')
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-input wire:model="laboratoireForm.renseignement" label="Renseignement *" />
                            <x-input wire:model="laboratoireForm.note" label="Note clinique" />
                            <x-input wire:model="laboratoireForm.antibiotique" label="Antibiotique" />
                            <x-input wire:model="laboratoireForm.commentaire" label="Commentaire" />
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Demander les
                                        examens de laboratoire</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Selection identique a la logique d initialisation de la consultation.
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                                    {{ count($laboratoireActeIds) }}
                                </span>
                            </div>

                            <div class="mt-4">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Examens de
                                    laboratoire *</p>

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    @forelse ($this->availableLaboratoireActes() as $acte)
                                        @php($isValidated = $this->isLaboratoireActeIdValidated($acte->id))
                                        <label wire:key="laboratoire-acte-{{ $acte->id }}"
                                            @class([
                                                'flex items-start gap-3 rounded-2xl border px-4 py-3 transition',
                                                'cursor-default border-emerald-200 bg-emerald-50/70 dark:border-emerald-900/60 dark:bg-emerald-950/20' => $isValidated,
                                                'cursor-pointer border-slate-200 bg-white hover:border-sky-300 hover:bg-sky-50/60 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-sky-700 dark:hover:bg-sky-950/30' => !$isValidated,
                                            ])>
                                            <input type="checkbox" value="{{ $acte->id }}"
                                                wire:model="laboratoireActeIds" @disabled($isValidated)
                                                class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 disabled:cursor-not-allowed disabled:opacity-70" />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3">
                                                    <p class="font-medium text-slate-900 dark:text-white">
                                                        {{ $acte->name }}</p>
                                                    <div class="flex shrink-0 items-center gap-2">
                                                        @if ($isValidated)
                                                            <span
                                                                class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                                                Validé
                                                            </span>
                                                        @endif
                                                        <span
                                                            class="whitespace-nowrap text-sm font-semibold text-sky-700 dark:text-sky-300">
                                                            {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                                                        </span>
                                                    </div>
                                                </div>
                                                @if ($acte->service?->name)
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                        {{ $acte->service->name }}
                                                    </p>
                                                @endif
                                            </div>
                                        </label>
                                    @empty
                                        <p class="text-sm text-slate-500 dark:text-slate-400 md:col-span-2">
                                            Aucun examen de laboratoire disponible.
                                        </p>
                                    @endforelse
                                </div>
                                @error('laboratoireActeIds')
                                    <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                @if ($currentSection === 'imagerie')
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-input wire:model="imagerieForm.renseignement" label="Renseignement *" />
                            <x-input wire:model="imagerieForm.note" label="Note" />
                            <x-input wire:model="imagerieForm.antibiotique" label="Antibiotique" />
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Demander les
                                        examens d imagerie</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Selection identique a la logique d initialisation de la consultation.
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                                    {{ count($imagerieActeIds) }}
                                </span>
                            </div>

                            <div class="mt-4">
                                <x-select.styled label="Examens d imagerie *" wire:model="imagerieActeIds"
                                    :request="[
                                        'url' => route('api.actes'),
                                        'params' => ['departement' => $this->imagerieDepartement()?->id],
                                    ]" select="label:name|value:id" multiple />
                                @error('imagerieActeIds')
                                    <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                @if ($currentSection === 'prescription')
                    <div class="space-y-3">
                        <x-textarea wire:model="prescriptionNote" label="Prescription medicale" rows="8"
                            maxlength="5000" count />
                        <div
                            class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                            <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                Ajouter un medicament a la prescription (sans ID manuel)
                            </p>
                            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                <x-select.styled label="Medicament *" wire:model="prescriptionMedicamentId"
                                    :options="$this->availableMedicaments()
                                        ->map(
                                            fn($m) => [
                                                'label' => $m->name . ' (' . $m->reference . ')',
                                                'value' => $m->id,
                                            ],
                                        )
                                        ->values()
                                        ->all()" select="label:label|value:value" />
                                <x-number label="Quantite *" wire:model="prescriptionQty" />
                                <x-input label="Posologie" wire:model="prescriptionPosologie"
                                    placeholder="Ex: 1 cp matin/soir" />
                            </div>
                            <div class="mt-3 flex justify-end">
                                <flux:button wire:click="addPrescriptionItem" variant="primary" color="emerald">
                                    Ajouter au traitement
                                </flux:button>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/60">
                            <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Lignes de prescription
                            </p>
                            <div class="space-y-2">
                                @forelse($this->prescriptionItems() as $item)
                                    <div
                                        class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                {{ $item->name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                                Qte: {{ (int) $item->pivot->nbr }} | Servie:
                                                {{ (int) $item->pivot->qte_servie }} | Posologie:
                                                {{ $item->pivot->posologie ?: '-' }}
                                            </p>
                                        </div>
                                        <flux:button size="xs" variant="danger"
                                            wire:click="removePrescriptionItem({{ $item->id }})">
                                            Retirer
                                        </flux:button>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Aucun medicament ajoute pour
                                        cette consultation.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <flux:button
                                href="{{ route('pharmacie.prescriptions', ['consultation' => $consultation->id]) }}"
                                wire:navigate variant="primary" color="emerald">
                                Ouvrir dans la pharmacie
                            </flux:button>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('consultation-section-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="primary" color="sky" wire:click="saveEditor">
                    Enregistrer
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal id="rendez-vous-modal"
        title="{{ $rendezVousEditId ? 'Modifier le rendez-vous' : 'Planifier un rendez-vous' }}" size="3xl"
        center persistent x-on:rendez-vous-modal-open.window="$tsui.open.modal('rendez-vous-modal')"
        x-on:rendez-vous-modal-saved.window="$tsui.close.modal('rendez-vous-modal')">
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-violet-100 bg-violet-50/70 p-4 text-sm text-violet-900 dark:border-violet-500/20 dark:bg-violet-950/30 dark:text-violet-200">
                <p class="font-semibold">{{ $this->patientIdentity() }}</p>
                <p class="mt-1 text-xs">Les rendez-vous planifies sont lies a cette consultation.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-date wire:model="rendezVousDate" label="Date du rendez-vous *" />
                <x-input wire:model="rendezVousHeure" type="time" label="Heure *" />
            </div>

            <x-textarea wire:model="rendezVousMotif" label="Motif / instructions" rows="4" maxlength="255"
                count placeholder="Ex: Controle, resultats a apporter, consignes particulieres..." />
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('rendez-vous-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="primary" color="violet" wire:click="saveRendezVous">
                    {{ $rendezVousEditId ? 'Enregistrer' : 'Planifier' }}
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal id="rendez-vous-delete-modal" title="Supprimer le rendez-vous" size="2xl" center persistent
        x-on:rendez-vous-delete-open.window="$tsui.open.modal('rendez-vous-delete-modal')"
        x-on:rendez-vous-deleted.window="$tsui.close.modal('rendez-vous-delete-modal')">
        <p class="text-sm text-slate-600 dark:text-slate-300">
            Confirmez la suppression de ce rendez-vous planifie. La fiche et la facturation associees seront supprimees.
        </p>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('rendez-vous-delete-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="danger" wire:click="deleteRendezVous">
                    Supprimer
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal id="laboratoire-acte-note-modal" title="Ajouter une note à l'examen" size="3xl" center persistent
        x-on:laboratoire-acte-note-open.window="$tsui.open.modal('laboratoire-acte-note-modal')"
        x-on:laboratoire-acte-note-saved.window="$tsui.close.modal('laboratoire-acte-note-modal')">
        <div class="space-y-5">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                Examen : {{ $this->selectedLaboratoireActeName ?? 'Sélectionner un examen' }}
            </p>
            <x-textarea wire:model.defer="laboratoireActeNote" label="Note clinique" rows="6" maxlength="255"
                count />
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('laboratoire-acte-note-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="primary" wire:click="saveLaboratoireActeNote">
                    Enregistrer la note
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal id="consultation-close-modal" title="Cloturer la consultation" size="2xl" center persistent
        x-on:consultation-close-open.window="$tsui.open.modal('consultation-close-modal')"
        x-on:consultation-closed.window="$tsui.close.modal('consultation-close-modal')">
        <div class="space-y-4">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Vous allez cloturer definitivement cette consultation. Verifiez l'issue avant de confirmer.
            </p>
            @if (filled($consultation->issue))
                <div
                    class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Issue</p>
                    <p class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $this->issueLabel() }}</p>
                    @if (filled($consultation->autre_issue))
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $consultation->autre_issue }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('consultation-close-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="primary" color="emerald" wire:click="closeConsultation">
                    Confirmer la cloture
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal id="laboratoire-acte-delete-modal" title="Confirmer la suppression" size="3xl" center persistent
        x-on:laboratoire-acte-delete-open.window="$tsui.open.modal('laboratoire-acte-delete-modal')"
        x-on:laboratoire-acte-deleted.window="$tsui.close.modal('laboratoire-acte-delete-modal')">
        <div class="space-y-5">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Vous allez supprimer l'examen de laboratoire suivant :
            </p>
            <p class="text-base font-semibold text-slate-900 dark:text-white">
                {{ $this->selectedLaboratoireActeName ?? 'Aucun examen sélectionné' }}
            </p>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Cette action est possible uniquement pour les examens non validés.
            </p>
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('laboratoire-acte-delete-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="danger" wire:click="deleteLaboratoireActe">
                    Supprimer l'examen
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

</x-pages::consultation.layout>
