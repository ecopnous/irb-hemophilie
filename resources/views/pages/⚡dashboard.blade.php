<?php

use App\Services\DashboardMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tableau de bord réception')] class extends Component {
    public string $search = '';
    public string $type = '';
    public string $genre = '';
    public string $user_id = '';
    public string $departement_id = '';
    public string $assignment = '';
    public $province_id;
    public $ville_id;
    public $commune_id;
    public $age_min;
    public $age_max;
    public $date_start;
    public $date_end;

    private function filterPayload(): array
    {
        return [
            'search' => $this->search,
            'type' => $this->type,
            'genre' => $this->genre,
            'user_id' => $this->user_id,
            'departement_id' => $this->departement_id,
            'assignment' => $this->assignment,
            'province_id' => $this->province_id,
            'ville_id' => $this->ville_id,
            'commune_id' => $this->commune_id,
            'age_min' => $this->age_min,
            'age_max' => $this->age_max,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
        ];
    }

    #[Computed]
    public function stats(): array
    {
        $service = app(DashboardMetricsService::class);

        return $service->aggregateStats($service->receptionQuery($this->filterPayload()));
    }

    #[Computed]
    public function overview(): array
    {
        return app(DashboardMetricsService::class)->overview();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'type', 'genre', 'user_id', 'departement_id', 'assignment', 'province_id', 'ville_id', 'commune_id', 'age_min', 'age_max', 'date_start', 'date_end']);
    }
};
?>

<div class="space-y-6">
    <div class="grid gap-6 xl:grid-cols-[1.5fr,1fr]">
        <div class="space-y-5">
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Réception', 'icon' => 'building-office-2'],
            ]" />

            <div class="space-y-3">
                <p class="text-xs font-black uppercase tracking-[0.28em] text-cyan-700 dark:text-cyan-300">
                    Secrétariat Médical
                </p>
                <div class="space-y-2">
                    <h1 class="max-w-3xl text-3xl font-black tracking-tight text-slate-900 dark:text-white md:text-4xl">
                        Tableau de bord de coordination clinique
                    </h1>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400 md:text-base">
                        Une vue de pilotage pour suivre les consultations, orienter rapidement les patients et garder
                        la réception alignée avec le laboratoire, l'imagerie et la facturation.
                    </p>
                </div>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Total</p>
                    <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['total'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Toutes lignes apres filtrage</p>
                </div>

                <div
                    class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Depistages
                    </p>
                    <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['depistages'] }}
                    </p>
                    <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Demandes orientees par examen</p>
                </div>

                <div
                    class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">
                        À orienter</p>
                    <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">
                        {{ $this->stats['sans_medecin'] }}
                    </p>
                    <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">À affecter un médecin en priorité
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Visite Médicale</p>
                    <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">
                        {{ $this->stats['consultations'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Prises en charge cliniques</p>
                </div>

                <div
                    class="rounded-3xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-300">
                        Rendez-Vous</p>
                    <p class="mt-3 text-3xl font-black text-blue-900 dark:text-blue-100">
                        {{ $this->stats['programmees'] }}
                    </p>
                    <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">Consultation prise par rendez-vous
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">
                        Aujourd'hui
                    </p>
                    <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                        {{ $this->stats['aujourd_hui'] }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Entrées du jour</p>
                </div>
            </section>
        </div>

        <div
            class="rounded-[1.75rem] border border-slate-200/70 bg-white/90 p-5 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Raccourcis</p>
                    <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Pôles actifs</h2>
                </div>
                <div class="rounded-2xl bg-cyan-100 p-3 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300">
                    <flux:icon.squares-2x2 class="h-5 w-5" />
                </div>
            </div>

            <div class="mt-5 grid gap-3">
                <a href="{{ route('consultation.triage') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-200 hover:bg-cyan-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Triage</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Patients à orienter</p>
                    </div>
                    <span
                        class="rounded-full bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">{{ $this->overview['triage'] }}</span>
                </a>

                <a href="{{ route('laboratoire.index') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-200 hover:bg-cyan-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Laboratoire</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Bons en circulation</p>
                    </div>
                    <span
                        class="rounded-full bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">{{ $this->overview['laboratoire'] }}</span>
                </a>

                <a href="{{ route('facturation.index') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-200 hover:bg-cyan-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Facturation</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Dossiers à traiter</p>
                    </div>
                    <span
                        class="rounded-full bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">{{ $this->overview['facturation'] }}</span>
                </a>

                <a href="{{ route('imagerie.index') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-200 hover:bg-cyan-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Imagerie</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Demandes associées</p>
                    </div>
                    <span
                        class="rounded-full bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">{{ $this->overview['imagerie'] }}</span>
                </a>

                <a href="{{ route('reception.papeterie') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-200 hover:bg-cyan-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Papeterie</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Stocks & consommables</p>
                    </div>
                    <flux:icon.clipboard-document-list class="h-5 w-5 text-cyan-600 dark:text-cyan-300" />
                </a>

                <a href="{{ route('reception.services') }}" wire:navigate
                    class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-200 hover:bg-cyan-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/10">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">Services de base</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Prestations reception</p>
                    </div>
                    <flux:icon.briefcase class="h-5 w-5 text-cyan-600 dark:text-cyan-300" />
                </a>
            </div>
        </div>
    </div>

    <section class="grid gap-4 lg:grid-cols-4">
        <div
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Flux</p>
            <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Orientation</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Priorisez les consultations non assignées, puis basculez rapidement vers le triage ou la fiche patient.
            </p>
        </div>

        <div
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Qualité</p>
            <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Données cliniques</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Les filtres permettent de repérer les consultations incomplètes, les périodes critiques et les files
                chargées.
            </p>
        </div>

        <div
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Coordination</p>
            <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Parcours patient</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Gardez la réception connectée au laboratoire, à l'imagerie et à la facturation sans perdre le contexte
                patient.
            </p>
        </div>

        <div
            class="rounded-[1.75rem] border border-cyan-100 bg-cyan-50/80 p-5 shadow-sm dark:border-cyan-500/20 dark:bg-cyan-500/10">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300">Objectif du jour
            </p>
            <h3 class="mt-2 text-lg font-black text-cyan-900 dark:text-cyan-100">Réduire l'attente</h3>
            <p class="mt-2 text-sm leading-6 text-cyan-800/80 dark:text-cyan-200/80">
                Commencez par les dossiers sans médecin pour fluidifier le passage vers les actes, la facturation et les
                examens.
            </p>
        </div>
    </section>

    <div>
        <div class="flex flex-col gap-2 px-3 pt-2 pb-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Suivi opérationnel</p>
                <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Registre des consultations</h2>
            </div>
            <div
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="font-semibold text-slate-900 dark:text-white">Consultations Aujourd'hui :
                    {{ $this->stats['aujourd_hui'] }}</p>
            </div>
        </div>

        <div class="mb-4">
            <x-card header="Recherche avancee" minimize="mount">
                <div class="space-y-5 p-5">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <x-input label="Recherche" wire:model.live.debounce.400ms="search"
                            placeholder="Reference, NIN, INS, nom..." />

                        <x-select.styled label="Type" wire:model.live="type" :options="[
                            ['label' => 'Tous', 'value' => ''],
                            ['label' => 'Consultation', 'value' => 'consultation'],
                            ['label' => 'Depistage', 'value' => 'depistage'],
                        ]"
                            select="label:label|value:value" />

                        <x-select.styled label="Affectation" wire:model.live="assignment" :options="[
                            ['label' => 'Toutes', 'value' => ''],
                            ['label' => 'Assignees', 'value' => 'assigned'],
                            ['label' => 'Sans medecin', 'value' => 'unassigned'],
                        ]"
                            select="label:label|value:value" />

                        <x-select.styled label="Departement" wire:model.live="departement_id" :request="route('api.departements')"
                            select="label:name|value:id" searchable />

                        <x-select.styled label="Medecin" wire:model.live="user_id" :request="[
                            'url' => route('api.usersConnected'),
                            'params' => ['hopital_id' => current_hopital_id()],
                        ]"
                            select="label:name|value:id" searchable />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <x-select.styled label="Genre" wire:model.live="genre" :options="[
                            ['label' => 'Tous', 'value' => ''],
                            ['label' => 'Homme', 'value' => 'M'],
                            ['label' => 'Femme', 'value' => 'F'],
                        ]"
                            select="label:label|value:value" />

                        <x-date label="Periode" wire:model.live="date_start" wire:model:end.live="date_end" range />

                        <div class="grid grid-cols-2 gap-3">
                            <x-number label="Age min" wire:model.live="age_min" min="0" />
                            <x-number label="Age max" wire:model.live="age_max" min="0" />
                        </div>

                        <x-select.styled label="Province" wire:model.live="province_id" :request="route('api.provinces')"
                            select="label:name|value:id" searchable />

                        <x-select.styled label="Ville" wire:model.live="ville_id" :request="[
                            'url' => route('api.villes'),
                            'params' => ['province' => $province_id],
                        ]" :disabled="!$province_id"
                            wire:key="consultation-ville-{{ $province_id }}" select="label:name|value:id"
                            searchable />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <x-select.styled label="Commune" wire:model.live="commune_id" :request="[
                            'url' => route('api.communes'),
                            'params' => ['ville' => $ville_id],
                        ]"
                            :disabled="!$ville_id" wire:key="consultation-commune-{{ $ville_id }}"
                            select="label:name|value:id" searchable />
                        <x-button wire:click="resetFilters" outline icon="arrow-path">
                            Reinitialiser
                        </x-button>
                    </div>
                </div>
            </x-card>
        </div>

        <livewire:reception-table :search="$search" :type="$type" :genre="$genre" :user_id="$user_id"
            :departement_id="$departement_id" :assignment="$assignment" :province_id="$province_id" :ville_id="$ville_id" :commune_id="$commune_id"
            :age_min="$age_min" :age_max="$age_max" :date_start="$date_start" :date_end="$date_end" defer />
    </div>
</div>
