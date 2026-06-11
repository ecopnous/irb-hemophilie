<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Projet;
use App\Models\Consultation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projets et campagnes'), Layout('layouts::app.other.support_tech')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        $projetIds = Projet::query()->pluck('id');

        return [
            'projets' => Projet::query()->count(),
            'avec_assurance' => Projet::query()->whereNotNull('assurance_id')->count(),
            'consultations' => $projetIds->isEmpty()
                ? 0
                : Consultation::query()->whereIn('projet_id', $projetIds)->count(),
            'assurances_disponibles' => Assurance::query()
                ->where(function ($q) {
                    $q->where('is_delete', false)->orWhereNull('is_delete');
                })
                ->count(),
        ];
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Gestion des projets et campagnes</flux:heading>

    <x-header_default
        title="Projets et campagnes"
        subtitle="Pilotage des campagnes de sante rattachees a une assurance porteuse"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Projets', 'icon' => 'clipboard-document-check'],
        ]"
    >
        <x-slot:actions>
            <x-button icon="clipboard-document-check" position="left" href="{{ route('settings.projet.create') }}" wire:navigate>
                Nouveau projet
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Projets</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['projets'] }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Campagnes enregistrees</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Assurees</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['avec_assurance'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Projets avec assurance liee</p>
        </div>

        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Consultations</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['consultations'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Prises en charge rattachees</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Assurances</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['assurances_disponibles'] }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Partenaires disponibles</p>
        </div>
    </div>

    @if ($this->stats['assurances_disponibles'] === 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <p class="font-semibold">Aucune assurance enregistree</p>
            <p class="mt-1">Creez une assurance avant d'ajouter un projet ou une campagne.</p>
            <x-button href="{{ route('settings.assurance.create') }}" wire:navigate class="mt-3" icon="plus">
                Creer une assurance
            </x-button>
        </div>
    @endif

    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 md:p-5">
        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Registre</p>
                <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Liste des projets</h2>
            </div>
        </div>

        <livewire:projet-table />
    </div>
</section>
