<?php

use App\Models\facturation\Facturation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Attributes\Layout;

new #[Title('Facturation et caisse'), Layout('layouts::app.other.facturation')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $genre = '';
    public string $statusTab = 'toutes';
    public $date_start;
    public $date_end;

    public array $headers = [['index' => 'consultation.reference', 'label' => 'Reference'], ['index' => 'patient', 'label' => 'Patient'], ['index' => 'consultation.departement.name', 'label' => 'Departement'], ['index' => 'consultation.assurance.name', 'label' => 'Prise en charge'], ['index' => 'montant_total', 'label' => 'Montant'], ['index' => 'etat_facture', 'label' => 'Etat'], ['index' => 'created_at', 'label' => 'Date']];

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
        $this->statusTab = 'toutes';
        $this->resetPage();
    }

    private function baseQuery()
    {
        return Facturation::query()
            ->with(['dossierPatient', 'consultation.dossierPatient', 'consultation.departement', 'consultation.assurance', 'consultation.actes'])
            ->whereHas('consultation', fn($q) => $q->whereHopitalId(current_hopital_id()))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($q2) use ($term) {
                    $q2->whereHas('consultation', fn($cq) => $cq->where('reference', 'like', $term))->orWhereHas('dossierPatient', function ($dq) use ($term) {
                        $dq->where('nom', 'like', $term)->orWhere('postnom', 'like', $term)->orWhere('prenom', 'like', $term)->orWhere('nin', 'like', $term)->orWhere('ins', 'like', $term);
                    });
                });
            })
            ->when($this->genre !== '', fn($q) => $q->whereHas('dossierPatient', fn($dq) => $dq->where('genre', $this->genre)))
            ->when($this->date_start, fn($q) => $q->whereDate('created_at', '>=', $this->date_start))
            ->when($this->date_end, fn($q) => $q->whereDate('created_at', '<=', $this->date_end));
    }

    protected function billingSummary(Facturation $facturation): array
    {
        $actes = $facturation->consultation?->actes ?? collect();

        $total = (float) $actes->sum(fn($acte) => (float) ($acte->pivot->montant ?? 0));
        $paid = (float) $facturation->payments()->whereNull('voided_at')->sum('amount');

        $status = match (true) {
            $total <= 0 => 'a_facturer',
            $paid <= 0 => 'en_attente',
            $paid < $total => 'partiel',
            default => 'paye',
        };

        return [
            'total' => $total,
            'paid' => $paid,
            'status' => $status,
        ];
    }

    protected function statusColor(string $status): string
    {
        return match ($status) {
            'a_facturer' => 'slate',
            'en_attente' => 'amber',
            'partiel' => 'sky',
            'paye' => 'green',
            default => 'slate',
        };
    }

    #[Computed]
    public function filteredRows()
    {
        $collection = $this->baseQuery()
            ->latest('created_at')
            ->get()
            ->map(function (Facturation $facturation) {
                $summary = $this->billingSummary($facturation);

                $facturation->montant_total = $summary['total'];
                $facturation->montant_paye = $summary['paid'];
                $facturation->etat_facture = $summary['status'];
                $facturation->highlight = $this->statusColor($summary['status']);

                return $facturation;
            })
            ->filter(fn(Facturation $facturation) => $this->statusTab === 'toutes' || $facturation->etat_facture === $this->statusTab)
            ->values();

        $page = Paginator::resolveCurrentPage() ?: 1;
        $perPage = 20;
        $items = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($items, $collection->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    #[Computed]
    public function stats(): array
    {
        $items = $this->baseQuery()->get()->map(fn(Facturation $facturation) => $this->billingSummary($facturation));

        return [
            'toutes' => $items->count(),
            'a_facturer' => $items->where('status', 'a_facturer')->count(),
            'en_attente' => $items->where('status', 'en_attente')->count(),
            'partiel' => $items->where('status', 'partiel')->count(),
            'paye' => $items->where('status', 'paye')->count(),
            'montant_total' => (float) $items->sum('total'),
        ];
    }

    public function tabClasses(string $tab): string
    {
        return $this->statusTab === $tab ? 'border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-gray-200 bg-white text-gray-600 hover:border-emerald-200 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-emerald-500/30 dark:hover:text-emerald-300';
    }
};
?>

<div class="space-y-4">
    <section
        class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/70 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Facturation', 'icon' => 'banknotes'],
                    ]" />

                    <div class="space-y-1">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-emerald-600 dark:text-emerald-300">
                            Facturation et Caisse
                        </p>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            Suivi des factures patients
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Vue centralisée des consultations facturées et de l'etat de paiement.
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-4 py-3 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                        Montant cumule</p>
                    <p class="mt-2 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                        {{ number_format($this->stats['montant_total'], 2, ',', ' ') }}</p>
                </div>
            </div>

            <div
                class="flex flex-col gap-3 border-t border-emerald-100/80 pt-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-3">
                    <button type="button" wire:click="setStatusTab('toutes')"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $this->tabClasses('toutes') }}">
                        <flux:icon.receipt-percent class="h-4 w-4" />
                        Toutes
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-xs dark:bg-white/10">{{ $this->stats['toutes'] }}</span>
                    </button>
                    <button type="button" wire:click="setStatusTab('a_facturer')"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $this->tabClasses('a_facturer') }}">
                        <flux:icon.clipboard-document-list class="h-4 w-4" />
                        A facturer
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-xs dark:bg-white/10">{{ $this->stats['a_facturer'] }}</span>
                    </button>
                    <button type="button" wire:click="setStatusTab('en_attente')"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $this->tabClasses('en_attente') }}">
                        <flux:icon.clock class="h-4 w-4" />
                        En attente
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-xs dark:bg-white/10">{{ $this->stats['en_attente'] }}</span>
                    </button>
                    <button type="button" wire:click="setStatusTab('partiel')"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $this->tabClasses('partiel') }}">
                        <flux:icon.scale class="h-4 w-4" />
                        Paiement partiel
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-xs dark:bg-white/10">{{ $this->stats['partiel'] }}</span>
                    </button>
                    <button type="button" wire:click="setStatusTab('paye')"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $this->tabClasses('paye') }}">
                        <flux:icon.check-badge class="h-4 w-4" />
                        Paye
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-xs dark:bg-white/10">{{ $this->stats['paye'] }}</span>
                    </button>
                </div>
                {{-- 
                <div class="flex flex-wrap gap-3">
                    <flux:button variant="ghost" icon="printer">Imprimer lot</flux:button>
                    <flux:button variant="primary" color="emerald" icon="document-arrow-down">Exporter Excel
                    </flux:button>
                </div> --}}
            </div>
        </div>
    </section>

    {{-- <x-card header="Filtres de recherche" minimize="mount" loading>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-input label="Recherche" wire:model.live.debounce.500ms="search"
                placeholder="Reference, patient, NIN, INS..." />

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

    {{-- <x-table :headers="$headers" :rows="$this->filteredRows" link="facturation/show/{facture_id}" paginate striped highlight
        highlight-property="highlight" wire:navigate>
        @interact('column_consultation_reference', $row)
            <div class="font-semibold text-emerald-700 dark:text-emerald-300">
                {{ data_get($row, 'consultation.reference', '-') }}
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

        @interact('column_consultation_departement_name', $row)
            {{ data_get($row, 'consultation.departement.name', '-') }}
        @endinteract

        @interact('column_consultation_assurance_name', $row)
            {{ data_get($row, 'consultation.assurance.name', 'Paiement direct') }}
        @endinteract

        @interact('column_montant_total', $row)
            @php
                $total = (float) ($row['montant_total'] ?? 0);
                $paid = (float) ($row['montant_paye'] ?? 0);
            @endphp
            <div class="space-y-1">
                <p class="font-semibold text-slate-900 dark:text-white">{{ number_format($total, 2, ',', ' ') }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Paye: {{ number_format($paid, 2, ',', ' ') }}</p>
            </div>
        @endinteract

        @interact('column_etat_facture', $row)
            @php
                $etat = $row['etat_facture'] ?? 'a_facturer';
                $badge = match ($etat) {
                    'a_facturer' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                    'en_attente' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                    'partiel' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
                    'paye' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                    default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                };
                $label = match ($etat) {
                    'a_facturer' => 'A facturer',
                    'en_attente' => 'En attente',
                    'partiel' => 'Partiel',
                    'paye' => 'Paye',
                    default => ucfirst($etat),
                };
            @endphp
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $badge }}">
                {{ $label }}
            </span>
        @endinteract

        @interact('column_created_at', $row)
            @php
                $createdAt = $row['created_at'] ?? null;
            @endphp
            {{ $createdAt ? \Illuminate\Support\Carbon::parse($createdAt)->format('d/m/Y H:i') : '-' }}
        @endinteract

        <x-slot:empty>
            <div class="py-10 text-center">
                <p class="text-base font-semibold text-slate-700 dark:text-slate-200">Aucune facture trouvee.</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Initialisez des consultations pour
                    alimenter la file de facturation.</p>
            </div>
        </x-slot:empty>
    </x-table> --}}

    <livewire:facturation-table />
</div>
