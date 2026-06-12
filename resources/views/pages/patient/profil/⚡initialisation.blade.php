<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use App\Models\Configs\PacquetSoin;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\Imagerie;
use App\Models\Laboratoire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app.other.profil_medical')] class extends Component {
    public $patient;
    public $type;
    public string $type_fiche = 'standard';
    public ?string $depistage_target = null;
    public $departement_id;
    public $service_id;
    public $acte_ids = [];
    public $user_ids = [];
    public $assurance_id;
    public $projet_id;
    public $pacquet_soin_id;
    public bool $use_project_period = true;
    public bool $new_visite = false;
    public $next_visit_date = null;
    public ?string $next_visit_time = null;
    public string $ref = '';
    public $date_consultation;

    public function mount($id): void
    {
        abort_unless(current_hopital_id(), 403, 'Aucun hopital courant en session.');

        $this->patient = DossierPatient::findOrFail($id);
        $this->date_consultation = today()->format('Y-m-d');
    }

    protected function resolvedConsultationDate(): \Carbon\CarbonInterface
    {
        $date = filled($this->date_consultation)
            ? Carbon::parse($this->date_consultation)->startOfDay()
            : today();

        return $date->isToday() ? now() : $date->copy()->setTime(8, 0);
    }

    protected function resolvedNextVisitDateTime(): \Carbon\CarbonInterface
    {
        $date = Carbon::parse($this->next_visit_date)->startOfDay();
        $time = filled($this->next_visit_time) ? $this->next_visit_time : '08:30';
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return $date->copy()->setTime((int) $hour, (int) $minute);
    }

    public function updatedType($value): void
    {
        $this->reset(['departement_id', 'service_id', 'acte_ids', 'user_ids', 'ref', 'depistage_target', 'pacquet_soin_id', 'use_project_period', 'new_visite', 'next_visit_date', 'next_visit_time']);

        if ($this->isDepistageType($value)) {
            $this->depistage_target = 'laboratoire';
            $this->applyDepistageTargetDefaults();
        }
    }

    public function updatedDepartementId($value): void
    {
        $departement = Departement::find($value);
        $this->ref = $departement?->ref ?? '';
    }

    public function updatedDepistageTarget(): void
    {
        $this->reset(['acte_ids', 'pacquet_soin_id', 'departement_id', 'service_id', 'ref']);
        $this->applyDepistageTargetDefaults();
    }

    public function updatedPacquetSoinId($value): void
    {
        if (!$value) {
            $this->acte_ids = [];
            $this->ref = '';

            return;
        }

        $paquet = PacquetSoin::query()
            ->with(['actes.departement'])
            ->find($value);

        $this->acte_ids = $paquet?->actes->pluck('id')->map(fn($id) => (string) $id)->all() ?? [];
        $this->syncDepistageReferenceFromActes();
    }

    public function updatedActeIds(): void
    {
        if ($this->isDepistageType()) {
            $this->syncDepistageReferenceFromActes();
        }
    }

    public function updatedProjetId($value): void
    {
        if (!$value) {
            $this->use_project_period = false;
        }
    }

    public function isConsultationType(?string $type = null): bool
    {
        return in_array($type ?? $this->type, ['consultation'], true);
    }

    public function isDepistageType(?string $type = null): bool
    {
        return in_array($type ?? $this->type, ['depistage'], true);
    }

    public function laboratoireDepartement(): ?Departement
    {
        return Departement::query()->where('name', 'like', '%laboratoire%')->orWhere('ref', 'labo')->first();
    }

    public function imagerieDepartement(): ?Departement
    {
        return Departement::query()->where('name', 'like', '%imagerie%')->orWhere('ref', 'img')->first();
    }

    protected function applyDepistageTargetDefaults(): void
    {
        $departement = match ($this->depistage_target) {
            'imagerie' => $this->imagerieDepartement(),
            'laboratoire' => $this->laboratoireDepartement(),
            default => null,
        };

        $this->departement_id = $departement?->id;
        $this->ref = $departement?->ref ?? '';
    }

    protected function selectedDepartement(): ?Departement
    {
        $departementId = $this->resolvedDepartementId();

        return $departementId ? Departement::find($departementId) : null;
    }

    protected function isMedicalConsultation(): bool
    {
        return $this->type === 'consultation';
    }

    protected function selectedActes()
    {
        if ($this->acte_ids === []) {
            return collect();
        }

        return Acte::query()
            ->with('departement')
            ->whereIn('id', array_map('intval', $this->acte_ids))
            ->get();
    }

    protected function acteBelongsToLaboratoire(Acte $acte): bool
    {
        $departement = $acte->departement;

        if (!$departement) {
            return false;
        }

        return str_contains(strtolower((string) $departement->name), 'laboratoire') || strtolower((string) $departement->ref) === 'labo';
    }

    protected function acteBelongsToImagerie(Acte $acte): bool
    {
        $departement = $acte->departement;

        if (!$departement) {
            return false;
        }

        return str_contains(strtolower((string) $departement->name), 'imagerie') || strtolower((string) $departement->ref) === 'img';
    }

    protected function syncDepistageReferenceFromActes(): void
    {
        $firstActe = $this->selectedActes()->first();
        $departement = $firstActe?->departement;

        $this->ref = $departement?->ref ?? $this->ref;
    }

    protected function shouldCreateLaboratoireBon(): bool
    {
        $departement = $this->selectedDepartement();

        if ($this->isDepistageType()) {
            if ($this->depistage_target === 'laboratoire') {
                return true;
            }

            if ($this->depistage_target === 'pacquet_soins') {
                return $this->selectedActes()->contains(fn(Acte $acte) => $this->acteBelongsToLaboratoire($acte));
            }

            return false;
        }

        return $this->isMedicalConsultation() && (($departement && str_contains(strtolower((string) $departement->name), 'laboratoire')) || strtolower((string) ($departement?->ref ?? '')) === 'labo');
    }

    protected function shouldCreateImagerieBon(): bool
    {
        $departement = $this->selectedDepartement();

        if ($this->isDepistageType()) {
            if ($this->depistage_target === 'imagerie') {
                return true;
            }

            if ($this->depistage_target === 'pacquet_soins') {
                return $this->selectedActes()->contains(fn(Acte $acte) => $this->acteBelongsToImagerie($acte));
            }

            return false;
        }

        return $this->isMedicalConsultation() && (($departement && str_contains(strtolower((string) $departement->name), 'imagerie')) || strtolower((string) ($departement?->ref ?? '')) === 'img');
    }

    protected function resolvedDepartementId(): ?int
    {
        if ($this->isDepistageType()) {
            if ($this->depistage_target === 'laboratoire') {
                return $this->laboratoireDepartement()?->id;
            }

            if ($this->depistage_target === 'imagerie') {
                return $this->imagerieDepartement()?->id;
            }

            $selectedActes = $this->selectedActes();

            $primaryActe = $selectedActes->first(fn(Acte $acte) => $this->acteBelongsToLaboratoire($acte)) ?? ($selectedActes->first(fn(Acte $acte) => $this->acteBelongsToImagerie($acte)) ?? $selectedActes->first());

            return $primaryActe?->departement_id;
        }

        return $this->departement_id ? (int) $this->departement_id : null;
    }

    protected function resolvedReference(): string
    {
        if ($this->ref !== '') {
            return $this->ref;
        }

        $departement = $this->resolvedDepartementId() ? Departement::find($this->resolvedDepartementId()) : null;

        return $departement?->ref ?? 'GEN';
    }

    protected function patientProgrammedConsultations(): Builder
    {
        return Consultation::query()->whereHopitalId(current_hopital_id())->where('dossier_patient_id', $this->patient->id)->programmed();
    }

    protected function openProgrammedConsultations(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereNull('is_clore')->orWhere('is_clore', false)->orWhereNull('issue');
        });
    }

    protected function consultationContinuationRoute(Consultation $consultation): string
    {
        $needsPrelevement = blank($consultation->user_id) || blank($consultation->poids) || blank($consultation->temperature);

        return $needsPrelevement ? route('consultation.prelevement', $consultation->id) : route('consultation.show', $consultation->id);
    }

    protected function plannedDelayLabel(int $days): string
    {
        return match (true) {
            $days <= 0 => "aujourd'hui",
            $days === 1 => 'dans 1 jour',
            default => "dans {$days} jours",
        };
    }

    protected function overdueDelayLabel(int $days): string
    {
        return match (true) {
            $days <= 1 => 'depuis 1 jour',
            default => "depuis {$days} jours",
        };
    }

    #[Computed]
    public function programmedVisitNotices(): array
    {
        $today = Carbon::today();
        $notices = [];

        $upcomingQuery = $this->openProgrammedConsultations(
            $this->patientProgrammedConsultations()
                ->whereDate('created_at', '>=', $today)
                ->whereDate('created_at', '<=', $today->copy()->addDays(14))
                ->orderBy('created_at'),
        );

        $upcomingVisit = (clone $upcomingQuery)->first();
        $upcomingCount = (clone $upcomingQuery)->count();

        if ($upcomingVisit) {
            $daysUntilVisit = $today->diffInDays($upcomingVisit->created_at->copy()->startOfDay());

            $notices[] = [
                'key' => 'upcoming-programmed-visit',
                'tone' => 'sky',
                'title' => 'Visite programmee a venir detectee',
                'message' => sprintf('Nous avons detecte une consultation programmee le %s (%s, %s) pour ce patient. Souhaitez-vous poursuivre la prise en charge sur cette visite deja ouverte ?', $upcomingVisit->created_at?->format('d/m/Y') ?? '-', $upcomingVisit->reference, $this->plannedDelayLabel($daysUntilVisit)),
                'supporting' => $upcomingCount > 1 ? 'D autres visites programmees existent egalement sur cette meme periode.' : 'Vous pouvez reprendre directement la visite existante ou continuer l ouverture d une nouvelle consultation.',
                'action_url' => $this->consultationContinuationRoute($upcomingVisit),
                'action_label' => 'Continuer sur cette visite',
            ];
        }

        $overdueQuery = $this->openProgrammedConsultations($this->patientProgrammedConsultations()->whereDate('created_at', '<', $today)->orderByDesc('created_at'));

        $overdueVisit = (clone $overdueQuery)->first();
        $overdueCount = (clone $overdueQuery)->count();

        if ($overdueVisit) {
            $daysOverdue = $overdueVisit->created_at?->copy()->startOfDay()->diffInDays($today) ?? 14;

            $notices[] = [
                'key' => 'overdue-programmed-visit',
                'tone' => 'amber',
                'title' => 'Visite programmee non cloturee detectee',
                'message' => sprintf('Nous avons detecte une consultation programmee datee du %s (%s) qui semble encore non cloturee. Cette visite est en attente %s. Nous vous recommandons de la reprendre avant d ouvrir une nouvelle consultation.', $overdueVisit->created_at?->format('d/m/Y') ?? '-', $overdueVisit->reference, $this->overdueDelayLabel($daysOverdue)),
                'supporting' => $overdueCount > 1 ? 'D autres visites programmees plus anciennes restent egalement ouvertes pour ce patient.' : 'La reprise de cette visite permet de garder un parcours patient plus propre et plus lisible.',
                'action_url' => $this->consultationContinuationRoute($overdueVisit),
                'action_label' => 'Reprendre cette visite',
            ];
        }

        return $notices;
    }

    public function save(): void
    {
        $validated = $this->validate(
            [
                'type' => 'required|in:consultation,depistage',
                'type_fiche' => 'required|in:standard,hémophilie,drépanocytose',
                'depistage_target' => $this->isDepistageType() ? 'required|in:laboratoire,imagerie,pacquet_soins' : 'nullable',
                'departement_id' => $this->isConsultationType() ? 'required|exists:departements,id' : 'nullable',
                'assurance_id' => 'nullable|exists:assurances,id',
                'projet_id' => 'nullable|exists:projets,id',
                'use_project_period' => 'boolean',
                'new_visite' => 'boolean',
                'next_visit_date' => $this->new_visite ? 'required|date|after_or_equal:today' : 'nullable|date',
                'next_visit_time' => $this->new_visite ? 'nullable|date_format:H:i' : 'nullable|date_format:H:i',
                'service_id' => 'nullable|exists:services,id',
                'pacquet_soin_id' => $this->isDepistageType() && $this->depistage_target === 'pacquet_soins' ? 'required|exists:pacquet_soins,id' : 'nullable',
                'acte_ids' => 'required|array|min:1',
                'acte_ids.*' => 'exists:actes,id',
                'user_ids' => 'nullable|array',
                'user_ids.*' => 'exists:users,id',
                'date_consultation' => 'nullable|date|before_or_equal:today',
            ],
            [
                'acte_ids.required' => 'Veuillez selectionner au moins un acte.',
                'departement_id.required' => 'Veuillez selectionner un departement pour la consultation.',
                'depistage_target.required' => 'Veuillez choisir une cible pour le depistage.',
                'pacquet_soin_id.required' => 'Veuillez selectionner un paquet de soins.',
                'next_visit_date.required' => 'Veuillez renseigner la date de la consultation programmee.',
                'next_visit_date.after_or_equal' => 'La date du rendez-vous ne peut pas etre dans le passe.',
                'next_visit_time.date_format' => 'L\'heure du rendez-vous est invalide.',
                'date_consultation.before_or_equal' => 'La date de consultation ne peut pas etre dans le futur.',
            ],
        );

        if (($validated['new_visite'] ?? false) && filled($validated['next_visit_date'])) {
            $nextVisitAt = $this->resolvedNextVisitDateTime();

            if ($nextVisitAt->isPast()) {
                $this->addError('next_visit_time', 'Le rendez-vous doit etre planifie dans le futur.');

                return;
            }
        }

        $consultationAt = $this->resolvedConsultationDate();

        $consultation = DB::transaction(function () use ($validated, $consultationAt) {
            $selectedActes = Acte::query()->with('departement')->whereIn('id', $validated['acte_ids'])->get()->keyBy('id');

            $consultation = Consultation::createWithPeriodContext(
                [
                    'type' => $validated['type'],
                    'type_fichier' => $validated['type_fiche'],
                    'dossier_patient_id' => $this->patient->id,
                    'departement_id' => $this->resolvedDepartementId(),
                    'projet_id' => $validated['projet_id'] ?? null,
                    'assurance_id' => $validated['assurance_id'] ?? null,
                    'service_id' => $this->isConsultationType() ? $validated['service_id'] ?? null : null,
                    'hopital_id' => current_hopital_id(),
                    'created_at' => $consultationAt,
                    'updated_at' => $consultationAt,
                ],
                [
                    'use_project_period' => (bool) ($validated['use_project_period'] ?? false),
                ],
            );

            $attachData = collect($validated['acte_ids'])
                ->mapWithKeys(function ($acteId) use ($selectedActes) {
                    /** @var \App\Models\Configs\Acte|null $acte */
                    $acte = $selectedActes->get((int) $acteId);

                    return [
                        $acteId => [
                            'ref' => $acte?->departement?->ref ?? $this->resolvedReference(),
                            'montant' => (float) ($acte?->montant ?? 0),
                        ],
                    ];
                })
                ->toArray();

            $consultation->actes()->sync($attachData);
            $consultation->users()->sync($validated['user_ids'] ?? []);

            $facturationId = DB::table('facturations')->insertGetId([
                'consultation_id' => $consultation->id,
                'dossier_patient_id' => $this->patient->id,
                'hopital_id' => current_hopital_id(),
                'created_at' => $consultationAt,
                'updated_at' => $consultationAt,
            ]);

            $consultation->update([
                'facturation_id' => $facturationId,
            ]);

            if ($this->shouldCreateLaboratoireBon()) {
                $laboratoire = Laboratoire::create([
                    'consultation_id' => $consultation->id,
                    'user_id' => Auth::id(),
                    'hopital_id' => current_hopital_id(),
                    'renseignement' => $this->isDepistageType() ? 'Depistage - laboratoire' : 'Consultation medicale - laboratoire',
                    'statut' => 'en attente',
                ]);

                $consultation->update([
                    'laboratoire_id' => $laboratoire->id,
                ]);
            }

            if ($this->shouldCreateImagerieBon()) {
                $imagerie = Imagerie::create([
                    'consultation_id' => $consultation->id,
                    'hopital_id' => current_hopital_id(),
                    'renseignement' => $this->isDepistageType() ? 'Depistage - imagerie' : 'Consultation medicale - imagerie',
                    'statut' => 'en attente',
                ]);

                $consultation->update([
                    'imagerie_id' => $imagerie->id,
                ]);
            }

            if ($this->isConsultationType() && ($validated['new_visite'] ?? false) && filled($validated['next_visit_date'])) {
                $nextVisitAt = $this->resolvedNextVisitDateTime();

                $programmedConsultation = Consultation::createWithPeriodContext(
                    [
                        'type' => 'consultation',
                        'type_fichier' => $validated['type_fiche'],
                        'dossier_patient_id' => $this->patient->id,
                        'departement_id' => $this->resolvedDepartementId(),
                        'projet_id' => $validated['projet_id'] ?? null,
                        'assurance_id' => $validated['assurance_id'] ?? null,
                        'service_id' => $validated['service_id'] ?? null,
                        'hopital_id' => current_hopital_id(),
                        'is_visite_program' => true,
                        'consultation_source_id' => $consultation->id,
                        'created_at' => $nextVisitAt,
                        'updated_at' => $nextVisitAt,
                    ],
                    [
                        'use_project_period' => (bool) ($validated['use_project_period'] ?? false),
                    ],
                );

                $programmedFacturationId = DB::table('facturations')->insertGetId([
                    'consultation_id' => $programmedConsultation->id,
                    'dossier_patient_id' => $this->patient->id,
                    'hopital_id' => current_hopital_id(),
                    'created_at' => $nextVisitAt,
                    'updated_at' => $nextVisitAt,
                ]);

                $programmedConsultation->update([
                    'facturation_id' => $programmedFacturationId,
                ]);
            }

            return $consultation;
        });

        $this->redirect(route('consultation.prelevement', $consultation->id), navigate: true);
    }
};
?>

<div class="transition-colors duration-300">
    <x-patient.patient-profil-header :nav="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
        ['label' => $patient->nin, 'link' => route('patient.show', $patient->id), 'icon' => 'identification'],
        ['label' => 'Nouvelle consultation', 'icon' => 'document'],
    ]" :patient="$patient" :current_patient="$patient->id">
        <x-slot name="title">Nouvelle consultation</x-slot>
        <x-slot name="subtitle">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->postnom) }}
            {{ ucfirst($patient->prenom) }}</x-slot>
    </x-patient.patient-profil-header>

    <div class="max-w-7xl mx-auto">
        <x-card header="Choisir le type de consultation" loading>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-select.styled label="Type de consultation *" wire:model.live="type" placeholder="Choisir..." required
                    :options="[
                        ['label' => 'Visite', 'value' => 'consultation'],
                        ['label' => 'Examen', 'value' => 'depistage'],
                    ]" />
                <x-select.styled label="Type de la visite *" wire:model.live="type_fiche" placeholder="Choisir..."
                    required :options="[
                        ['label' => 'Standard', 'value' => 'standard'],
                        ['label' => 'drépanocytose', 'value' => 'hémophilie'],
                        ['label' => 'drépanocytose', 'value' => 'drépanocytose'],
                    ]" />
                <x-date label="Date de la consultation" wire:model="date_consultation"
                    placeholder="Aujourd'hui par défaut" :max="today()->format('Y-m-d')" />
            </div>
        </x-card>

        <div class="mt-8">
            @if ($this->programmedVisitNotices !== [])
                <div class="mb-6 space-y-4">
                    @foreach ($this->programmedVisitNotices as $notice)
                        <div
                            class="rounded-3xl border p-5 shadow-sm {{ $notice['tone'] === 'amber' ? 'border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-500/10' : 'border-sky-200 bg-sky-50/80 dark:border-sky-500/20 dark:bg-sky-500/10' }}">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-2">
                                    <p
                                        class="text-xs font-black uppercase tracking-[0.22em] {{ $notice['tone'] === 'amber' ? 'text-amber-700 dark:text-amber-300' : 'text-sky-700 dark:text-sky-300' }}">
                                        Alerte de suivi
                                    </p>
                                    <h3
                                        class="text-base font-black {{ $notice['tone'] === 'amber' ? 'text-amber-950 dark:text-amber-100' : 'text-sky-950 dark:text-sky-100' }}">
                                        {{ $notice['title'] }}
                                    </h3>
                                    <p
                                        class="max-w-3xl text-sm leading-6 {{ $notice['tone'] === 'amber' ? 'text-amber-900/90 dark:text-amber-100/90' : 'text-sky-900/90 dark:text-sky-100/90' }}">
                                        {{ $notice['message'] }}
                                    </p>
                                    <p
                                        class="text-xs {{ $notice['tone'] === 'amber' ? 'text-amber-800/80 dark:text-amber-200/80' : 'text-sky-800/80 dark:text-sky-200/80' }}">
                                        {{ $notice['supporting'] }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center">
                                    <a href="{{ $notice['action_url'] }}" wire:navigate
                                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-bold transition {{ $notice['tone'] === 'amber' ? 'border-amber-300 bg-white text-amber-800 hover:border-amber-400 hover:bg-amber-100 dark:border-amber-400/30 dark:bg-slate-950/40 dark:text-amber-200 dark:hover:bg-amber-500/20' : 'border-sky-300 bg-white text-sky-800 hover:border-sky-400 hover:bg-sky-100 dark:border-sky-400/30 dark:bg-slate-950/40 dark:text-sky-200 dark:hover:bg-sky-500/20' }}">
                                        {{ $notice['action_label'] }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($type === 'consultation')
                <x-card header="Remplir les informations initiales">
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-select.styled label="Departement *" wire:model.live="departement_id" :request="route('api.departements')"
                            select="label:name|value:id" searchable />
                        <x-select.styled label="Service" wire:model.live="service_id" :request="['url' => route('api.services'), 'params' => ['departement' => $departement_id]]"
                            select="label:name|value:id" placeholder="Choisir..." :disabled="!$departement_id" />
                        <x-select.styled label="Actes a ajouter" wire:model="acte_ids" :request="[
                            'url' => route('api.actes'),
                            'params' => ['departement' => $departement_id, 'service' => $service_id],
                        ]"
                            select="label:name|value:id" multiple />
                    </div>

                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-select.styled label="Membre de l'equipe" placeholder="Choisir..." wire:model="user_ids"
                            :request="[
                                'url' => route('api.usersIn'),
                                'params' => ['hopital_id' => current_hopital_id()],
                            ]" select="label:name|value:id" multiple />
                        <x-select.styled label="Prise en charge" placeholder="Choisir..." wire:model="assurance_id"
                            :request="route('api.assurances')" select="label:name|value:id" searchable />
                        <x-select.styled label="Projet associe a la consultation" placeholder="Choisir ou creer"
                            wire:model.live="projet_id" :request="route('api.projets')" select="label:name|value:id" searchable />
                    </div>

                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @if ($projet_id)
                            <div
                                class="mb-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                                <label class="flex items-start gap-3">
                                    <x-toggle wire:model.live="use_project_period" />
                                    <div>
                                        <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Période
                                            automatique au projet</p>
                                        <p class="mt-1 text-xs text-sky-800/80 dark:text-sky-200/80">
                                            Si cochée, le modele generera automatiquement une periode du type
                                            lettre-du-projet + numero
                                            comme `M1`.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        @endif
                        <div
                            class="mb-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-500/20 dark:bg-sky-500/10">
                            <label class="flex items-start gap-3 mb-2">
                                <x-toggle wire:model.live="new_visite" />
                                <div>
                                    <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Programmée
                                        automatiquement la prochaine visite</p>
                                    <p class="mt-1 text-xs text-sky-800/80 dark:text-sky-200/80">
                                        Si cochée, le modele créera automatiquement la prochaine visite sous une periode
                                        determinée.
                                    </p>
                                </div>
                            </label>
                            <flux:icon.loading wire:loading wire:target="new_visite" />
                            <div wire:loading.remove wire:target="new_visite">
                                @if ($new_visite)
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <x-date wire:model="next_visit_date" label="Date du rendez-vous *"
                                            placeholder="Date de la prochaine visite" :min="today()->format('Y-m-d')" />
                                        <x-input wire:model="next_visit_time" type="time" label="Heure"
                                            placeholder="08:30 par défaut" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <flux:button wire:click="save" variant="primary" color="indigo" icon="save">
                            Enregistrer les informations
                        </flux:button>
                    </div>
                </x-card>
            @endif

            @if ($type === 'depistage')
                <x-card header="Configurer le depistage">
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-select.styled label="Cible du depistage *" wire:model.live="depistage_target"
                            placeholder="Choisir..." required :options="[
                                ['label' => 'Laboratoire', 'value' => 'laboratoire'],
                                ['label' => 'Imagerie', 'value' => 'imagerie'],
                                ['label' => 'Paquet de soins', 'value' => 'pacquet_soins'],
                            ]" />
                        <x-select.styled label="Prise en charge" placeholder="Choisir..." wire:model="assurance_id"
                            :request="route('api.assurances')" select="label:name|value:id" searchable />
                        {{-- <x-select.styled label="Projet associe" placeholder="Choisir ou creer"
                            wire:model.live="projet_id" :request="route('api.projets')" select="label:name|value:id" searchable /> --}}
                    </div>

                    @if ($depistage_target === 'laboratoire')
                        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <x-input label="Departement" :value="$this->laboratoireDepartement()?->name ?? 'Laboratoire'" disabled />
                            <x-select.styled label="Examens de laboratoire *" wire:model="acte_ids" :request="[
                                'url' => route('api.actes'),
                                'params' => ['departement' => $this->laboratoireDepartement()?->id],
                            ]"
                                select="label:name|value:id" multiple />
                            <x-select.styled label="Membre de l'equipe" placeholder="Choisir..." wire:model="user_ids"
                                :request="[
                                    'url' => route('api.usersIn'),
                                    'params' => ['hopital_id' => current_hopital_id()],
                                ]" select="label:name|value:id" multiple />
                        </div>
                    @endif

                    @if ($depistage_target === 'imagerie')
                        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <x-input label="Departement" :value="$this->imagerieDepartement()?->name ?? 'Imagerie'" disabled />
                            <x-select.styled label="Actes d'imagerie *" wire:model="acte_ids" :request="[
                                'url' => route('api.actes'),
                                'params' => ['departement' => $this->imagerieDepartement()?->id],
                            ]"
                                select="label:name|value:id" multiple />
                            <x-select.styled label="Membre de l'equipe" placeholder="Choisir..." wire:model="user_ids"
                                :request="[
                                    'url' => route('api.usersIn'),
                                    'params' => ['hopital_id' => current_hopital_id()],
                                ]" select="label:name|value:id" multiple />
                        </div>
                    @endif

                    @if ($depistage_target === 'pacquet_soins')
                        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <x-select.styled label="Paquet de soins *" wire:model.live="pacquet_soin_id"
                                :request="route('api.pacquetSoins')" select="label:name|value:id" searchable />
                            <x-select.styled label="Membre de l'equipe" placeholder="Choisir..."
                                wire:model="user_ids" :request="[
                                    'url' => route('api.usersIn'),
                                    'params' => ['hopital_id' => current_hopital_id()],
                                ]" select="label:name|value:id" multiple />
                            <x-input label="Reference departement" :value="$ref ?: 'Auto'" disabled />
                        </div>

                        @if ($pacquet_soin_id)
                            <div
                                class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Actes inclus dans le
                                    paquet</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Les actes du paquet sont preselectionnes. Vous pouvez en deselectionner si
                                    necessaire.
                                </p>

                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    @foreach (\App\Models\Configs\PacquetSoin::query()->with(['actes.departement', 'actes.service'])->find($pacquet_soin_id)?->actes ?? collect() as $acte)
                                        <label wire:key="depistage-paquet-acte-{{ $acte->id }}"
                                            class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50/60 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-sky-700 dark:hover:bg-sky-950/30">
                                            <input type="checkbox" value="{{ $acte->id }}" wire:model="acte_ids"
                                                class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3">
                                                    <p class="font-medium text-slate-900 dark:text-white">
                                                        {{ $acte->name }}</p>
                                                    <span
                                                        class="whitespace-nowrap text-sm font-semibold text-sky-700 dark:text-sky-300">
                                                        {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $acte->departement?->name ?: 'Sans departement' }}
                                                    @if ($acte->service?->name)
                                                        • {{ $acte->service->name }}
                                                    @endif
                                                </p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="flex justify-end">
                        <flux:button wire:click="save" variant="primary" color="indigo" icon="save">
                            Initialiser le depistage
                        </flux:button>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</div>
