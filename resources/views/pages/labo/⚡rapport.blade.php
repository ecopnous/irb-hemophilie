<?php

use App\Models\Laboratoire;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Rapports laboratoire'), Layout('layouts::app.other.laboratoire')] class extends Component {
    protected function reportsQuery()
    {
        return Laboratoire::query()
            ->whereHopitalId(current_hopital_id())
            ->where(function ($query) {
                $query
                    ->where('statut', 'terminé')
                    ->orWhereNotNull('date_heure_validation')
                    ->orWhereNotNull('user_valideur_id');
            });
    }

    #[Computed]
    public function stats(): array
    {
        $base = $this->reportsQuery();

        return [
            'total' => (clone $base)->count(),
            'termines' => (clone $base)->where('statut', 'terminé')->count(),
            'valides_today' => (clone $base)->whereDate('date_heure_validation', today())->count(),
            'avec_commentaire' => (clone $base)->whereNotNull('commentaire')->count(),
        ];
    }
};
?>

<div class="space-y-6">
    <section
        class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/60 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Laboratoire', 'link' => 'laboratoire.index', 'icon' => 'beaker'],
                        ['label' => 'Rapports', 'icon' => 'document-chart-bar'],
                    ]" />

                    <div class="space-y-1">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-emerald-600 dark:text-emerald-300">
                            Archivage et validation
                        </p>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            Rapports du laboratoire
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Tous les bons de laboratoire déjà validés ou terminés, avec leur statut final, date de
                            validation et valideur.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 lg:min-w-[24rem] xl:grid-cols-4">
                    <div
                        class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Rapports</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->stats['total'] }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-4 py-3 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                            Terminés
                        </p>
                        <p class="mt-2 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                            {{ $this->stats['termines'] }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-blue-100 bg-blue-50/90 px-4 py-3 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">
                            Validés aujourd'hui
                        </p>
                        <p class="mt-2 text-3xl font-black text-blue-900 dark:text-blue-100">
                            {{ $this->stats['valides_today'] }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Commentaires</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->stats['avec_commentaire'] }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-emerald-100/70 bg-white/70 px-4 py-4 text-sm text-slate-600 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
                Cette page regroupe les bons qui ont déjà passé l'étape de validation: bons terminés, bons datés de
                validation, ou bons déjà associés à un valideur.
            </div>
        </div>
    </section>

    <section class="rounded-[1.75rem] border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <div class="mb-4 space-y-1">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">Liste des bons validés</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Utilisez la recherche PowerGrid pour retrouver un patient, un médecin, un valideur ou une date de validation.
            </p>
        </div>

        <livewire:labo-table context="rapport" />
    </section>
</div>
