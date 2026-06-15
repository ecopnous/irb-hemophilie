<?php

use App\Services\DashboardMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tableau de bord réception')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        $service = app(DashboardMetricsService::class);

        return $service->receptionStats();
    }

    #[Computed]
    public function overview(): array
    {
        return app(DashboardMetricsService::class)->overview();
    }
};
?>

<div class="space-y-6">
    <div class="grid gap-6 xl:grid-cols-[1.5fr,1fr]">
        <div class="space-y-5">
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Réception', 'icon' => 'building-office-2'],
            ]" />

            <div class="space-y-3">
                <p class="text-xs font-black uppercase tracking-[0.28em] text-cyan-700 dark:text-cyan-300">
                    Secrétariat Médical
                </p>
                <div class="space-y-2">
                    <h1 class="max-w-3xl text-3xl font-black tracking-tight text-slate-900 dark:text-white md:text-4xl">
                        Tableau de bord de coordination clinique
                    </h1>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400 md:text-base">
                        Une vue de pilotage pour suivre les consultations, orienter rapidement les patients et garder
                        la réception alignée avec le laboratoire, l'imagerie et la facturation.
                    </p>
                </div>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Total</p>
                    <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['total'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Toutes lignes apres filtrage</p>
                </div>

                <div
                    class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Depistages
                    </p>
                    <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['depistages'] }}
                    </p>
                    <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Demandes orientees par examen</p>
                </div>

                <div
                    class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">
                        À orienter</p>
                    <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">
                        {{ $this->stats['sans_medecin'] }}
                    </p>
                    <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">À affecter un médecin en priorité
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Visite Médicale</p>
                    <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">
                        {{ $this->stats['consultations'] }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Prises en charge cliniques</p>
                </div>

                <div
                    class="rounded-3xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-300">
                        Rendez-Vous</p>
                    <p class="mt-3 text-3xl font-black text-blue-900 dark:text-blue-100">
                        {{ $this->stats['programmees'] }}
                    </p>
                    <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">Consultation prise par rendez-vous
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">
                        Aujourd'hui
                    </p>
                    <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                        {{ $this->stats['aujourd_hui'] }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Entrées du jour</p>
                </div>
            </section>
        </div>

        <div
            class="rounded-[1.75rem] border border-slate-200/70 bg-white/90 p-5 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Raccourcis</p>
                    <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Pôles actifs</h2>
                </div>
                <div class="rounded-2xl bg-cyan-100 p-3 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300">
                    <flux:icon.squares-2x2 class="h-5 w-5" />
                </div>
            </div>

            <div class="mt-5 grid gap-3">
                @foreach (config('navigation.dashboard_shortcuts', []) as $shortcut)
                    <x-nav.dashboard-shortcut :area="$shortcut['area']" :label="$shortcut['label']"
                        :description="$shortcut['description']" :route="$shortcut['route']" :icon="$shortcut['icon'] ?? null"
                        :badge-value="isset($shortcut['badge']) ? $this->overview[$shortcut['badge']] ?? null : null" />
                @endforeach
            </div>
        </div>
    </div>

    <section class="grid gap-4 lg:grid-cols-4">
        <div
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Flux</p>
            <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Orientation</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Priorisez les consultations non assignées, puis basculez rapidement vers le triage ou la fiche patient.
            </p>
        </div>

        <div
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Qualité</p>
            <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Données cliniques</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Les filtres permettent de repérer les consultations incomplètes, les périodes critiques et les files
                chargées.
            </p>
        </div>

        <div
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Coordination</p>
            <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Parcours patient</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Gardez la réception connectée au laboratoire, à l'imagerie et à la facturation sans perdre le contexte
                patient.
            </p>
        </div>

        <div
            class="rounded-[1.75rem] border border-cyan-100 bg-cyan-50/80 p-5 shadow-sm dark:border-cyan-500/20 dark:bg-cyan-500/10">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300">Objectif du jour
            </p>
            <h3 class="mt-2 text-lg font-black text-cyan-900 dark:text-cyan-100">Réduire l'attente</h3>
            <p class="mt-2 text-sm leading-6 text-cyan-800/80 dark:text-cyan-200/80">
                Commencez par les dossiers sans médecin pour fluidifier le passage vers les actes, la facturation et les
                examens.
            </p>
        </div>
    </section>

    <div>
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Suivi opérationnel</p>
            <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Registre des consultations</h2>
        </div>

        <livewire:reception-table />
    </div>
</div>
