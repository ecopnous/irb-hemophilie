<?php

use App\Models\Consultation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Bons d\'analyse laboratoire'), Layout('layouts::app.other.laboratoire')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $genre = '';
    public string $statusTab = 'reception';
    public $date_start;
    public $date_end;

    public array $headers = [['index' => 'reference', 'label' => 'Reference'], ['index' => 'patient', 'label' => 'Patient'], ['index' => 'dossierPatient.age', 'label' => 'Age'], ['index' => 'dossierPatient.genre', 'label' => 'Genre'], ['index' => 'departement.name', 'label' => 'Provenance'], ['index' => 'user.name', 'label' => 'Medecin'], ['index' => 'created_at', 'label' => 'Date'], ['index' => 'prelevement', 'label' => 'Prelevement']];

    public function updating($field): void
    {
        $this->resetPage();
    }

    public function setStatusTab(string $tab): void
    {
        $this->statusTab = $tab;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'genre', 'date_start', 'date_end']);
        $this->statusTab = 'reception';
        $this->resetPage();
    }

    private function consultationQuery()
    {
        return Consultation::query()
            ->with(['dossierPatient', 'departement', 'user', 'laboratoire'])
            ->whereHas('laboratoire')
            ->whereHopitalId(current_hopital_id())
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($q2) use ($term) {
                    $q2->where('reference', 'like', $term)
                        ->orWhereHas('dossierPatient', function ($dq) use ($term) {
                            $dq->where('nom', 'like', $term)->orWhere('postnom', 'like', $term)->orWhere('prenom', 'like', $term)->orWhere('nin', 'like', $term)->orWhere('ins', 'like', $term);
                        })
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', $term));
                });
            })
            ->when($this->genre !== '', fn($q) => $q->whereHas('dossierPatient', fn($dq) => $dq->where('genre', $this->genre)))
            ->when($this->date_start, fn($q) => $q->whereDate('created_at', '>=', $this->date_start))
            ->when($this->date_end, fn($q) => $q->whereDate('created_at', '<=', $this->date_end))
            ->when($this->statusTab === 'attente_validation', fn($q) => $q->whereHas('laboratoire', fn($lq) => $lq->where('statut', 'en attente')))
            ->when($this->statusTab === 'prelevements_complets', fn($q) => $q->whereHas('laboratoire', fn($lq) => $lq->whereNotNull('date_heure_prelevemnt')));
    }

    private function statsQuery()
    {
        return Consultation::query()->whereHas('laboratoire')->whereHopitalId(current_hopital_id());
    }

    protected function laboratoireHighlight(Consultation $consultation): string
    {
        return match ($consultation->laboratoire?->statut) {
            'en attente' => 'amber',
            'en cours' => 'sky',
            'terminé' => 'green',
            'bloqué' => 'rose',
            default => 'slate',
        };
    }

    #[Computed]
    public function rows()
    {
        return $this->consultationQuery()
            ->latest('created_at')
            ->paginate(20)
            ->through(function (Consultation $consultation) {
                $consultation->highlight = $this->laboratoireHighlight($consultation);

                return $consultation;
            });
    }

    #[Computed]
    public function stats(): array
    {
        $base = $this->statsQuery();

        return [
            'reception' => (clone $base)->count(),
            'attente_validation' => (clone $base)->whereHas('laboratoire', fn($q) => $q->where('statut', 'en attente'))->count(),
            'prelevements_complets' => (clone $base)->whereHas('laboratoire', fn($q) => $q->whereNotNull('date_heure_prelevemnt'))->count(),
        ];
    }

    public function tabClasses(string $tab): string
    {
        return $this->statusTab === $tab ? 'border-blue-200 bg-blue-50 text-blue-700 shadow-sm dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300' : 'border-gray-200 bg-white text-gray-600 hover:border-blue-200 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-blue-500/30 dark:hover:text-blue-300';
    }
};
?>

<div class="space-y-6">
    <section
        class="overflow-hidden rounded-[2rem] border border-blue-100 bg-gradient-to-br from-white via-blue-50/60 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Laboratoire', 'icon' => 'beaker'],
                    ]" />

                    <div class="space-y-1">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-blue-600 dark:text-blue-300">
                            Biologie Clinique
                        </p>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            Bons d'analyse laboratoire
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Tableau de reception, suivi des prelevements et validation des bons.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:min-w-[22rem]">
                    <div
                        class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Reception</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->stats['reception'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-amber-100 bg-amber-50/90 px-4 py-3 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">
                            En attente</p>
                        <p class="mt-2 text-3xl font-black text-amber-900 dark:text-amber-100">
                            {{ $this->stats['attente_validation'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-4 py-3 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                            Prelevements</p>
                        <p class="mt-2 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                            {{ $this->stats['prelevements_complets'] }}</p>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col gap-3 border-t border-blue-100/80 pt-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-3">
                    <button type="button" wire:click="setStatusTab('reception')"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $this->tabClasses('reception') }}">
                        <flux:icon.tag class="h-4 w-4" />
                        Reception des bons
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-xs dark:bg-white/10">{{ $this->stats['reception'] }}</span>
                    </button>

                    <button type="button" wire:click="setStatusTab('attente_validation')"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $this->tabClasses('attente_validation') }}">
                        <flux:icon.funnel class="h-4 w-4" />
                        En attente de validation
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-xs dark:bg-white/10">{{ $this->stats['attente_validation'] }}</span>
                    </button>

                    <button type="button" wire:click="setStatusTab('prelevements_complets')"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $this->tabClasses('prelevements_complets') }}">
                        <flux:icon.beaker class="h-4 w-4" />
                        Prelevements complets
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-xs dark:bg-white/10">{{ $this->stats['prelevements_complets'] }}</span>
                    </button>
                </div>

                {{-- <div class="flex flex-wrap gap-3">
                    <flux:button variant="ghost" icon="arrow-down-tray">Import CSV</flux:button>
                    <flux:button variant="primary" color="emerald" icon="document-arrow-down">Exporter Excel
                    </flux:button>
                </div> --}}
            </div>
        </div>
    </section>

    {{-- <x-card header="Filtres de recherche" minimize="mount" loading>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-input label="Recherche" wire:model.live.debounce.500ms="search"
                placeholder="Ref., patient, NIN, medecin..." />

            <x-select.styled label="Genre" wire:model.live="genre" :options="[
                ['label' => 'Tous', 'value' => ''],
                ['label' => 'Masculin', 'value' => 'M'],
                ['label' => 'Feminin', 'value' => 'F'],
            ]"
                select="label:label|value:value" />

            <x-date label="Periode" wire:model.live="date_start" wire:model:end.live="date_end" range />

            <div class="flex items-end">
                <x-button wire:click="resetFilters" outline icon="arrow-path" class="w-full">
                    Reinitialiser
                </x-button>
            </div>
        </div>
    </x-card> --}}

    {{-- <section class="">
        <x-table :$headers :rows="$this->rows" link="/laboratoire/show/{laboratoire_id}" paginate striped highlight
            highlight-property="highlight" wire:navigate>
            @interact('column_reference', $row)
                <div class="font-semibold text-blue-700 dark:text-blue-300">
                    {{ $row['reference'] ?? '-' }}
                </div>
            @endinteract

            @interact('column_patient', $row)
                @php
                    $dp = $row['dossier_patient'] ?? ($row['dossierPatient'] ?? null);
                @endphp
                @if ($dp)
                    <div class="space-y-1">
                        <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
                            {{ trim(($dp['nom'] ?? '') . ' ' . ($dp['postnom'] ?? '') . ' ' . ($dp['prenom'] ?? '')) }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $dp['nin'] ?? '' }} {{ !empty($dp['ins']) ? '• INS ' . $dp['ins'] : '' }}
                        </p>
                    </div>
                @else
                    <span class="text-gray-400">-</span>
                @endif
            @endinteract

            @interact('column_dossierPatient_age', $row)
                {{ $row['dossier_patient']['age'] ?? ($row['dossierPatient']['age'] ?? '-') }}
            @endinteract

            @interact('column_dossierPatient_genre', $row)
                @php
                    $genre = $row['dossier_patient']['genre'] ?? ($row['dossierPatient']['genre'] ?? null);
                @endphp
                <span
                    class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    {{ $genre === 'M' ? 'Masculin' : ($genre === 'F' ? 'Feminin' : '-') }}
                </span>
            @endinteract

            @interact('column_departement_name', $row)
                {{ $row['departement']['name'] ?? ($row['departement']['name'] ?? '-') }}
            @endinteract

            @interact('column_user_name', $row)
                {{ $row['user']['name'] ?? ($row['user']['name'] ?? 'Non assigne') }}
            @endinteract

            @interact('column_created_at', $row)
                @php
                    $createdAt = $row['created_at'] ?? null;
                @endphp
                {{ $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('d/m/Y H:i') : '-' }}
            @endinteract

            @interact('column_prelevement', $row)
                @php
                    $prelevement = data_get($row, 'laboratoire.date_heure_prelevemnt');
                @endphp
                @if ($prelevement)
                    <span
                        class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        {{ \Illuminate\Support\Carbon::parse($prelevement)->format('d/m/Y H:i') }}
                    </span>
                @else
                    <span
                        class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                        En attente
                    </span>
                @endif
            @endinteract

            @interact('column_laboratoire_statut', $row)
                @php
                    $statut = data_get($row, 'laboratoire.statut');
                    $badge = match ($statut) {
                        'en attente' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                        'en cours' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
                        'terminé' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                        'bloqué' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                        default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                    };
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $badge }}">
                    {{ ucfirst($statut ?? 'indefini') }}
                </span>
            @endinteract

            <x-slot:empty>
                <div class="py-10 text-center">
                    <p class="text-base font-semibold text-slate-700 dark:text-slate-200">Aucun bon de laboratoire
                        trouve.</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajustez les filtres ou initialisez une
                        consultation avec bon de laboratoire.</p>
                </div>
            </x-slot:empty>
        </x-table>
    </section> --}}
    <livewire:labo-table />
</div>
