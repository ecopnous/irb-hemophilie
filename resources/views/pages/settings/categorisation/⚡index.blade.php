<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Categorisation;
use App\Models\Configs\PacquetSoin;
use App\Models\Configs\Projet;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Categorisations'), Layout('layouts::app.other.support_tech')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        $categorieIds = Categorisation::query()->pluck('id');

        return [
            'categories' => $categorieIds->count(),
            'assurances' => $categorieIds->isEmpty()
                ? 0
                : Assurance::query()->whereIn('categorisation_id', $categorieIds)->count(),
            'paquets' => $categorieIds->isEmpty()
                ? 0
                : PacquetSoin::query()->whereIn('categorisation_id', $categorieIds)->count(),
            'projets' => $categorieIds->isEmpty()
                ? 0
                : Projet::query()
                    ->whereIn('assurance_id', Assurance::query()->whereIn('categorisation_id', $categorieIds)->pluck('id'))
                    ->count(),
            'moyenne_prise_en_charge' => round((float) Categorisation::query()->avg('pourcentage'), 1),
        ];
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Gestion des categorisations</flux:heading>

    <x-header_default
        title="Categorisations"
        subtitle="Niveaux de prise en charge pour les assurances, paquets de soins et projets"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Categorisations', 'icon' => 'squares-plus'],
        ]"
    >
        <x-slot:actions>
            <x-button icon="squares-plus" position="left" href="{{ route('settings.categorisation.create') }}" wire:navigate>
                Nouvelle categorie
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Categories</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['categories'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Niveaux de prise en charge</p>
        </div>

        <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-300">Assurances</p>
            <p class="mt-3 text-3xl font-black text-blue-900 dark:text-blue-100">{{ $this->stats['assurances'] }}</p>
            <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">Partenaires rattaches</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Paquets</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['paquets'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Paquets de soins lies</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Moyenne</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['moyenne_prise_en_charge'] }}%</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Prise en charge moyenne</p>
        </div>
    </div>

    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 md:p-5">
        <div class="mb-4">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Registre</p>
            <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Liste des categorisations</h2>
        </div>

        <livewire:categorisation-table />
    </div>
</section>
