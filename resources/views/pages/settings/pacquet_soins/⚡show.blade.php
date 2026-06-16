<?php

use App\Models\Configs\PacquetSoin;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fiche paquet de soins'), Layout('layouts::app.other.support_tech')] class extends Component {
    public PacquetSoin $paquet;

    public function mount(int $id): void
    {
        $this->paquet = PacquetSoin::query()
            ->with(['categorisation', 'actes.departement', 'actes.service'])
            ->findOrFail($id);
    }

    #[Computed]
    public function montantTotal(): float
    {
        return (float) $this->paquet->actes->sum('montant');
    }
}; ?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Fiche paquet de soins</flux:heading>

    <x-header_default
        :title="$paquet->name"
        :subtitle="'Categorisation : ' . ($paquet->categorisation?->name ?: 'Non classe')"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings.hopital.index', 'icon' => 'cog-6-tooth'],
            ['label' => 'Paquets de soins', 'link' => 'settings.paquet.index', 'icon' => 'briefcase'],
            ['label' => $paquet->name, 'icon' => 'document-text'],
        ]"
    >
        <x-slot:actions>
            <flux:button href="{{ route('settings.paquet.index') }}" variant="ghost" icon="arrow-left" wire:navigate>
                Retour
            </flux:button>
            <x-button icon="squares-plus" position="left" href="{{ route('settings.paquet.create') }}" wire:navigate>
                Nouveau paquet
            </x-button>
        </x-slot:actions>
    </x-header_default>

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="bg-linear-to-r from-violet-600 via-indigo-600 to-sky-500 px-6 py-7 sm:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.22em] text-white/70">Paquet de soins</p>
                    <h1 class="mt-1 text-2xl font-black text-white sm:text-3xl">{{ $paquet->name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm text-white/85">
                        {{ $paquet->description ?: 'Aucune description renseignee pour ce paquet.' }}
                    </p>
                </div>
                <flux:badge :color="$paquet->paiement_directe ? 'lime' : 'amber'" class="!bg-white/15 !text-white">
                    {{ $paquet->paiement_directe ? 'Paiement direct' : 'Paiement differe' }}
                </flux:badge>
            </div>
        </div>

        <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-4 dark:bg-slate-800">
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Categorisation</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $paquet->categorisation?->name ?: '—' }}</p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Actes inclus</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $paquet->actes->count() }}</p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Montant cumule</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ number_format($this->montantTotal, 2, ',', ' ') }} $</p>
            </div>
            <div class="bg-white px-5 py-4 dark:bg-slate-950/70">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Cree le</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $paquet->created_at?->format('d/m/Y') ?: '—' }}</p>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
            <div class="mb-5 flex items-center justify-between gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                        <flux:icon.clipboard-document-list class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Actes inclus</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Liste des actes medicaux rattaches.</p>
                    </div>
                </div>
                <flux:badge color="sky" inset>{{ $paquet->actes->count() }}</flux:badge>
            </div>

            <div class="space-y-3">
                @forelse ($paquet->actes->sortBy('name') as $acte)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $acte->name }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $acte->departement?->name ?: 'Departement non defini' }}
                                    · {{ $acte->service?->name ?: 'Service non defini' }}
                                </p>
                            </div>
                            <p class="text-sm font-bold text-sky-700 dark:text-sky-300">
                                {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        Aucun acte n'est associe a ce paquet.
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Synthese</h2>
                <div class="mt-4 space-y-3">
                    <x-patient.fiche-field label="Identifiant" :value="'#' . $paquet->id" />
                    <x-patient.fiche-field label="Montant total" :value="number_format($this->montantTotal, 2, ',', ' ') . ' $'" />
                    <x-patient.fiche-field label="Mode de paiement" :value="$paquet->paiement_directe ? 'Direct' : 'Differe'" />
                </div>
            </section>

            <section @class([
                'rounded-[1.75rem] border p-5 shadow-sm sm:p-6',
                'border-emerald-200 bg-emerald-50/80 dark:border-emerald-500/20 dark:bg-emerald-500/10' => $paquet->paiement_directe,
                'border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-500/10' => ! $paquet->paiement_directe,
            ])>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Regle de facturation</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ $paquet->paiement_directe
                        ? 'Ce paquet est configure pour un paiement direct du patient lors de la facturation.'
                        : 'Ce paquet est configure pour une prise en charge differee. Verifiez la categorisation avant facturation.' }}
                </p>
            </section>
        </aside>
    </div>
</section>
