<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Categorisation;
use App\Models\Configs\Projet;
use App\Models\DossierPatient;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Assurances'), Layout('layouts::app.other.support_tech')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        $assuranceIds = Assurance::query()
            ->where(function ($q) {
                $q->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->pluck('id');

        return [
            'assurances' => $assuranceIds->count(),
            'avec_categorie' => Assurance::query()
                ->whereIn('id', $assuranceIds)
                ->whereNotNull('categorisation_id')
                ->count(),
            'projets' => Projet::query()->whereIn('assurance_id', $assuranceIds)->count(),
            'patients' => $assuranceIds->isEmpty()
                ? 0
                : DossierPatient::query()->whereIn('assurance_id', $assuranceIds)->count(),
            'categories_disponibles' => Categorisation::query()->count(),
        ];
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Gestion des assurances</flux:heading>

    <x-header_default
        title="Assurances"
        subtitle="Gestion des partenaires payeurs et de leurs categorisations de prise en charge"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Assurances', 'icon' => 'shield-check'],
        ]"
    >
        <x-slot:actions>
            <x-button icon="shield-check" position="left" href="{{ route('settings.assurance.create') }}" wire:navigate>
                Nouvelle assurance
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-300">Assurances</p>
            <p class="mt-3 text-3xl font-black text-blue-900 dark:text-blue-100">{{ $this->stats['assurances'] }}</p>
            <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">Partenaires enregistres</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Categorisees</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['avec_categorie'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Avec prise en charge definie</p>
        </div>

        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Projets</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['projets'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Campagnes rattachees</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Patients</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['patients'] }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dossiers couverts</p>
        </div>
    </div>

    @if ($this->stats['categories_disponibles'] === 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <p class="font-semibold">Aucune categorisation enregistree</p>
            <p class="mt-1">Creez une categorisation avant d'ajouter une assurance.</p>
            <x-button href="{{ route('settings.categorisation.create') }}" wire:navigate class="mt-3" icon="plus">
                Creer une categorisation
            </x-button>
        </div>
    @endif

    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 md:p-5">
        <div class="mb-4">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Registre</p>
            <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Liste des assurances</h2>
        </div>

        <livewire:assurance-table />
    </div>
</section>
