<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Categorisation;
use App\Models\Configs\PacquetSoin;
use App\Models\Configs\Projet;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche categorisation'), Layout('layouts::app.other.support_tech')] class extends Component {
    public Categorisation $categorisation;

    public function mount(int $id): void
    {
        $this->categorisation = Categorisation::query()
            ->withCount(['assurances', 'paquets'])
            ->findOrFail($id);
    }

    #[Computed]
    public function stats(): array
    {
        $assuranceIds = Assurance::query()
            ->where('categorisation_id', $this->categorisation->id)
            ->pluck('id');

        return [
            'assurances' => (int) $this->categorisation->assurances_count,
            'paquets' => (int) $this->categorisation->paquets_count,
            'projets' => $assuranceIds->isEmpty()
                ? 0
                : Projet::query()->whereIn('assurance_id', $assuranceIds)->count(),
        ];
    }

    #[Computed]
    public function recentAssurances(): Collection
    {
        return Assurance::query()
            ->where('categorisation_id', $this->categorisation->id)
            ->where(function ($q) {
                $q->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function recentPaquets(): Collection
    {
        return PacquetSoin::query()
            ->where('categorisation_id', $this->categorisation->id)
            ->where(function ($q) {
                $q->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->latest('created_at')
            ->limit(8)
            ->get();
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Fiche categorisation</flux:heading>

    <x-header_default
        :title="$categorisation->name"
        :subtitle="'Prise en charge : ' . $categorisation->pourcentage . '%'"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Categorisations', 'link' => 'settings/categorisation', 'icon' => 'squares-plus'],
            ['label' => $categorisation->name, 'icon' => 'document-text'],
        ]"
    >
        <x-slot:actions>
            <x-button href="{{ route('settings.categorisation.index') }}" wire:navigate>Retour a la liste</x-button>
            <x-button icon="squares-plus" position="left" href="{{ route('settings.categorisation.create') }}" wire:navigate>
                Nouvelle categorie
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-300">Assurances</p>
            <p class="mt-3 text-3xl font-black text-blue-900 dark:text-blue-100">{{ $this->stats['assurances'] }}</p>
            <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">Partenaires utilisant cette categorie</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Paquets</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['paquets'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Paquets de soins rattaches</p>
        </div>

        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10 sm:col-span-2 xl:col-span-1">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Projets</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['projets'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Campagnes via assurances liees</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_360px]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">Categorisation</p>
                        <h2 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $categorisation->name }}</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ $categorisation->description ?: 'Aucune description n\'a encore ete renseignee pour cette categorie.' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-violet-200 bg-violet-50 px-5 py-4 text-center dark:border-violet-500/30 dark:bg-violet-500/10">
                        <p class="text-xs font-bold uppercase tracking-wide text-violet-700 dark:text-violet-300">Prise en charge</p>
                        <p class="mt-1 text-4xl font-black text-violet-900 dark:text-violet-100">{{ $categorisation->pourcentage }}%</p>
                    </div>
                </div>

                <div class="mt-6">
                    <x-progress :percent="$categorisation->pourcentage" title="Niveau de couverture" />
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Assurances rattachees</flux:heading>
                        <flux:subheading class="mt-1">Partenaires payeurs utilisant cette categorisation.</flux:subheading>
                    </div>
                    <flux:badge color="blue" inset>{{ $this->stats['assurances'] }}</flux:badge>
                </div>

                <div class="space-y-3">
                    @forelse ($this->recentAssurances as $index => $assurance)
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-black text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('settings.assurance.show', $assurance->id) }}" wire:navigate
                                    class="font-bold text-slate-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-300">
                                    {{ $assurance->name }}
                                </a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $assurance->reference }} · {{ ucfirst($assurance->type) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucune assurance n'utilise encore cette categorie.
                            <div class="mt-3">
                                <x-button href="{{ route('settings.assurance.create') }}" wire:navigate size="sm" icon="plus">
                                    Creer une assurance
                                </x-button>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Paquets de soins</flux:heading>
                        <flux:subheading class="mt-1">Offres tarifaires associees a cette categorie.</flux:subheading>
                    </div>
                    <flux:badge color="emerald" inset>{{ $this->stats['paquets'] }}</flux:badge>
                </div>

                <div class="space-y-3">
                    @forelse ($this->recentPaquets as $index => $paquet)
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('settings.paquet.show', $paquet->id) }}" wire:navigate
                                    class="font-bold text-slate-900 hover:text-emerald-600 dark:text-white dark:hover:text-emerald-300">
                                    {{ $paquet->name }}
                                </a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $paquet->paiement_directe ? 'Paiement direct' : 'Paiement differe' }}
                                    · {{ optional($paquet->created_at)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucun paquet de soins n'est rattache a cette categorie.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <flux:heading size="lg">Synthese</flux:heading>
                <div class="mt-5 grid gap-3 text-sm">
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Identifiant</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">#{{ $categorisation->id }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Cree le</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ optional($categorisation->created_at)->format('d/m/Y H:i') ?: '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Derniere mise a jour</p>
                        <p class="mt-1 font-bold text-slate-900 dark:text-white">{{ optional($categorisation->updated_at)->format('d/m/Y H:i') ?: '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-6 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
                <flux:heading size="lg">Chaine de couverture</flux:heading>
                <p class="mt-3 text-sm leading-6 text-violet-900/80 dark:text-violet-100/80">
                    Cette categorie alimente les assurances, qui portent ensuite les projets et campagnes de sante.
                </p>
                <div class="mt-4 space-y-2 text-sm font-semibold text-violet-900 dark:text-violet-100">
                    <p>1. Categorisation ({{ $categorisation->pourcentage }}%)</p>
                    <p>2. Assurances ({{ $this->stats['assurances'] }})</p>
                    <p>3. Projets ({{ $this->stats['projets'] }})</p>
                </div>
            </div>
        </aside>
    </div>
</section>
