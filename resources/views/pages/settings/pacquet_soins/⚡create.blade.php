<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Categorisation;
use App\Models\Configs\PacquetSoin;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;
use TallStackUi\Traits\Interactions;

new #[Title('Nouveau paquet de soins')] class extends Component {
    use Interactions;

    public string $name = '';
    public string $description = '';
    public bool $paiement_directe = false;
    public ?int $categorisation_id = null;
    public string $searchActe = '';
    public array $selectedActes = [];
    public Collection $categories;

    public function mount(): void
    {
        $this->categories = Categorisation::query()->orderBy('name')->get();
    }

    #[Computed]
    public function actes()
    {
        return Acte::query()
            ->with(['departement', 'service'])
            ->when($this->searchActe, function ($query) {
                $query->where(function ($inner) {
                    $inner
                        ->where('name', 'like', "%{$this->searchActe}%")
                        ->orWhereHas('departement', function ($departement) {
                            $departement->where('name', 'like', "%{$this->searchActe}%");
                        })
                        ->orWhereHas('service', function ($service) {
                            $service->where('name', 'like', "%{$this->searchActe}%");
                        });
                });
            })
            ->orderBy('name')
            ->get()
            ->groupBy(fn(Acte $acte) => $acte->departement?->name ?: 'Sans departement');
    }

    #[Computed]
    public function selectedActesCollection(): Collection
    {
        if ($this->selectedActes === []) {
            return collect();
        }

        return Acte::query()
            ->whereIn('id', $this->selectedActes)
            ->with(['departement', 'service'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function totalMontant(): float
    {
        return (float) $this->selectedActesCollection->sum('montant');
    }

    public function save(): void
    {
        try {
            $validated = $this->validate(
                [
                    'name' => ['required', 'string', 'max:255'],
                    'description' => ['nullable', 'string', 'max:1000'],
                    'paiement_directe' => ['boolean'],
                    'categorisation_id' => ['nullable', 'integer', 'exists:categorisations,id'],
                    'selectedActes' => ['required', 'array', 'min:1'],
                    'selectedActes.*' => ['integer', 'exists:actes,id'],
                ],
                [
                    'selectedActes.min' => 'Selectionnez au moins un acte medical.',
                ],
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dialog()->error('Formulaire incomplet', 'Veuillez renseigner les champs obligatoires du paquet.')->send();
            throw $e;
        }

        $paquet = PacquetSoin::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'paiement_directe' => $validated['paiement_directe'],
            'categorisation_id' => $validated['categorisation_id'],
        ]);

        $paquet->actes()->sync($validated['selectedActes']);

        Flux::toast(variant: 'success', heading: 'Paquet enregistre', text: 'Le paquet de soins a ete cree avec succes.');

        $this->redirectRoute('settings.paquet.show', ['id' => $paquet->id], navigate: true);
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Nouveau paquet de soins')" :subheading="__('Composer un paquet a partir d\'actes medicaux existants')">
        <form wire:submit.prevent="save" class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_380px]">
            <div class="space-y-6">
                <div
                    class="rounded-3xl border border-zinc-200 bg-white/95 p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="lg">Informations du paquet</flux:heading>
                            <flux:subheading class="mt-1">Les informations principales visibles dans la fiche
                                detaillee.</flux:subheading>
                        </div>
                        <flux:badge color="sky" inset>Configuration</flux:badge>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="name" label="Nom du paquet *" clearable
                            placeholder="Ex: Paquet chirurgie mineure" />
                        <x-select.native wire:model="categorisation_id" label="Categorisation" :options="$categories
                            ->map(fn($categorie) => ['label' => $categorie->name, 'value' => $categorie->id])
                            ->values()
                            ->all()" />
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-[1fr_auto] md:items-start">
                        <x-textarea wire:model="description" label="Description" maxlength="1000" count
                            placeholder="Resume clinique, objectifs, conditions d'utilisation..." />
                        <label
                            class="flex min-h-14 items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800/70 dark:text-zinc-100">
                            <input type="checkbox" wire:model="paiement_directe"
                                class="h-4 w-4 rounded border-zinc-300 text-sky-600 focus:ring-sky-500" />
                            Paiement direct
                        </label>
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-zinc-200 bg-white/95 p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
                    <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <flux:heading size="lg">Actes medicaux inclus</flux:heading>
                            <flux:subheading class="mt-1">Un acte peut appartenir a plusieurs paquets. Selection
                                multiple autorisee.</flux:subheading>
                        </div>

                        <div class="w-full md:max-w-sm">
                            <x-input wire:model.live.debounce.300ms="searchActe" icon="magnifying-glass"
                                placeholder="Rechercher un acte, un departement..." />
                        </div>
                    </div>

                    <div class="space-y-5">
                        @forelse ($this->actes as $departement => $actes)
                            <div
                                class="rounded-2xl border border-zinc-200/80 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                            {{ $departement }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $actes->count() }}
                                            acte(s) disponible(s)</p>
                                    </div>
                                    <flux:badge color="sky" inset>{{ $actes->count() }}</flux:badge>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    @foreach ($actes as $acte)
                                        <label wire:key="acte-option-{{ $acte->id }}"
                                            class="flex cursor-pointer items-start gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50/60 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-sky-700 dark:hover:bg-sky-950/30">
                                            <input type="checkbox" value="{{ $acte->id }}"
                                                wire:model="selectedActes"
                                                class="mt-1 h-4 w-4 rounded border-zinc-300 text-sky-600 focus:ring-sky-500" />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3">
                                                    <p class="font-medium text-zinc-900 dark:text-white">
                                                        {{ $acte->name }}</p>
                                                    <span
                                                        class="whitespace-nowrap text-sm font-semibold text-sky-700 dark:text-sky-300">
                                                        {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                    Service: {{ $acte->service?->name ?: 'Non defini' }}
                                                </p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-2xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                Aucun acte medical ne correspond a votre recherche.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div
                    class="rounded-3xl border border-zinc-200 bg-white/95 p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
                    <flux:heading size="lg">Apercu</flux:heading>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Nom</p>
                            <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $name ?: 'Nom du paquet' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Actes selectionnes</p>
                                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                                    {{ count($selectedActes) }}</p>
                            </div>
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Montant cumule</p>
                                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                                    {{ number_format($this->totalMontant, 2, ',', ' ') }} $</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Mode de paiement</p>
                            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $paiement_directe ? 'Paiement direct du patient' : 'Paiement differe / prise en charge' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-sky-200 bg-sky-50/80 p-6 shadow-sm dark:border-sky-900/70 dark:bg-sky-950/30">
                    <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Verification avant enregistrement
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-sky-900/80 dark:text-sky-100/80">
                        <li>{{ $categorisation_id ? 'Categorisation selectionnee' : 'Categorisation manquante' }}</li>
                        <li>{{ count($selectedActes) > 0 ? 'Actes rattaches au paquet' : 'Aucun acte selectionne' }}
                        </li>
                        <li>{{ $name !== '' ? 'Nom renseigne' : 'Nom obligatoire' }}</li>
                    </ul>
                </div>

                <div class="flex flex-col gap-3">
                    <flux:button type="submit" variant="primary" color="sky" icon="save"
                        class="w-full justify-center">
                        Enregistrer le paquet
                    </flux:button>
                    <x-button href="{{ route('settings.paquet.index') }}" wire:navigate class="justify-center">
                        Retour a la liste
                    </x-button>
                </div>
            </aside>
        </form>
    </x-pages::settings.layout>
</section>
