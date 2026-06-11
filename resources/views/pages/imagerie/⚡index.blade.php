<?php

use App\Models\Consultation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bons d\'imagerie')] class extends Component {
    private function statsQuery()
    {
        return Consultation::query()
            ->whereHas('imagerie')
            ->whereHopitalId(current_hopital_id());
    }

    #[Computed]
    public function stats(): array
    {
        $base = $this->statsQuery();

        return [
            'reception' => (clone $base)->count(),
            'en_attente' => (clone $base)->whereHas('imagerie', fn($q) => $q->where('statut', 'en attente'))->count(),
            'en_cours' => (clone $base)->whereHas('imagerie', fn($q) => $q->where('statut', 'en cours'))->count(),
            'termines' => (clone $base)->whereHas('imagerie', fn($q) => $q->where('statut', 'terminé'))->count(),
        ];
    }
};
?>

<div class="space-y-6">
    <section
        class="overflow-hidden rounded-4xl border border-fuchsia-100 bg-linear-to-br from-white via-fuchsia-50/60 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Imagerie', 'icon' => 'photo'],
                    ]" />

                    <div class="space-y-1">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-fuchsia-600 dark:text-fuchsia-300">
                            Imagerie medicale
                        </p>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            Bons d'imagerie
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Tableau PowerGrid des demandes d'imagerie avec recherche, filtres, examens demandes et
                            acces direct au compte rendu.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4 lg:min-w-md">
                    <div
                        class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Reception</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ $this->stats['reception'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-amber-100 bg-amber-50/90 px-4 py-3 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">
                            En attente</p>
                        <p class="mt-2 text-3xl font-black text-amber-900 dark:text-amber-100">
                            {{ $this->stats['en_attente'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-sky-100 bg-sky-50/90 px-4 py-3 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">En
                            cours</p>
                        <p class="mt-2 text-3xl font-black text-sky-900 dark:text-sky-100">
                            {{ $this->stats['en_cours'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-4 py-3 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                            Termines</p>
                        <p class="mt-2 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                            {{ $this->stats['termines'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="rounded-[1.75rem] border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <div class="mb-4 space-y-1">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">Tableau des bons d'imagerie</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Utilisez la recherche PowerGrid pour retrouver un patient, un examen, un statut ou un medecin, puis
                ouvrez directement le bon concerne.
            </p>
        </div>

        <livewire:imagerie-table />
    </section>
</div>
