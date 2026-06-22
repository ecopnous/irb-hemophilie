<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use App\Models\Configs\GroupeExamen;
use App\Models\Consultation;
use App\Models\Imagerie;
use App\Models\Laboratoire;
use App\Models\liaison\Image;
use App\Models\prescription\Medicament;
use App\Models\prescription\Prescription;
use App\Services\Consultation\ClinicalExamService;
use App\Services\ConsultationContextBuilder;
use App\Services\GeminiService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
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
    public ?int $groupeExamenId = null;
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

    public bool $showAiModal = false;
    public bool $aiAnalyzing = false;
    public ?string $aiAnalysis = null;
    public ?string $aiError = null;

    /** @var array<string, array<string, mixed>> */
    public array $clinicalExamForm = [];

    /** @var array{examined_at: string, synthesis: string} */
    public array $clinicalExamMeta = [
        'examined_at' => '',
        'synthesis' => '',
    ];

    public function mount(int $id): void
    {
        $this->loadConsultation($id);
    }

    public function loadConsultation(int $id): void
    {
        $this->consultation = Consultation::query()
            ->with(['dossierPatient', 'departement', 'service', 'user', 'projet.assurance.categorisation', 'assurance.categorisation', 'laboratoire.images', 'imagerie', 'prescription.medicaments', 'actes.departement', 'actes.service', 'consultationSource', 'symptomeItems', 'clinicalExam.filledBy', 'clinicalExamValues.definition'])
            ->findOrFail($id);

        $this->syncIssueForm();
        $this->syncSymptomeForm();
    }

    public function syncSymptomeForm(): void
    {
        $this->symptome_ids = $this->consultation->symptomeItems
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function updatedSymptomeIds(): void
    {
        if ($this->consultation->is_clore) {
            $this->syncSymptomeForm();

            return;
        }

        $this->saveSymptomes();
    }

    protected function saveSymptomes(): void
    {
        $validated = $this->validate([
            'symptome_ids' => ['nullable', 'array'],
            'symptome_ids.*' => ['exists:symptomes,id'],
        ]);

        $this->consultation->symptomeItems()->sync(
            collect($validated['symptome_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
        );

        $this->consultation->load('symptomeItems');
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
        $this->groupeExamenId = null;

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

    public function updatedGroupeExamenId($value): void
    {
        if (! $value) {
            return;
        }

        $groupe = GroupeExamen::query()
            ->active()
            ->with('actes:id')
            ->find((int) $value);

        if (! $groupe) {
            return;
        }

        $this->laboratoireActeIds = collect($this->laboratoireActeIds)
            ->merge($groupe->actes->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->merge($this->validatedLaboratoireActeIds())
            ->unique()
            ->values()
            ->all();
    }

    public function selectedLaboratoireActesPreview(): Collection
    {
        if ($this->laboratoireActeIds === []) {
            return collect();
        }

        return Acte::query()
            ->with(['service', 'departement'])
            ->whereIn('id', $this->laboratoireActeIds)
            ->orderBy('name')
            ->get();
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

        $this->loadConsultation($this->consultation->id);
        $this->dispatch('consultation-section-saved');
    }

    public function sectionFieldMap(): array
    {
        return [
            'complement_anamnese' => 'complement_anamnese',
            'diagnostic_presomption' => 'diagnostic_presomption',
            'diagnostic_certitude' => 'diagnostic_certitude',
            'plan_traitement_conduite' => 'plan_traitement_conduite',
        ];
    }

    public function narrativeSections(): array
    {
        return [
            ['key' => 'complement_anamnese', 'title' => 'Complement d\'anamnese', 'preview' => $this->consultation->complement_anamnese],
            ['key' => 'diagnostic_presomption', 'title' => 'Diagnostic de presomption', 'preview' => $this->consultation->diagnostic_presomption],
            ['key' => 'diagnostic_certitude', 'title' => 'Diagnostic de certitude', 'preview' => $this->consultation->diagnostic_certitude],
            ['key' => 'plan_traitement_conduite', 'title' => 'Plan de traitement et conduite a tenir', 'preview' => $this->consultation->plan_traitement_conduite],
        ];
    }

    public function narrativeSection(string $key): array
    {
        return collect($this->narrativeSections())->firstWhere('key', $key) ?? [
            'key' => $key,
            'title' => $key,
            'preview' => null,
        ];
    }

    public function narrativeSectionTitle(?string $key): string
    {
        return collect($this->narrativeSections())->firstWhere('key', $key)['title']
            ?? match ($key) {
                'vitals' => 'Signes vitaux',
                'laboratoire' => 'Demande laboratoire',
                'imagerie' => 'Demande imagerie',
                'prescription' => 'Prescription medicale',
                default => 'Edition de la consultation',
            };
    }

    public function openClinicalExamEditor(): void
    {
        $this->resetValidation();
        $form = app(ClinicalExamService::class)->toFormArray($this->consultation);
        $this->clinicalExamMeta = [
            'examined_at' => $form['examined_at'] ?? now()->format('Y-m-d'),
            'synthesis' => $form['synthesis'],
        ];
        $this->clinicalExamForm = $form['fields'];
        $this->dispatch('clinical-exam-modal-open');
    }

    public function saveClinicalExam(): void
    {
        $this->validate(app(ClinicalExamService::class)->validationRules());

        app(ClinicalExamService::class)->sync($this->consultation, [
            'examined_at' => $this->clinicalExamMeta['examined_at'] ?: null,
            'synthesis' => $this->clinicalExamMeta['synthesis'] ?: null,
            'fields' => $this->clinicalExamForm,
        ]);

        $this->loadConsultation($this->consultation->id);
        $this->dispatch('clinical-exam-saved');
    }

    /**
     * @return array{answered: int, total: int, percent: int}
     */
    #[Computed]
    public function clinicalExamProgress(): array
    {
        return app(ClinicalExamService::class)->progress($this->consultation);
    }

    public function hasClinicalExamData(): bool
    {
        return app(ClinicalExamService::class)->hasData($this->consultation);
    }

    /**
     * @return Collection<int, array{section: array{key: string, label: string}, rows: Collection<int, array{definition: \App\Models\ClinicalExamFieldDefinition, answer: \App\Models\ConsultationClinicalExamValue|null}>}>
     */
    public function clinicalExamPresentation(): Collection
    {
        return app(ClinicalExamService::class)->presentationSections($this->consultation);
    }

    /**
     * @return array<int, array{key: string, label: string, fields: Collection<int, \App\Models\ClinicalExamFieldDefinition>}>
     */
    public function clinicalExamSections(): array
    {
        return app(ClinicalExamService::class)->sections();
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
                    'type_visite' => $owner->type_visite,
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

        return [['label' => 'Reference', 'value' => $rdv->reference], ['label' => 'Type de fiche', 'value' => ucfirst((string) $rdv->type_visite)], ['label' => 'Departement', 'value' => $rdv->departement?->name ?: '-'], ['label' => 'Service', 'value' => $rdv->service?->name ?: '-'], ['label' => 'Medecin', 'value' => $rdv->user?->name ?: 'Non assigne'], ['label' => 'Projet', 'value' => $rdv->projet?->name ?: '-'], ['label' => 'Prise en charge', 'value' => $rdv->assurance?->name ?: 'Paiement direct'], ['label' => 'Periode', 'value' => $rdv->mois ?: '-']];
    }

    public function hasContent(?string $value): bool
    {
        return filled($value);
    }

    public function runDiagnosisAnalysis(): void
    {
        $this->aiError = null;
        $this->aiAnalysis = null;
        $this->aiAnalyzing = true;
        $this->showAiModal = true;

        $payload = app(ConsultationContextBuilder::class)->build($this->consultation);

        if (! $payload['has_data']) {
            $this->aiError = 'Renseignez au minimum des symptômes, une anamnèse, un examen clinique ou des constantes vitales avant de lancer l\'analyse.';
            $this->aiAnalyzing = false;

            return;
        }

        $result = app(GeminiService::class)->analyzeConsultationDiagnosis($payload['context']);

        if (blank($result['text'])) {
            $this->aiError = $result['user_error'];
        } else {
            $this->aiAnalysis = trim($result['text']);
        }

        $this->aiAnalyzing = false;
    }

    public function closeAiModal(): void
    {
        $this->showAiModal = false;
    }

    #[Computed]
    public function aiAnalysisHtml(): string
    {
        if (blank($this->aiAnalysis)) {
            return '';
        }

        return Str::markdown($this->aiAnalysis, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
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
        $priseEnCharge = $this->consultation->projet?->name
            ?: ($this->consultation->effectiveAssurance()?->name ?: 'Paiement direct');

        if ($this->consultation->effectiveAssurance() && $this->consultation->coverageRate() > 0) {
            $priseEnCharge .= sprintf(
                ' (%s — %s%%)',
                $this->consultation->coverageCategoryName(),
                number_format($this->consultation->coverageRate(), 0, ',', ' ')
            );
        }

        return [['label' => 'Reference', 'value' => $this->consultation->reference], ['label' => 'Type', 'value' => $this->consultation->type === 'consultation' ? 'Visite' : 'Examen'], ['label' => 'Fiche', 'value' => ucfirst((string) $this->consultation->type_visite)], ['label' => 'Periode', 'value' => $this->consultation->mois ?: '-'], ['label' => 'Departement', 'value' => $this->consultation->departement?->name ?: '-'], ['label' => 'Service', 'value' => $this->consultation->service?->name ?: '-'], ['label' => 'Medecin', 'value' => $this->consultation->user?->name ?: 'Non assigne'], ['label' => 'Projet', 'value' => $this->consultation->projet?->name ?: '-'], ['label' => 'Prise en charge', 'value' => $priseEnCharge], ['label' => 'Date creation', 'value' => optional($this->consultation->created_at)->format('d/m/Y H:i') ?: '-']];
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

    public function laboratoireImages(): Collection
    {
        $laboratoire = $this->consultation->laboratoire;

        if (! $laboratoire) {
            return collect();
        }

        return $laboratoire->images
            ->sortByDesc('created_at')
            ->values();
    }

    public function imagerieActes()
    {
        return $this->consultation->actes->filter(fn($acte) => $this->acteBelongsToSection($acte, 'imagerie'))->values();
    }

    protected function imagerieActePivotId(int $acteId): ?int
    {
        return DB::table('acte_consultation')
            ->where('consultation_id', $this->consultation->id)
            ->where('acte_id', $acteId)
            ->value('id');
    }

    public function imagerieActeImages(int $acteId): Collection {
        $pivotId = $this->imagerieActePivotId($acteId);

        if (! $pivotId) {
            return collect();
        }

        return Image::query()
            ->where('acte_consultation_id', $pivotId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function imagerieImagesTotal(): int
    {
        return $this->imagerieActes()->sum(
            fn ($acte) => $this->imagerieActeImages((int) $acte->id)->count(),
        );
    }

    public function imagerieImagesByActe(): Collection {
        return $this->imagerieActes()
            ->map(fn ($acte) => (object) [
                'acte_id' => (int) $acte->id,
                'acte_name' => $acte->name,
                'images' => $this->imagerieActeImages((int) $acte->id),
            ])
            ->filter(fn ($group) => $group->images->isNotEmpty())
            ->values();
    }

    protected function laboratoireDepartement(): ?Departement
    {
        return Departement::query()->where('name', 'like', '%laboratoire%')->orWhere('ref', 'labo')->first();
    }

    protected function imagerieDepartement(): ?Departement
    {
        return Departement::query()->where('name', 'like', '%imagerie%')->orWhere('ref', 'img')->first();
    }

    protected function syncSectionActes(string $section, array $selectedIds): void {
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
                                <x-button wire:click="openEditor('vitals')" sm
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

                    {{-- 1. Symptomes (edition inline) --}}
                    <section
                        class="rounded-md border border-rose-200 bg-white shadow-sm dark:border-rose-500/25 dark:bg-slate-950/70">
                        <div
                            class="border-b border-rose-100 bg-linear-to-r from-rose-50 to-white px-5 py-4 dark:border-rose-500/20 dark:from-rose-950/40 dark:to-slate-950/70">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-rose-600 dark:text-rose-300">
                                        Anamnese
                                    </p>
                                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Symptomes</h3>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-bold {{ $this->hasSymptomes() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ $this->consultation->symptomeItems->count() }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-4 px-5 py-5">
                            @unless ($consultation->is_clore)
                                <x-select.styled label="Selectionner les symptomes"
                                    wire:model.live.debounce.500ms="symptome_ids" :request="route('api.symptomes')"
                                    select="label:name|value:id" multiple searchable
                                    hint="Enregistrement automatique a chaque modification" />
                                @error('symptome_ids')
                                    <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                                @error('symptome_ids.*')
                                    <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            @endunless

                            <div wire:loading.class="opacity-60" wire:target="symptome_ids"
                                class="flex flex-wrap gap-2 transition-opacity">
                                @forelse ($this->consultation->symptomeItems as $symptome)
                                    <span wire:key="symptome-{{ $symptome->id }}"
                                        class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-800 dark:border-rose-500/25 dark:bg-rose-500/10 dark:text-rose-300">
                                        {{ $symptome->name }}
                                    </span>
                                @empty
                                    <p class="text-sm italic text-slate-500 dark:text-slate-400">
                                        Aucun symptome enregistre.
                                    </p>
                                @endforelse
                            </div>

                            <p wire:loading wire:target="symptome_ids"
                                class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                Enregistrement en cours...
                            </p>
                        </div>
                    </section>

                    {{-- 2. Complement d'anamnese --}}
                    <section
                        class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div
                            class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full {{ $this->sectionHasContent($this->narrativeSection('complement_anamnese')) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                        {{ $this->narrativeSection('complement_anamnese')['title'] }}
                                    </h3>
                                </div>
                                @unless ($consultation->is_clore)
                                    <x-button wire:click="openEditor('complement_anamnese')" sm
                                        x-on:click="$tsui.open.modal('consultation-section-modal')" icon="pencil-square">
                                        {{ $this->sectionHasContent($this->narrativeSection('complement_anamnese')) ? 'Editer' : 'Ajouter' }}
                                    </x-button>
                                @endunless
                            </div>
                        </div>
                        <div class="px-5 py-5">
                            @if (blank($this->narrativeSection('complement_anamnese')['preview']))
                                <p class="text-sm italic text-slate-500 dark:text-slate-400">
                                    Aucune information enregistree.
                                </p>
                            @else
                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                                    {{ $this->narrativeSection('complement_anamnese')['preview'] }}
                                </p>
                            @endif
                        </div>
                    </section>

                    {{-- 3. Examen clinique structure --}}
                    <section
                        class="rounded-md border border-teal-200 bg-white shadow-sm dark:border-teal-500/25 dark:bg-slate-950/70">
                        <div
                            class="border-b border-teal-100 bg-linear-to-r from-teal-50 to-white px-5 py-4 dark:border-teal-500/20 dark:from-teal-950/40 dark:to-slate-950/70">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-teal-600 dark:text-teal-300">
                                        Examen clinique
                                    </p>
                                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                                        Constats physiques & synthèse
                                    </h3>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Complétion : {{ $this->clinicalExamProgress['answered'] }}/{{ $this->clinicalExamProgress['total'] }} ({{ $this->clinicalExamProgress['percent'] }}%)
                                    </p>
                                </div>
                                <x-button wire:click="openClinicalExamEditor" sm
                                    x-on:click="$tsui.open.modal('clinical-exam-modal')" icon="pencil-square">
                                    {{ $this->hasClinicalExamData() ? 'Editer' : 'Renseigner' }}
                                </x-button>
                            </div>
                        </div>

                        <div class="px-5 py-5">
                            @if ($this->consultation->clinicalExam?->examined_at)
                                <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Date examen : {{ $this->consultation->clinicalExam->examined_at->format('d/m/Y') }}
                                    @if ($this->consultation->clinicalExam->filledBy)
                                        · Rempli par {{ $this->consultation->clinicalExam->filledBy->name }}
                                    @endif
                                </p>
                            @endif

                            @if ($this->clinicalExamPresentation()->isEmpty() && blank($this->consultation->clinicalExam?->synthesis))
                                <p class="text-sm italic text-slate-500 dark:text-slate-400">
                                    Aucun élément d'examen clinique enregistré. Renseignez les sections selon la fiche standard.
                                </p>
                            @else
                                <div class="grid gap-4 lg:grid-cols-2">
                                    @foreach ($this->clinicalExamPresentation() as $section)
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                                            <h4 class="mb-3 text-xs font-black uppercase tracking-[0.18em] text-teal-700 dark:text-teal-300">
                                                {{ $section['section']['label'] }}
                                            </h4>
                                            @foreach ($section['rows'] as $row)
                                                <x-consultation.clinical-exam-field-display
                                                    :definition="$row['definition']"
                                                    :answer="$row['answer']"
                                                    wire:key="clinical-display-{{ $row['definition']->key }}"
                                                />
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>

                                @if (filled($this->consultation->clinicalExam?->synthesis))
                                    <div class="mt-4 rounded-2xl border border-teal-200 bg-teal-50/50 p-4 dark:border-teal-500/20 dark:bg-teal-500/10">
                                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-teal-700 dark:text-teal-300">
                                            Synthèse médicale
                                        </p>
                                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                                            {{ $this->consultation->clinicalExam->synthesis }}
                                        </p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </section>

                    {{-- 4. Diagnostic de presomption --}}
                    <section
                        class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div
                            class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full {{ $this->sectionHasContent($this->narrativeSection('diagnostic_presomption')) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                        {{ $this->narrativeSection('diagnostic_presomption')['title'] }}
                                    </h3>
                                </div>
                                @unless ($consultation->is_clore)
                                    <x-button wire:click="openEditor('diagnostic_presomption')" sm
                                        x-on:click="$tsui.open.modal('consultation-section-modal')" icon="pencil-square">
                                        {{ $this->sectionHasContent($this->narrativeSection('diagnostic_presomption')) ? 'Editer' : 'Ajouter' }}
                                    </x-button>
                                @endunless
                            </div>
                        </div>
                        <div class="px-5 py-5">
                            @if (blank($this->narrativeSection('diagnostic_presomption')['preview']))
                                <p class="text-sm italic text-slate-500 dark:text-slate-400">
                                    Aucune information enregistree.
                                </p>
                            @else
                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                                    {{ $this->narrativeSection('diagnostic_presomption')['preview'] }}
                                </p>
                            @endif
                        </div>
                    </section>

                    {{-- 5. Diagnostic de certitude --}}
                    <section
                        class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div
                            class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full {{ $this->sectionHasContent($this->narrativeSection('diagnostic_certitude')) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                        {{ $this->narrativeSection('diagnostic_certitude')['title'] }}
                                    </h3>
                                </div>
                                @unless ($consultation->is_clore)
                                    <x-button wire:click="openEditor('diagnostic_certitude')" sm
                                        x-on:click="$tsui.open.modal('consultation-section-modal')" icon="pencil-square">
                                        {{ $this->sectionHasContent($this->narrativeSection('diagnostic_certitude')) ? 'Editer' : 'Ajouter' }}
                                    </x-button>
                                @endunless
                            </div>
                        </div>
                        <div class="px-5 py-5">
                            @if (blank($this->narrativeSection('diagnostic_certitude')['preview']))
                                <p class="text-sm italic text-slate-500 dark:text-slate-400">
                                    Aucune information enregistree.
                                </p>
                            @else
                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                                    {{ $this->narrativeSection('diagnostic_certitude')['preview'] }}
                                </p>
                            @endif
                        </div>
                    </section>

                    <section
                        class="rounded-md border border-violet-200 bg-linear-to-r from-violet-50/80 to-indigo-50/50 shadow-sm dark:border-violet-500/20 dark:from-violet-950/30 dark:to-indigo-950/20">
                        <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-violet-600 dark:text-violet-300">
                                    Aide a la decision
                                </p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    Orientation diagnostique basee sur les elements cliniques saisis (ne remplace pas le jugement medical).
                                </p>
                            </div>
                            <flux:button
                                variant="primary"
                                icon="sparkles"
                                wire:click="runDiagnosisAnalysis"
                                wire:loading.attr="disabled"
                                wire:target="runDiagnosisAnalysis"
                                class="shrink-0"
                            >
                                <span wire:loading.remove wire:target="runDiagnosisAnalysis">Aide au diagnostic</span>
                                <span wire:loading wire:target="runDiagnosisAnalysis">Analyse en cours…</span>
                            </flux:button>
                        </div>
                    </section>

                    @include('pages.consultation.partials.show-laboratoire')

                    @include('pages.consultation.partials.show-imagerie')

                    {{-- 8. Prescription medicale --}}
                    <section
                        class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div
                            class="rounded-md border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Cure
                                        et traitement</p>
                                    <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Prescription
                                        medicale</h3>
                                </div>
                                @unless ($consultation->is_clore)
                                    <x-button wire:click="openEditor('prescription')" sm
                                        x-on:click="$tsui.open.modal('consultation-section-modal')" icon="document-plus">
                                        Prescrire
                                    </x-button>
                                @endunless
                            </div>
                        </div>

                        <div class="space-y-4 px-5 py-5">
                            <div
                                class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Note medicale</p>
                                <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">
                                    {{ $this->consultation->prescription_medicale ?: 'Aucune note de prescription enregistree.' }}
                                </p>
                            </div>

                            <div>
                                <div class="mb-3 flex items-center justify-between">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Medicaments prescrits
                                    </p>
                                    <span
                                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ $this->prescriptionItems()->count() }}
                                    </span>
                                </div>
                                <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                                    <table class="min-w-full border-collapse bg-white text-sm dark:bg-slate-950/40">
                                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                                            <tr
                                                class="text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
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
                                                        <p class="font-semibold text-slate-900 dark:text-white">
                                                            {{ $item->name }}</p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                                            {{ $item->reference }}</p>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">{{ (int) $item->pivot->nbr }}</td>
                                                    <td class="px-4 py-3 text-center">{{ (int) $item->pivot->qte_servie }}
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                        {{ $item->pivot->posologie ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4"
                                                        class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
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

                    {{-- 9. Plan et conduite a tenir --}}
                    <section
                        class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div
                            class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full {{ $this->sectionHasContent($this->narrativeSection('plan_traitement_conduite')) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                        {{ $this->narrativeSection('plan_traitement_conduite')['title'] }}
                                    </h3>
                                </div>
                                @unless ($consultation->is_clore)
                                    <x-button wire:click="openEditor('plan_traitement_conduite')" sm
                                        x-on:click="$tsui.open.modal('consultation-section-modal')" icon="pencil-square">
                                        {{ $this->sectionHasContent($this->narrativeSection('plan_traitement_conduite')) ? 'Editer' : 'Ajouter' }}
                                    </x-button>
                                @endunless
                            </div>
                        </div>
                        <div class="px-5 py-5">
                            @if (blank($this->narrativeSection('plan_traitement_conduite')['preview']))
                                <p class="text-sm italic text-slate-500 dark:text-slate-400">
                                    Aucune information enregistree.
                                </p>
                            @else
                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                                    {{ $this->narrativeSection('plan_traitement_conduite')['preview'] }}
                                </p>
                            @endif
                        </div>
                    </section>

                    {{-- 10. Rendez-vous medical --}}
                    <section
                        class="overflow-hidden rounded-md border border-violet-200 bg-white shadow-sm dark:border-violet-500/25 dark:bg-slate-950/70">
                        <div
                            class="border-b border-violet-100 bg-linear-to-r from-violet-50 to-white px-5 py-4 dark:border-violet-500/20 dark:from-violet-950/40 dark:to-slate-950/70">
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
                                        {{ $this->programmedRendezVousList()->count() }}
                                    </span>
                                    <x-button wire:click="openRendezVousModal" sm icon="plus"
                                        x-on:click="$tsui.open.modal('rendez-vous-modal')">
                                        Ajouter un RDV
                                    </x-button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 px-5 py-5">
                            @forelse ($this->programmedRendezVousList() as $rdv)
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
                @else
                    @include('pages.consultation.partials.show-laboratoire')

                    @include('pages.consultation.partials.show-imagerie')
                @endif

                {{-- 11. Issue de la consultation --}}
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

    <x-modal id="consultation-section-modal" :title="$this->narrativeSectionTitle($currentSection)" size="6xl" center z-index="z-20" persistent
        x-on:consultation-section-saved.window="$tsui.close.modal('consultation-section-modal')">
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                <p class="font-semibold">{{ $this->patientIdentity() }}</p>
                <p class="mt-1 text-xs">Reference consultation: {{ $consultation->reference }}</p>
            </div>

            <flux:icon.loading wire:loading wire:target="openEditor" />

            <div wire:loading.remove wire:target="openEditor">
                @if (in_array($currentSection, array_keys($this->sectionFieldMap()), true))
                    <x-textarea wire:model="textValue" label="Contenu" rows="10" maxlength="5000" count />
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
                                        Choisissez un groupe préconfiguré ou sélectionnez les examens individuellement.
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                                    {{ count($laboratoireActeIds) }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-4">
                                <x-select.styled label="Groupe d'examens (sélection rapide)"
                                    wire:model.live="groupeExamenId" placeholder="Choisir un groupe préconfiguré..."
                                    :request="route('api.groupeExamens')" select="label:name|value:id" searchable
                                    hint="Ajoute automatiquement les examens du groupe à la sélection ci-dessous." />

                                <x-select.styled label="Examens de laboratoire *" wire:model.live="laboratoireActeIds"
                                    placeholder="Rechercher et sélectionner des examens..."
                                    :request="[
                                        'url' => route('api.actes'),
                                        'params' => ['departement' => $this->laboratoireDepartement()?->id],
                                    ]" select="label:name|value:id" multiple searchable />

                                @error('laboratoireActeIds')
                                    <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                                @enderror

                                @if ($this->selectedLaboratoireActesPreview()->isNotEmpty())
                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach ($this->selectedLaboratoireActesPreview() as $acte)
                                            <div wire:key="laboratoire-acte-preview-{{ $acte->id }}"
                                                @class([
                                                    'rounded-2xl border px-4 py-3',
                                                    'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900/60 dark:bg-emerald-950/20' => $this->isLaboratoireActeIdValidated($acte->id),
                                                    'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900' => ! $this->isLaboratoireActeIdValidated($acte->id),
                                                ])>
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="font-medium text-slate-900 dark:text-white">
                                                            {{ $acte->name }}</p>
                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                            {{ $acte->service?->name ?: ($acte->departement?->name ?: 'Laboratoire') }}
                                                        </p>
                                                    </div>
                                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                                        @if ($this->isLaboratoireActeIdValidated($acte->id))
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
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Aucun examen sélectionné. Utilisez un groupe ou la liste déroulante.
                                    </p>
                                @endif
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

    <x-modal id="clinical-exam-modal" title="Examen clinique" size="4xl" center persistent scrollable
        x-on:clinical-exam-saved.window="$tsui.close.modal('clinical-exam-modal')">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Date de l'examen</label>
                    <input type="date" wire:model="clinicalExamMeta.examined_at"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @error('clinicalExamMeta.examined_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Renseignez les sections selon la fiche standard. Les champs vides ne seront pas enregistrés.
                    </p>
                </div>
            </div>

            @foreach ($this->clinicalExamSections() as $section)
                <div wire:key="clinical-section-{{ $section['key'] }}" class="space-y-3">
                    <h4 class="border-b border-slate-200 pb-2 text-xs font-black uppercase tracking-[0.2em] text-teal-700 dark:border-slate-700 dark:text-teal-300">
                        {{ $section['label'] }}
                    </h4>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($section['fields'] as $definition)
                            <x-consultation.clinical-exam-field-editor
                                :definition="$definition"
                                :wire-key="$definition->key"
                                :present="$clinicalExamForm[$definition->key]['present'] ?? null"
                                wire:key="clinical-editor-{{ $definition->key }}"
                            />
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div>
                <x-textarea wire:model="clinicalExamMeta.synthesis" label="Commentaire médical et synthèse"
                    rows="6" maxlength="2000" count
                    placeholder="Synthèse globale de l'examen clinique (250 mots max recommandé)..." />
            </div>
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('clinical-exam-modal')">
                    Annuler
                </flux:button>
                <flux:button variant="primary" wire:click="saveClinicalExam" wire:loading.attr="disabled" wire:target="saveClinicalExam">
                    <span wire:loading.remove wire:target="saveClinicalExam">Enregistrer l'examen</span>
                    <span wire:loading wire:target="saveClinicalExam">Enregistrement…</span>
                </flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

    <flux:modal wire:model.self="showAiModal" class="max-w-3xl">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Aide au diagnostic</flux:heading>
                <flux:subheading>
                    {{ $this->patientIdentity() }} · {{ $consultation->reference }}
                </flux:subheading>
            </div>

            @if ($aiAnalyzing)
                <div class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-6 py-12 dark:border-slate-800 dark:bg-slate-900/50">
                    <flux:icon.arrow-path class="size-8 animate-spin text-violet-600" />
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Analyse des éléments cliniques en cours…</p>
                </div>
            @elseif ($aiError)
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>Analyse indisponible</flux:callout.heading>
                    {{ $aiError }}
                </flux:callout>
            @elseif ($aiAnalysis)
                <div class="ai-analysis-markdown rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    {!! $this->aiAnalysisHtml !!}
                </div>
                <p class="text-xs text-slate-500">
                    Généré par IA — hypothèses orientatives à valider par le praticien. Ne constitue pas un diagnostic définitif.
                </p>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeAiModal">Fermer</flux:button>
                @if (! $aiAnalyzing && $aiAnalysis)
                    <flux:button variant="primary" icon="sparkles" wire:click="runDiagnosisAnalysis" wire:loading.attr="disabled" wire:target="runDiagnosisAnalysis">
                        Relancer l'analyse
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:modal>

    <style>
        .ai-analysis-markdown h3 {
            margin-top: 0;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgb(79 70 229);
        }
        .dark .ai-analysis-markdown h3 {
            color: rgb(165 180 252);
        }
        .ai-analysis-markdown h3 + ul {
            margin-top: 0;
        }
        .ai-analysis-markdown ul {
            margin: 0 0 1.25rem 0;
            padding-left: 1.25rem;
            list-style-type: disc;
        }
        .ai-analysis-markdown li {
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
            line-height: 1.5;
            color: rgb(51 65 85);
        }
        .dark .ai-analysis-markdown li {
            color: rgb(203 213 225);
        }
        .ai-analysis-markdown h3:last-of-type + p,
        .ai-analysis-markdown p:last-child {
            margin-bottom: 0;
            font-size: 0.875rem;
            line-height: 1.625;
            color: rgb(30 41 59);
        }
        .dark .ai-analysis-markdown h3:last-of-type + p,
        .dark .ai-analysis-markdown p:last-child {
            color: rgb(226 232 240);
        }
        .ai-analysis-markdown strong {
            font-weight: 600;
            color: rgb(15 23 42);
        }
        .dark .ai-analysis-markdown strong {
            color: rgb(248 250 252);
        }
    </style>

</x-pages::consultation.layout>
