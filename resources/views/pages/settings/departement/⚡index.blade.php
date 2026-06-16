<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use App\Models\Configs\Service;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Départements'), Layout('layouts::app.other.support_tech')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        return [
            'departements' => Departement::query()->where('is_delete', false)->count(),
            'services' => Service::query()->where('is_delete', false)->count(),
            'actes' => Acte::query()->where('is_delete', false)->count(),
            'avec_chef' => Departement::query()->where('is_delete', false)->whereNotNull('user_id')->count(),
        ];
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Gestion des départements</flux:heading>

    <x-header_default
        title="Départements médicaux"
        subtitle="Organisation des services, actes et responsables de département"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Départements', 'icon' => 'building-office-2'],
        ]"
    />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-indigo-200 bg-indigo-50/80 p-5 shadow-sm dark:border-indigo-500/20 dark:bg-indigo-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-indigo-700 dark:text-indigo-300">Départements</p>
            <p class="mt-3 text-3xl font-black text-indigo-900 dark:text-indigo-100">{{ $this->stats['departements'] }}</p>
            <p class="mt-1 text-xs text-indigo-700/80 dark:text-indigo-300/80">Unités cliniques actives</p>
        </div>

        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Services</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['services'] }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Services rattachés</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Actes</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['actes'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Actes médicaux catalogués</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Chefs nommés</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['avec_chef'] }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Départements avec responsable</p>
        </div>
    </div>

    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 md:p-5">
        <div class="mb-4">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Registre</p>
            <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Liste des départements</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Consultez les effectifs, services, actes et chefs de département.
            </p>
        </div>

        <livewire:departement-table />
    </div>
</section>
