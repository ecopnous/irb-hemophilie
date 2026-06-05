<?php

use App\Models\Configs\PacquetSoin;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Fiche paquet de soins')] class extends Component
{
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
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Fiche paquet de soins')" :subheading="__('Vision detaillee du paquet, de sa categorisation et des actes inclus')">
        <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-3">
                <x-button href="{{ route('settings.paquet.index') }}" wire:navigate>Retour a la liste</x-button>
                <flux:badge :color="$paquet->paiement_directe ? 'emerald' : 'amber'" inset>
                    {{ $paquet->paiement_directe ? 'Paiement direct' : 'Paiement differe' }}
                </flux:badge>
            </div>
            <x-button icon="squares-plus" position="left" href="{{ route('settings.paquet.create') }}" wire:navigate>
                Nouveau paquet
            </x-button>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_360px]">
            <div class="space-y-6">
                <div class="rounded-3xl border border-zinc-200 bg-white/95 p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Paquet de soins</p>
                            <h2 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $paquet->name }}</h2>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                {{ $paquet->description ?: 'Aucune description n a encore ete renseignee pour ce paquet.' }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800/70">
                            <p class="text-zinc-500 dark:text-zinc-400">Categorisation</p>
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $paquet->categorisation?->name ?: 'Non classe' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-white/95 p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <flux:heading size="lg">Actes inclus</flux:heading>
                            <flux:subheading class="mt-1">Liste des actes medicaux rattaches a ce paquet de soins.</flux:subheading>
                        </div>
                        <flux:badge color="sky" inset>{{ $paquet->actes->count() }} acte(s)</flux:badge>
                    </div>

                    <div class="space-y-3">
                        @forelse ($paquet->actes->sortBy('name') as $acte)
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p class="font-semibold text-zinc-900 dark:text-white">{{ $acte->name }}</p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            Departement: {{ $acte->departement?->name ?: 'Non defini' }}
                                            • Service: {{ $acte->service?->name ?: 'Non defini' }}
                                        </p>
                                    </div>
                                    <p class="text-sm font-semibold text-sky-700 dark:text-sky-300">
                                        {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                Aucun acte n'est encore associe a ce paquet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-3xl border border-zinc-200 bg-white/95 p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
                    <flux:heading size="lg">Synthese</flux:heading>
                    <div class="mt-5 grid gap-3">
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Identifiant</p>
                            <p class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">#{{ $paquet->id }}</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Montant cumule</p>
                            <p class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">{{ number_format($this->montantTotal, 2, ',', ' ') }} $</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Date de creation</p>
                            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">{{ $paquet->created_at?->format('d/m/Y H:i') ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-6 shadow-sm dark:border-emerald-900/70 dark:bg-emerald-950/30">
                    <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">Regle de facturation</p>
                    <p class="mt-3 text-sm leading-6 text-emerald-900/80 dark:text-emerald-100/80">
                        {{ $paquet->paiement_directe
                            ? 'Ce paquet est configure pour un paiement direct. Il peut etre propose comme offre predefinie au moment de la facturation.'
                            : 'Ce paquet est configure pour une prise en charge differee ou indirecte. Verifiez la couverture liee a la categorisation avant facturation.' }}
                    </p>
                </div>
            </aside>
        </div>
    </x-pages::settings.layout>
</section>
