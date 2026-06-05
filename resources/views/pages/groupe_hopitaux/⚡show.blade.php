<?php

use App\Models\Configs\GroupeHopital;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail groupe d hopitaux')] class extends Component {
    public GroupeHopital $groupe;

    public function mount(int $id): void
    {
        $this->groupe = GroupeHopital::query()
            ->with(['user', 'hopitaux'])
            ->withCount('hopitaux')
            ->findOrFail($id);
    }

    #[Computed]
    public function activeHopitauxCount(): int
    {
        return $this->groupe->hopitaux->where('is_actif', true)->count();
    }

    #[Computed]
    public function typesSummary(): string
    {
        $types = $this->groupe->hopitaux
            ->groupBy('type')
            ->map(fn($items, $type) => ucfirst((string) $type) . ' (' . $items->count() . ')')
            ->values()
            ->all();

        return $types === [] ? 'Aucun type disponible' : implode(' / ', $types);
    }
};
?>

<section class="w-full space-y-6">
    <x-header_default :title="__('Detail du groupe')" :subtitle="__(
        'Vue complete du groupe, de son objectif et des hopitaux rattaches.',
    )" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Groupes d hopitaux', 'link' => 'groupe_hopitaux.index', 'icon' => 'building-office-2'],
        ['label' => $groupe->nom, 'icon' => 'eye'],
    ]">
        <x-slot:actions>
            <x-button icon="arrow-left" position="left" href="{{ route('groupe_hopitaux.index') }}" wire:navigate>
                Retour
            </x-button>
            <x-button icon="plus" position="left" href="{{ route('groupe_hopitaux.create') }}" wire:navigate>
                Nouveau groupe
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-6 px-4 pb-10 sm:px-6 lg:px-8 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <div class="space-y-6">
            <div
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div
                    class="border-b border-slate-200 bg-gradient-to-br from-blue-50 via-white to-slate-50 px-6 py-6 dark:border-slate-800 dark:from-blue-950/30 dark:via-slate-950 dark:to-slate-900">
                    <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-blue-600 dark:text-blue-300">
                                Groupe d hopitaux
                            </p>
                            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">
                                {{ $groupe->nom }}
                            </h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                {{ $groupe->objetif ?: 'Aucun objectif n a encore ete renseigne pour ce groupe.' }}
                            </p>
                        </div>

                        <flux:badge color="emerald" inset>
                            {{ $groupe->hopitaux_count }} hopital(aux)
                        </flux:badge>
                    </div>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/70">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Cree par</p>
                        <p class="mt-2 text-sm font-bold text-slate-900 dark:text-white">
                            {{ $groupe->user?->name ?: $groupe->user?->email ?: 'Non renseigne' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/70">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Creation</p>
                        <p class="mt-2 text-sm font-bold text-slate-900 dark:text-white">
                            {{ $groupe->created_at?->format('d/m/Y H:i') ?: '-' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/70">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Types</p>
                        <p class="mt-2 text-sm font-bold text-slate-900 dark:text-white">
                            {{ $this->typesSummary }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Etablissements</p>
                        <h2 class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                            Hopitaux du groupe concerne
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Tableau PowerGrid avec recherche, pagination et informations administratives.
                        </p>
                    </div>
                    <flux:badge color="sky" inset>{{ $groupe->hopitaux_count }} ligne(s)</flux:badge>
                </div>

                <livewire:groupe-hopital-hopital-table :groupe-id="$groupe->id" />
            </div>
        </div>

        <aside class="space-y-6">
            <div
                class="xl:sticky xl:top-6 space-y-6 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Synthese</p>
                    <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Indicateurs du groupe</h3>
                </div>

                <div class="grid gap-3">
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Identifiant</p>
                        <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">#{{ $groupe->id }}</p>
                    </div>
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                        <p class="text-xs text-blue-700 dark:text-blue-300">Hopitaux rattaches</p>
                        <p class="mt-2 text-2xl font-black text-blue-950 dark:text-blue-100">
                            {{ $groupe->hopitaux_count }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <p class="text-xs text-emerald-700 dark:text-emerald-300">Hopitaux actifs</p>
                        <p class="mt-2 text-2xl font-black text-emerald-950 dark:text-emerald-100">
                            {{ $this->activeHopitauxCount }}
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Note</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ $groupe->note ?: 'Aucune note de gestion n a ete renseignee.' }}
                    </p>
                </div>
            </div>
        </aside>
    </div>
</section>