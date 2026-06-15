<?php

use App\Models\Configs\GroupeExamen;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Groupes d\'examens'), Layout('layouts::app.other.laboratoire')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => GroupeExamen::query()->count(),
            'active' => GroupeExamen::query()->where('is_active', true)->count(),
            'with_actes' => GroupeExamen::query()->has('actes')->count(),
        ];
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-2">
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Laboratoire', 'link' => 'laboratoire.index', 'icon' => 'beaker'],
                ['label' => 'Groupes d\'examens', 'icon' => 'rectangle-group'],
            ]" />
            <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                Groupes d'examens
            </h1>
            <p class="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                Composez des ensembles d'examens de laboratoire pour une prescription rapide lors des consultations.
            </p>
        </div>

        <flux:button href="{{ route('laboratoire.groupes.create') }}" wire:navigate variant="primary" color="sky"
            icon="plus">
            Nouveau groupe
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Groupes</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['total'] }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Enregistrés au laboratoire</p>
        </div>
        <div
            class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Actifs</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['active'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Disponibles à la prescription</p>
        </div>
        <div
            class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Avec examens</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['with_actes'] }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Groupes non vides</p>
        </div>
    </div>

    <div
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 md:p-5">
        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Registre</p>
                <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Liste des groupes</h2>
            </div>
        </div>

        <livewire:groupe-examen-table />
    </div>
</div>
