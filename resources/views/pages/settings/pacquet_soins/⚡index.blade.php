<?php

use App\Models\Configs\PacquetSoin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Paquets de soins'), Layout('layouts::app.other.support_tech')] class extends Component {
    use WithPagination;

    public int $quantity = 10;
    public ?string $search = null;

    public array $headers = [
        ['index' => 'name', 'label' => 'Paquet'],
        ['index' => 'categorisation_name', 'label' => 'Categorisation'],
        ['index' => 'actes_count', 'label' => 'Actes'],
        ['index' => 'montant_total', 'label' => 'Montant total'],
        ['index' => 'paiement_badge', 'label' => 'Paiement'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingQuantity(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function rows()
    {
        return PacquetSoin::query()
            ->with('categorisation')
            ->withCount('actes')
            ->withSum('actes', 'montant')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $inner) {
                    $inner
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('categorisation', function (Builder $categorisation) {
                            $categorisation->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate($this->quantity)
            ->through(function (PacquetSoin $paquet) {
                $paquet->name = Str::ucfirst(mb_strtolower((string) $paquet->name));
                $paquet->categorisation_name = $paquet->categorisation?->name ?: 'Non classe';
                $paquet->montant_total = number_format((float) ($paquet->actes_sum_montant ?? 0), 2, ',', ' ') . ' $';
                $paquet->paiement_badge = $paquet->paiement_directe ? 'Direct' : 'Differe';

                return $paquet;
            })
            ->withQueryString();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => PacquetSoin::query()->count(),
            'direct' => PacquetSoin::query()->where('paiement_directe', true)->count(),
            'with_actes' => PacquetSoin::query()->has('actes')->count(),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Gestion des paquets de soins</flux:heading>

    <x-header_default
        title="Paquets de soins"
        subtitle="Catalogue des offres predefinies et de leurs actes medicaux"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings.hopital.index', 'icon' => 'cog-6-tooth'],
            ['label' => 'Paquets de soins', 'icon' => 'briefcase'],
        ]"
    >
        <x-slot:actions>
            <x-button icon="squares-plus" position="left" href="{{ route('settings.paquet.create') }}" wire:navigate>
                Nouveau paquet
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Paquets</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['total'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Offres enregistrees</p>
        </div>
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Paiement direct</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['direct'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Facturation immediate</p>
        </div>
        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Avec actes</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['with_actes'] }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Paquets operationnels</p>
        </div>
    </div>

    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 md:p-5">
        <div class="mb-4">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Registre</p>
            <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Liste des paquets de soins</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cliquez sur une ligne pour ouvrir la fiche detaillee.</p>
        </div>

        <x-table :$headers :rows="$this->rows" filter paginate loading link="/settings/paquet/show/{id}" />
    </div>
</section>
