<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use App\Models\Configs\GroupeExamen;
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
    public string $type = 'consultation';
    public string $type_visite = 'standard';
    public ?string $depistage_target = null;
    public $departement_id;
    public $service_id;
    public $acte_ids = [];
    public $user_ids = [];
    public $projet_id;
    public $pacquet_soin_id;
    public $groupe_examen_id;
    /** @var \Illuminate\Support\Collection<int, \App\Models\Configs\Acte> */
    public $paquetActes;
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
        $this->paquetActes = collect();
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
        $this->reset(['departement_id', 'service_id', 'acte_ids', 'user_ids', 'ref', 'depistage_target', 'pacquet_soin_id', 'groupe_examen_id', 'paquetActes', 'use_project_period', 'new_visite', 'next_visit_date', 'next_visit_time']);

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
        $this->reset(['acte_ids', 'pacquet_soin_id', 'groupe_examen_id', 'paquetActes', 'departement_id', 'service_id', 'ref']);
        $this->applyDepistageTargetDefaults();
    }

    public function updatedGroupeExamenId($value): void
    {
        if (!$value) {
            return;
        }

        $groupe = GroupeExamen::query()
            ->active()
            ->with('actes:id')
            ->find($value);

        if (!$groupe) {
            return;
        }

        $this->acte_ids = $groupe->actes
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->syncDepistageReferenceFromActes();
    }

    public function updatedPacquetSoinId($value): void
    {
        if (!$value) {
            $this->acte_ids = [];
            $this->ref = '';
            $this->paquetActes = collect();

            return;
        }

        $paquet = PacquetSoin::query()
            ->with(['actes.departement', 'actes.service'])
            ->find($value);

        $this->paquetActes = $paquet?->actes ?? collect();
        $this->acte_ids = $this->paquetActes->pluck('id')->map(fn($id) => (string) $id)->all();
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
                ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->addDays(14)->endOfDay()])
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

        $overdueQuery = $this->openProgrammedConsultations(
            $this->patientProgrammedConsultations()
                ->where('created_at', '<', $today->copy()->startOfDay())
                ->orderByDesc('created_at')
        );

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
                'type_visite' => 'required|in:standard,hémophilie,drépanocytose',
                'depistage_target' => $this->isDepistageType() ? 'required|in:laboratoire,imagerie,pacquet_soins' : 'nullable',
                'departement_id' => $this->isConsultationType() ? 'required|exists:departements,id' : 'nullable',
                'projet_id' => 'nullable|exists:projets,id',
                'use_project_period' => 'boolean',
                'new_visite' => 'boolean',
                'next_visit_date' => $this->new_visite ? 'required|date|after_or_equal:today' : 'nullable|date',
                'next_visit_time' => $this->new_visite ? 'nullable|date_format:H:i' : 'nullable|date_format:H:i',
                'service_id' => 'nullable|exists:services,id',
                'pacquet_soin_id' => $this->isDepistageType() && $this->depistage_target === 'pacquet_soins' ? 'required|exists:pacquet_soins,id' : 'nullable',
                'groupe_examen_id' => $this->isDepistageType() && $this->depistage_target === 'laboratoire' ? 'nullable|exists:groupe_examens,id' : 'nullable',
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
                    'type_visite' => $validated['type_visite'],
                    'dossier_patient_id' => $this->patient->id,
                    'departement_id' => $this->resolvedDepartementId(),
                    'projet_id' => $validated['projet_id'] ?? null,
                    'assurance_id' => null,
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
                        'type_visite' => $validated['type_visite'],
                        'dossier_patient_id' => $this->patient->id,
                        'departement_id' => $this->resolvedDepartementId(),
                        'projet_id' => $validated['projet_id'] ?? null,
                        'assurance_id' => null,
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

        $redirectUrl = $this->isConsultationType()
            ? route('consultation.prelevement', $consultation->id)
            : route('dashboard');

        $this->redirect($redirectUrl, navigate: true);
    }
};
?>

@php
    $typeFichierLabels = [
        'standard' => 'Standard',
        'hémophilie' => 'Hémophilie',
        'drépanocytose' => 'Drépanocytose',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6 pb-28 transition-colors duration-300">
    <x-patient.patient-profil-header :nav="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
        ['label' => $patient->nin, 'link' => route('patient.show', $patient->id), 'icon' => 'identification'],
        ['label' => 'Nouvelle prise en charge', 'icon' => 'document'],
    ]" :patient="$patient" :current_patient="$patient->id">
        <x-slot name="title">Ouvrir une prise en charge</x-slot>
        <x-slot name="subtitle">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->postnom) }}
            {{ ucfirst($patient->prenom) }} · {{ $patient->genre }} · {{ $patient->age }}</x-slot>
    </x-patient.patient-profil-header>

    {{-- Bandeau patient --}}
    <section
        class="flex flex-col gap-4 overflow-hidden rounded-4xl border border-indigo-100 bg-linear-to-br from-white via-indigo-50/50 to-slate-50 px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex items-center gap-4">
            <div
                class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-linear-to-br from-indigo-500 to-violet-500 text-lg font-black text-white shadow-md">
                @if ($patient->photo_url)
                    <img src="{{ $patient->photo_url }}" alt="{{ $patient->full_name }}"
                        class="size-full object-cover" />
                @else
                    {{ strtoupper(substr($patient->prenom ?? 'P', 0, 1) . substr($patient->nom ?? 'X', 0, 1)) }}
                @endif
            </div>
            <div class="min-w-0">
                <p class="truncate text-lg font-black uppercase tracking-tight text-slate-900 dark:text-white">
                    {{ $patient->full_name }}
                </p>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    NIN {{ $patient->nin }}{{ $patient->ins ? ' · INS ' . $patient->ins : '' }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-white px-3 py-1.5 text-xs font-bold text-indigo-700 dark:border-indigo-500/30 dark:bg-slate-900/60 dark:text-indigo-300">
                <flux:icon.calendar-days class="size-3.5" />
                {{ today()->format('d/m/Y') }}
            </span>
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
                <flux:icon.building-office-2 class="size-3.5" />
                {{ current_hopital_nom() }}
            </span>
        </div>
    </section>

    {{-- Étape 1 : nature & contexte --}}
    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                    <flux:icon.clipboard-document-list class="size-5" />
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Étape 1</p>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Nature de la prise en charge</h2>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-5" wire:loading.class="opacity-60" wire:target="type, depistage_target">
            <div class="grid gap-3 sm:grid-cols-2">
                <label @class([
                    'group relative cursor-pointer overflow-hidden rounded-2xl border-2 p-5 transition',
                    'border-indigo-500 bg-indigo-50/80 shadow-sm ring-2 ring-indigo-500/20 dark:border-indigo-400 dark:bg-indigo-500/10' => $type === 'consultation',
                    'border-slate-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/30 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-indigo-500/40' => $type !== 'consultation',
                ])>
                    <input type="radio" wire:model.live="type" value="consultation" class="sr-only" />
                    <div class="flex items-start gap-4">
                        <div @class([
                            'flex size-11 shrink-0 items-center justify-center rounded-xl transition',
                            'bg-indigo-500 text-white' => $type === 'consultation',
                            'bg-slate-100 text-slate-500 group-hover:bg-indigo-100 group-hover:text-indigo-600 dark:bg-slate-800 dark:text-slate-400' => $type !== 'consultation',
                        ])>
                            <flux:icon.user-circle class="size-6" />
                        </div>
                        <div>
                            <p class="text-base font-black text-slate-900 dark:text-white">Visite</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Consultation médicale, orientation vers un service et suivi clinique.
                            </p>
                        </div>
                    </div>
                </label>

                <label @class([
                    'group relative cursor-pointer overflow-hidden rounded-2xl border-2 p-5 transition',
                    'border-violet-500 bg-violet-50/80 shadow-sm ring-2 ring-violet-500/20 dark:border-violet-400 dark:bg-violet-500/10' => $type === 'depistage',
                    'border-slate-200 bg-white hover:border-violet-200 hover:bg-violet-50/30 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-violet-500/40' => $type !== 'depistage',
                ])>
                    <input type="radio" wire:model.live="type" value="depistage" class="sr-only" />
                    <div class="flex items-start gap-4">
                        <div @class([
                            'flex size-11 shrink-0 items-center justify-center rounded-xl transition',
                            'bg-violet-500 text-white' => $type === 'depistage',
                            'bg-slate-100 text-slate-500 group-hover:bg-violet-100 group-hover:text-violet-600 dark:bg-slate-800 dark:text-slate-400' => $type !== 'depistage',
                        ])>
                            <flux:icon.beaker class="size-6" />
                        </div>
                        <div>
                            <p class="text-base font-black text-slate-900 dark:text-white">Examen</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Dépistage labo, imagerie ou paquet de soins sans visite complète.
                            </p>
                        </div>
                    </div>
                </label>
            </div>

            <div class="grid gap-4 border-t border-slate-100 pt-5 dark:border-slate-800 sm:grid-cols-2">
                <x-select.styled label="Type de la visite *" wire:model.live="type_visite" placeholder="Choisir..."
                    required :options="[
                        ['label' => 'Standard', 'value' => 'standard'],
                        ['label' => 'Hémophilie', 'value' => 'hémophilie'],
                        ['label' => 'Drépanocytose', 'value' => 'drépanocytose'],
                    ]" />
                <x-date label="Date de la prise en charge" wire:model="date_consultation"
                    placeholder="Aujourd'hui par défaut" :max="today()->format('Y-m-d')" />
            </div>
        </div>
    </section>

    @if ($this->programmedVisitNotices !== [])
        <div class="space-y-4">
            @foreach ($this->programmedVisitNotices as $notice)
                <div
                    class="overflow-hidden rounded-2xl border shadow-sm {{ $notice['tone'] === 'amber' ? 'border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-500/10' : 'border-sky-200 bg-sky-50/80 dark:border-sky-500/20 dark:bg-sky-500/10' }}">
                    <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex gap-4">
                            <div @class([
                                'flex size-10 shrink-0 items-center justify-center rounded-xl',
                                'bg-amber-200 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200' => $notice['tone'] === 'amber',
                                'bg-sky-200 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200' => $notice['tone'] !== 'amber',
                            ])>
                                <flux:icon.bell-alert class="size-5" />
                            </div>
                            <div class="space-y-1">
                                <p @class([
                                    'text-[11px] font-black uppercase tracking-[0.2em]',
                                    'text-amber-700 dark:text-amber-300' => $notice['tone'] === 'amber',
                                    'text-sky-700 dark:text-sky-300' => $notice['tone'] !== 'amber',
                                ])>
                                    Alerte de suivi
                                </p>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ $notice['title'] }}
                                </h3>
                                <p class="max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    {{ $notice['message'] }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $notice['supporting'] }}</p>
                            </div>
                        </div>
                        <a href="{{ $notice['action_url'] }}" wire:navigate
                            class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition {{ $notice['tone'] === 'amber' ? 'bg-amber-600 text-white hover:bg-amber-700' : 'bg-sky-600 text-white hover:bg-sky-700' }}">
                            {{ $notice['action_label'] }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($this->isConsultationType())
        <section
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                        <flux:icon.building-office-2 class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Étape 2</p>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Organisation de la visite</h2>
                    </div>
                </div>
            </div>

            <div class="space-y-8 p-5">
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Parcours & actes</p>
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-select.styled label="Département *" wire:model.live="departement_id"
                            :request="route('api.departements')" select="label:name|value:id" searchable />
                        <x-select.styled label="Service" wire:model.live="service_id"
                            :request="['url' => route('api.services'), 'params' => ['departement' => $departement_id]]"
                            select="label:name|value:id" placeholder="Choisir..." :disabled="!$departement_id" />
                        <x-select.styled label="Actes à ajouter" wire:model="acte_ids" :request="[
                            'url' => route('api.actes'),
                            'params' => ['departement' => $departement_id, 'service' => $service_id],
                        ]" select="label:name|value:id" multiple />
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Équipe & projet</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select.styled label="Membre de l'équipe" placeholder="Choisir..." wire:model="user_ids"
                            :request="[
                                'url' => route('api.usersIn'),
                                'params' => ['hopital_id' => current_hopital_id()],
                            ]" select="label:name|value:id" multiple />
                        <x-select.styled label="Projet associé" placeholder="Choisir ou créer"
                            wire:model.live="projet_id" :request="route('api.projets')" select="label:name|value:id"
                            searchable />
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Options avancées</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        @if ($projet_id)
                            <div
                                class="rounded-2xl border border-sky-200 bg-linear-to-br from-sky-50 to-white p-4 dark:border-sky-500/20 dark:from-sky-500/10 dark:to-slate-900/40">
                                <label class="flex items-start gap-3">
                                    <x-toggle wire:model.live="use_project_period" />
                                    <div>
                                        <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Période
                                            automatique au projet</p>
                                        <p class="mt-1 text-xs text-sky-800/80 dark:text-sky-200/80">
                                            Génère une période type lettre-du-projet + numéro (ex. M1).
                                        </p>
                                    </div>
                                </label>
                            </div>
                        @endif
                        <div @class([
                            'rounded-2xl border border-sky-200 bg-linear-to-br from-sky-50 to-white p-4 dark:border-sky-500/20 dark:from-sky-500/10 dark:to-slate-900/40',
                            'md:col-span-2' => ! $projet_id,
                        ])>
                            <label class="mb-3 flex items-start gap-3">
                                <x-toggle wire:model.live="new_visite" />
                                <div>
                                    <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Programmer la
                                        prochaine visite</p>
                                    <p class="mt-1 text-xs text-sky-800/80 dark:text-sky-200/80">
                                        Crée automatiquement le rendez-vous de suivi sur la période déterminée.
                                    </p>
                                </div>
                            </label>
                            <flux:icon.loading wire:loading wire:target="new_visite" />
                            <div wire:loading.remove wire:target="new_visite">
                                @if ($new_visite)
                                    <div class="grid gap-4 border-t border-sky-200/60 pt-4 sm:grid-cols-2 dark:border-sky-500/20">
                                        <x-date wire:model="next_visit_date" label="Date du rendez-vous *"
                                            placeholder="Date de la prochaine visite" :min="today()->format('Y-m-d')" />
                                        <x-input wire:model="next_visit_time" type="time" label="Heure"
                                            placeholder="08:30 par défaut" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($this->isDepistageType())
        <section
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                        <flux:icon.beaker class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Étape 2</p>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Configurer l'examen</h2>
                    </div>
                </div>
            </div>

            <div class="space-y-8 p-5">
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Type d'examen</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label @class([
                            'group cursor-pointer rounded-2xl border-2 p-4 transition',
                            'border-violet-500 bg-violet-50/80 ring-2 ring-violet-500/20 dark:border-violet-400 dark:bg-violet-500/10' => $depistage_target === 'laboratoire',
                            'border-slate-200 bg-white hover:border-violet-200 dark:border-slate-700 dark:bg-slate-900/40' => $depistage_target !== 'laboratoire',
                        ])>
                            <input type="radio" wire:model.live="depistage_target" value="laboratoire"
                                class="sr-only" />
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'flex size-9 items-center justify-center rounded-lg',
                                    'bg-violet-500 text-white' => $depistage_target === 'laboratoire',
                                    'bg-slate-100 text-slate-500 dark:bg-slate-800' => $depistage_target !== 'laboratoire',
                                ])>
                                    <flux:icon.beaker class="size-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-900 dark:text-white">Laboratoire</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Analyses et prélèvements</p>
                                </div>
                            </div>
                        </label>

                        <label @class([
                            'group cursor-pointer rounded-2xl border-2 p-4 transition',
                            'border-violet-500 bg-violet-50/80 ring-2 ring-violet-500/20 dark:border-violet-400 dark:bg-violet-500/10' => $depistage_target === 'imagerie',
                            'border-slate-200 bg-white hover:border-violet-200 dark:border-slate-700 dark:bg-slate-900/40' => $depistage_target !== 'imagerie',
                        ])>
                            <input type="radio" wire:model.live="depistage_target" value="imagerie" class="sr-only" />
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'flex size-9 items-center justify-center rounded-lg',
                                    'bg-violet-500 text-white' => $depistage_target === 'imagerie',
                                    'bg-slate-100 text-slate-500 dark:bg-slate-800' => $depistage_target !== 'imagerie',
                                ])>
                                    <flux:icon.camera class="size-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-900 dark:text-white">Imagerie</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Radio, écho, scanner…</p>
                                </div>
                            </div>
                        </label>

                        <label @class([
                            'group cursor-pointer rounded-2xl border-2 p-4 transition',
                            'border-violet-500 bg-violet-50/80 ring-2 ring-violet-500/20 dark:border-violet-400 dark:bg-violet-500/10' => $depistage_target === 'pacquet_soins',
                            'border-slate-200 bg-white hover:border-violet-200 dark:border-slate-700 dark:bg-slate-900/40' => $depistage_target !== 'pacquet_soins',
                        ])>
                            <input type="radio" wire:model.live="depistage_target" value="pacquet_soins"
                                class="sr-only" />
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'flex size-9 items-center justify-center rounded-lg',
                                    'bg-violet-500 text-white' => $depistage_target === 'pacquet_soins',
                                    'bg-slate-100 text-slate-500 dark:bg-slate-800' => $depistage_target !== 'pacquet_soins',
                                ])>
                                    <flux:icon.rectangle-stack class="size-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-900 dark:text-white">Paquet de soins</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Ensemble d'actes groupés</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-select.styled label="Projet associé" placeholder="Choisir ou créer" wire:model.live="projet_id"
                        :request="route('api.projets')" select="label:name|value:id" searchable />

                    <x-select.styled label="Membre de l'équipe" placeholder="Choisir..."
                                wire:model="user_ids" :request="[
                                    'url' => route('api.usersIn'),
                                    'params' => ['hopital_id' => current_hopital_id()],
                                ]" select="label:name|value:id" multiple />
                </div>

                @if ($depistage_target === 'laboratoire')
                    <div>
                        <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Laboratoire</p>
                        <div class="grid gap-4 md:grid-cols-3">
                            <x-input label="Département" :value="$this->laboratoireDepartement()?->name ?? 'Laboratoire'" disabled />
                            <x-select.styled label="Groupe d'examens" wire:model.live="groupe_examen_id"
                                :request="route('api.groupeExamens')" select="label:name|value:id" searchable
                                placeholder="Choisir un groupe (optionnel)" />
                            <x-select.styled label="Examens de laboratoire *" wire:model="acte_ids" :request="[
                                'url' => route('api.actes'),
                                'params' => ['departement' => $this->laboratoireDepartement()?->id],
                            ]" select="label:name|value:id" multiple />
                        </div>
                    </div>
                @endif

                @if ($depistage_target === 'imagerie')
                    <div>
                        <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Imagerie</p>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input label="Département" :value="$this->imagerieDepartement()?->name ?? 'Imagerie'" disabled />
                            <x-select.styled label="Actes d'imagerie *" wire:model="acte_ids" :request="[
                                'url' => route('api.actes'),
                                'params' => ['departement' => $this->imagerieDepartement()?->id],
                            ]" select="label:name|value:id" multiple />
                        </div>
                    </div>
                @endif

                @if ($depistage_target === 'pacquet_soins')
                    <div>
                        <p class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Paquet de soins
                        </p>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-select.styled label="Paquet de soins *" wire:model.live="pacquet_soin_id"
                                :request="route('api.pacquetSoins')" select="label:name|value:id" searchable />
                            <x-input label="Référence département" :value="$ref ?: 'Auto'" disabled />
                        </div>

                        @if ($pacquet_soin_id)
                            <div
                                class="mt-4 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                                <div
                                    class="border-b border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/80">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Actes du paquet
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Présélectionnés — décochez si
                                        nécessaire.</p>
                                </div>
                                <div class="grid gap-2 p-4 md:grid-cols-2">
                                    @foreach ($paquetActes as $acte)
                                        <label wire:key="depistage-paquet-acte-{{ $acte->id }}"
                                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 transition hover:border-violet-300 hover:bg-violet-50/40 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-violet-600/40">
                                            <input type="checkbox" value="{{ $acte->id }}" wire:model="acte_ids"
                                                class="mt-1 size-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500" />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                                        {{ $acte->name }}</p>
                                                    <span
                                                        class="whitespace-nowrap text-xs font-bold text-violet-700 dark:text-violet-300">
                                                        {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                                                    </span>
                                                </div>
                                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $acte->departement?->name ?: 'Sans département' }}
                                                    @if ($acte->service?->name)
                                                        · {{ $acte->service->name }}
                                                    @endif
                                                </p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Barre d'actions --}}
    <div
        class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 px-4 py-4 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0 text-sm text-slate-500 dark:text-slate-400">
                <span class="font-semibold text-slate-700 dark:text-slate-200">
                    {{ $this->isConsultationType() ? 'Visite' : 'Examen' }}
                </span>
                · Fiche {{ $typeFichierLabels[$type_visite] ?? ucfirst($type_visite) }}
                @if ($date_consultation)
                    · {{ \Illuminate\Support\Carbon::parse($date_consultation)->format('d/m/Y') }}
                @endif
            </div>
            <div class="flex flex-wrap justify-end gap-3">
                <flux:button href="{{ route('patient.show', $patient->id) }}" wire:navigate variant="subtle">
                    Annuler
                </flux:button>
                <flux:button wire:click="save" variant="primary" color="indigo" icon="check"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        {{ $this->isConsultationType() ? 'Ouvrir la visite' : "Initialiser l'examen" }}
                    </span>
                    <span wire:loading wire:target="save">Enregistrement…</span>
                </flux:button>
            </div>
        </div>
    </div>
</div>
