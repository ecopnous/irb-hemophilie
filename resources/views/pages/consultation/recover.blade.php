<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use App\Models\Configs\GroupeExamen;
use App\Models\Consultation;
use App\Models\Imagerie;
use App\Models\Laboratoire;
use App\Models\prescription\Prescription;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche de consultation')] class extends Component {
    public Consultation $consultation;
    public ?string $currentSection = null;

    public string $textValue = '';
    public ?string $issueValue = null;
    public array $vitalsForm = [];
    public array $laboratoireForm = [];
    public array $imagerieForm = [];
    public array $laboratoireActeIds = [];
    public ?int $groupeExamenId = null;
    public array $imagerieActeIds = [];
    public string $prescriptionNote = '';
    public string $rendezVousValue = '';

    public function mount(int $id): void
    {
        $this->loadConsultation($id);
    }

    public function loadConsultation(int $id): void
    {
        $this->consultation = Consultation::query()
            ->with(['dossierPatient', 'laboratoire', 'imagerie', 'prescription', 'actes.departement', 'actes.service'])
            ->findOrFail($id);
    }

    public function openEditor(string $section): void
    {
        $this->resetValidation();
        $this->currentSection = $section;
        $this->textValue = '';
        $this->issueValue = $this->consultation->issue;
        $this->prescriptionNote = (string) ($this->consultation->prescription_medicale ?? '');
        $this->rendezVousValue = (string) ($this->consultation->rendez_vous_medical ?? '');

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

            $field = $fieldMap[$this->currentSection];
            $this->consultation->update([$field => $validated['textValue'] ?: null]);
        }

        if ($this->currentSection === 'issue') {
            $validated = $this->validate([
                'issueValue' => ['nullable', 'in:Ambulatoire'],
            ]);

            $this->consultation->update([
                'issue' => $validated['issueValue'] ?: null,
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
            $validated = $this->validate([
                'laboratoireForm.renseignement' => ['required', 'string', 'max:255'],
                'laboratoireForm.note' => ['nullable', 'string', 'max:255'],
                'laboratoireForm.antibiotique' => ['nullable', 'string', 'max:255'],
                'laboratoireForm.commentaire' => ['nullable', 'string', 'max:255'],
                'laboratoireActeIds' => ['required', 'array', 'min:1'],
                'laboratoireActeIds.*' => ['exists:actes,id'],
            ]);

            Laboratoire::query()->updateOrCreate(['consultation_id' => $this->consultation->id], array_merge($validated['laboratoireForm'], ['consultation_id' => $this->consultation->id]));
            $this->syncSectionActes('laboratoire', $validated['laboratoireActeIds']);
        }

        if ($this->currentSection === 'imagerie') {
            $validated = $this->validate([
                'imagerieForm.renseignement' => ['required', 'string', 'max:255'],
                'imagerieForm.note' => ['nullable', 'string', 'max:255'],
                'imagerieForm.antibiotique' => ['nullable', 'string', 'max:255'],
                'imagerieActeIds' => ['required', 'array', 'min:1'],
                'imagerieActeIds.*' => ['exists:actes,id'],
            ]);

            Imagerie::query()->updateOrCreate(['consultation_id' => $this->consultation->id], array_merge($validated['imagerieForm'], ['consultation_id' => $this->consultation->id]));
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

        if ($this->currentSection === 'rendez_vous') {
            $validated = $this->validate([
                'rendezVousValue' => ['nullable', 'string', 'max:255'],
            ]);

            $this->consultation->update([
                'rendez_vous_medical' => $validated['rendezVousValue'] ?: null,
            ]);
        }

        $this->loadConsultation($this->consultation->id);
        $this->dispatch('consultation-section-saved');
    }

    public function sectionFieldMap(): array
    {
        return [
            'symptomes' => 'symptomes',
            'histoire_maladie' => 'histoire_maladie',
            'antecedents' => 'antecedents',
            'allergies' => 'allergies',
            'complement_anamnese' => 'complement_anamnese',
            'examen_physique' => 'examen_physique',
            'diagnostic_presomption' => 'diagnostic_presomption',
            'diagnostic_certitude' => 'diagnostic_certitude',
            'plan_traitement_conduite' => 'plan_traitement_conduite',
        ];
    }

    public function cardDefinitions(): array
    {
        return [
            ['key' => 'symptomes', 'title' => 'Symptomes', 'preview' => $this->consultation->symptomes],
            ['key' => 'histoire_maladie', 'title' => 'Histoire de la maladie', 'preview' => $this->consultation->histoire_maladie],
            ['key' => 'antecedents', 'title' => 'Antecedents', 'preview' => $this->consultation->antecedents],
            // ['key' => 'allergies', 'title' => 'Historique des allergies', 'preview' => $this->consultation->allergies],
            ['key' => 'complement_anamnese', 'title' => 'Complement d\'anamnese', 'preview' => $this->consultation->complement_anamnese],
            ['key' => 'examen_physique', 'title' => 'Examen physique', 'preview' => $this->consultation->examen_physique],
            ['key' => 'diagnostic_presomption', 'title' => 'Diagnostic de presomption', 'preview' => $this->consultation->diagnostic_presomption],
            ['key' => 'laboratoire', 'title' => 'Examen de laboratoire', 'preview' => $this->laboratoirePreview()],
            ['key' => 'imagerie', 'title' => 'Examen d\'imagerie medicale', 'preview' => $this->imageriePreview()],
            ['key' => 'diagnostic_certitude', 'title' => 'Diagnostic de certitude', 'preview' => $this->consultation->diagnostic_certitude],
            ['key' => 'prescription', 'title' => 'Prescription medicale', 'preview' => $this->consultation->prescription_medicale],
            ['key' => 'plan_traitement_conduite', 'title' => 'Plan de traitement et conduite a tenir', 'preview' => $this->consultation->plan_traitement_conduite],
            ['key' => 'rendez_vous', 'title' => 'Rendez-vous medical', 'preview' => $this->consultation->rendez_vous_medical],
            ['key' => 'issue', 'title' => 'Issue de la consultation', 'preview' => $this->consultation->issue],
        ];
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

    public function laboratoireActes()
    {
        return $this->consultation->actes
            ->filter(function ($acte) {
                return $this->acteBelongsToSection($acte, 'laboratoire');
            })
            ->values();
    }

    public function imagerieActes()
    {
        return $this->consultation->actes
            ->filter(function ($acte) {
                return $this->acteBelongsToSection($acte, 'imagerie');
            })
            ->values();
    }

    protected function acteBelongsToSection($acte, string $section): bool
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

    protected function laboratoireDepartement(): ?Departement
    {
        return Departement::query()->where('name', 'like', '%laboratoire%')->orWhere('ref', 'labo')->first();
    }

    protected function imagerieDepartement(): ?Departement
    {
        return Departement::query()->where('name', 'like', '%imagerie%')->orWhere('ref', 'img')->first();
    }

    protected function syncSectionActes(string $section, array $selectedIds): void
    {
        $selectedIds = array_map('intval', $selectedIds);

        $existingOtherActes = $this->consultation->actes
            ->reject(fn($acte) => $this->acteBelongsToSection($acte, $section))
            ->mapWithKeys(function ($acte) {
                return [
                    $acte->id => [
                        'ref' => $acte->pivot->ref,
                        'montant' => (float) ($acte->pivot->montant ?? ($acte->montant ?? 0)),
                        'prise_en_charge' => (float) ($acte->pivot->prise_en_charge ?? 0),
                        'payer' => (bool) ($acte->pivot->payer ?? false),
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

        $selectedActes = Acte::query()
            ->with('departement')
            ->whereIn('id', $selectedIds)
            ->get()
            ->mapWithKeys(function (Acte $acte) {
                return [
                    $acte->id => [
                        'ref' => $acte->departement?->ref ?? 'GEN',
                        'montant' => (float) ($acte->montant ?? 0),
                    ],
                ];
            })
            ->toArray();

        $this->consultation->actes()->sync($existingOtherActes + $selectedActes);
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
};
?>

<x-pages::consultation.layout :patient="$this->consultation->dossierPatient">
    <div class="mx-auto grid max-w-[1600px] grid-cols-1 gap-6 pt-4 lg:grid-cols-12">
        <div class="space-y-4 lg:col-span-7">
            @foreach ($this->cardDefinitions() as $card)
                <x-card minimize="mount">
                    <x-slot:header>
                        <div class="flex items-center gap-4">
                            @if ($this->hasContent($card['preview']))
                                <x-icon name="check-badge" class="h-5 w-5 text-emerald-500" />
                            @else
                                <x-icon name="question-mark-circle" class="h-5 w-5 text-red-500" />
                            @endif
                            <b>{{ $card['title'] }}</b>
                        </div>
                    </x-slot:header>

                    <div class="space-y-4">
                        <div
                            class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 text-sm leading-6 text-gray-700 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
                            {{ $card['preview'] ?: 'Aucune information n est encore enregistree pour cette section.' }}
                        </div>

                        <div class="flex justify-end">
                            <x-button wire:click="openEditor('{{ $card['key'] }}')"
                                x-on:click="$tsui.open.modal('consultation-section-modal')" icon="pencil-square">
                                {{ $this->hasContent($card['preview']) ? 'Editer' : 'Ajouter' }}
                            </x-button>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="space-y-6 lg:col-span-5">
            <div
                class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase text-blue-600">Signes vitaux</h3>
                    <button type="button" wire:click="openEditor('vitals')"
                        x-on:click="$tsui.open.modal('consultation-section-modal')"
                        class="text-xs font-bold italic text-blue-500 hover:underline">
                        {{ $this->hasContent((string) $consultation->poids) || $this->hasContent((string) $consultation->temperature) ? 'Editer les informations' : 'Ajouter les informations' }}
                    </button>
                </div>

                @php
                    $vitals = [
                        ['val' => $consultation->poids ?: '-', 'unit' => 'kg', 'label' => 'Poids'],
                        ['val' => $consultation->temperature ?: '-', 'unit' => '°C', 'label' => 'Temperature'],
                        [
                            'val' =>
                                $consultation->systolite || $consultation->diastolique
                                    ? ($consultation->systolite ?? '-') . '/' . ($consultation->diastolique ?? '-')
                                    : '-/-',
                            'unit' => '-',
                            'label' => 'Pression artérielle',
                        ],
                        [
                            'val' => $consultation->frequence_cardiaque ?: '-',
                            'unit' => 'bpm',
                            'label' => 'Frequence cardiaque',
                        ],
                        [
                            'val' => $consultation->frequence_respiratoire ?: '-',
                            'unit' => 'cpm',
                            'label' => 'Frequence respiratoire',
                        ],
                        [
                            'val' => $consultation->saturation_oxygene ?: '-',
                            'unit' => '%',
                            'label' => 'Saturation en Oxygene',
                        ],
                        ['val' => $consultation->taille ?: '-', 'unit' => 'cm', 'label' => 'Taille'],
                        [
                            'val' => $consultation->perimetre_cranien ?: '-',
                            'unit' => 'cm',
                            'label' => 'Perimètre cranien',
                        ],
                        [
                            'val' => $consultation->perimetre_brachial ?: '-',
                            'unit' => 'cm',
                            'label' => 'Perimètre brachal',
                        ],
                        [
                            'val' => $consultation->glycemie ?: '-',
                            'unit' => '-',
                            'label' => 'Glycemie',
                        ],
                        [
                            'val' => $consultation->s ?: '-',
                            'unit' => '-',
                            'label' => 'Allergies',
                        ],
                        [
                            'val' => 'O+/AA',
                            'unit' => 'GRP/ELECTRO',
                            'label' => 'Status sérologique',
                        ],
                    ];
                @endphp

                <div
                    class="grid grid-cols-2 gap-0.5 overflow-hidden rounded-xl border border-gray-200 bg-gray-200 shadow-inner dark:border-slate-800 dark:bg-slate-800 md:grid-cols-3">
                    @foreach ($vitals as $vital)
                        <div class="group flex flex-col items-center justify-center bg-white p-4 dark:bg-slate-900">
                            <span
                                class="text-2xl font-black leading-none text-gray-800 dark:text-white">{{ $vital['val'] }}</span>
                            <span class="mt-1 text-[10px] font-bold uppercase text-gray-400">{{ $vital['unit'] }}</span>
                            <p class="mt-2 text-center text-[9px] font-medium text-gray-400 dark:text-slate-500">
                                {{ $vital['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div
                    class="mt-6 flex items-center justify-between rounded-xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                    <div>
                        <p class="text-[10px] font-black uppercase text-emerald-600">Indice de masse corporelle</p>
                        <p class="text-2xl font-black italic text-emerald-700 dark:text-emerald-400">
                            {{ $this->imc() !== null ? number_format($this->imc(), 1, ',', ' ') : '--' }}
                            <span class="text-sm font-normal">IMC</span>
                        </p>
                    </div>
                    <div class="text-right">
                        <span
                            class="rounded bg-emerald-100 px-2 py-1 text-[10px] font-bold text-emerald-600 dark:bg-emerald-800">
                            {{ $this->imcLabel() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-modal id="consultation-section-modal" :title="collect($this->cardDefinitions())->firstWhere('key', $currentSection)['title'] ??
        'Edition de la consultation'" size="5xl" center persistent
        x-on:consultation-section-saved.window="$tsui.close.modal('consultation-section-modal')">
        <div class="space-y-5">
            <div
                class="rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                <p class="font-semibold">{{ $this->patientIdentity() }}</p>
                <p class="mt-1 text-xs">Reference consultation: {{ $consultation->reference }}</p>
            </div>

            @if (in_array($currentSection, array_keys($this->sectionFieldMap()), true))
                <x-textarea wire:model="textValue" label="Contenu" rows="8" maxlength="5000" count />
            @endif

            @if ($currentSection === 'issue')
                <x-select.native wire:model="issueValue" label="Issue de la consultation" :options="[
                    ['label' => 'Aucune issue selectionnee', 'value' => ''],
                    ['label' => 'Ambulatoire', 'value' => 'Ambulatoire'],
                ]" />
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
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Demander les examens de
                                    laboratoire</p>
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
                                hint="Ajoute automatiquement les examens du groupe à la sélection." />

                            <x-select.styled label="Examens de laboratoire *" wire:model.live="laboratoireActeIds"
                                placeholder="Rechercher et sélectionner des examens..."
                                :request="[
                                    'url' => route('api.actes'),
                                    'params' => ['departement' => $this->laboratoireDepartement()?->id],
                                ]" select="label:name|value:id" multiple searchable />
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @forelse ($this->selectedLaboratoireActesPreview() as $acte)
                                <div
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $acte->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $acte->service?->name ?: ($acte->departement?->name ?: 'Laboratoire') }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500 dark:text-slate-400">Aucun examen de laboratoire n'est
                                    rattache a cette consultation.</p>
                            @endforelse
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
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Demander les examens
                                    d'imagerie</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Selectionnez ici les examens d'imagerie a demander pour cette consultation.
                                </p>
                            </div>
                            <span
                                class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                                {{ $this->imagerieActes()->count() }}
                            </span>
                        </div>

                        <div class="mt-4">
                            <x-select.styled label="Examens d'imagerie *" wire:model="imagerieActeIds"
                                :request="[
                                    'url' => route('api.actes'),
                                    'params' => ['departement' => $this->imagerieDepartement()?->id],
                                ]" select="label:name|value:id" multiple />
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @forelse ($this->imagerieActes() as $acte)
                                <div
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $acte->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $acte->service?->name ?: ($acte->departement?->name ?: 'Imagerie') }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500 dark:text-slate-400">Aucun examen d'imagerie n'est
                                    rattache a cette consultation.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            @if ($currentSection === 'prescription')
                <x-textarea wire:model="prescriptionNote" label="Prescription medicale" rows="8"
                    maxlength="5000" count />
            @endif

            @if ($currentSection === 'rendez_vous')
                <x-input wire:model="rendezVousValue" label="Rendez-vous medical"
                    placeholder="Ex: 28/04/2026 a 10h30" />
            @endif
        </div>

        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('consultation-section-modal')">Annuler
                </flux:button>
                <flux:button variant="primary" color="sky" wire:click="saveEditor">Enregistrer</flux:button>
            </div>
        </x-slot:footer>
    </x-modal>
</x-pages::consultation.layout>
