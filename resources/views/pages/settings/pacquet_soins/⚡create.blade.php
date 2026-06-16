<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Categorisation;
use App\Models\Configs\PacquetSoin;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Nouveau paquet de soins'), Layout('layouts::app.other.support_tech')] class extends Component {
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
            ->groupBy(fn (Acte $acte) => $acte->departement?->name ?: 'Sans departement');
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
}; ?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Nouveau paquet de soins</flux:heading>

    <x-header_default
        title="Nouveau paquet de soins"
        subtitle="Composer une offre a partir d'actes medicaux existants"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings.hopital.index', 'icon' => 'cog-6-tooth'],
            ['label' => 'Paquets de soins', 'link' => 'settings.paquet.index', 'icon' => 'briefcase'],
            ['label' => 'Nouveau', 'icon' => 'plus-circle'],
        ]"
    >
        <x-slot:actions>
            <flux:button href="{{ route('settings.paquet.index') }}" variant="ghost" icon="arrow-left" wire:navigate>
                Retour
            </flux:button>
        </x-slot:actions>
    </x-header_default>

    <form wire:submit.prevent="save" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
                <div class="mb-5 border-b border-slate-100 pb-4 dark:border-slate-800">
                    <h2 class="text-base font-black text-slate-900 dark:text-white">Informations du paquet</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Donnees visibles dans la fiche detaillee.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-input wire:model="name" label="Nom du paquet *" clearable placeholder="Ex: Paquet chirurgie mineure" />
                    <x-select.native wire:model="categorisation_id" label="Categorisation" :options="$categories
                        ->map(fn ($categorie) => ['label' => $categorie->name, 'value' => $categorie->id])
                        ->values()
                        ->all()" />
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-[1fr_auto] md:items-start">
                    <x-textarea wire:model="description" label="Description" maxlength="1000" count
                        placeholder="Resume clinique, objectifs, conditions d'utilisation..." />
                    <label
                        class="flex min-h-14 items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-100">
                        <input type="checkbox" wire:model="paiement_directe"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        Paiement direct
                    </label>
                </div>
            </section>

            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
                <div class="mb-5 flex flex-col gap-4 border-b border-slate-100 pb-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Actes medicaux</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Selection multiple autorisee.</p>
                    </div>
                    <div class="w-full md:max-w-sm">
                        <x-input wire:model.live.debounce.300ms="searchActe" icon="magnifying-glass"
                            placeholder="Rechercher un acte..." />
                    </div>
                </div>

                <div class="space-y-5">
                    @forelse ($this->actes as $departement => $actes)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $departement }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $actes->count() }} acte(s)</p>
                                </div>
                                <flux:badge color="sky" inset>{{ $actes->count() }}</flux:badge>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($actes as $acte)
                                    <label wire:key="acte-option-{{ $acte->id }}"
                                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:border-indigo-300 hover:bg-indigo-50/60 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30">
                                        <input type="checkbox" value="{{ $acte->id }}" wire:model="selectedActes"
                                            class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <p class="font-medium text-slate-900 dark:text-white">{{ $acte->name }}</p>
                                                <span class="whitespace-nowrap text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                                                    {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                                                </span>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                {{ $acte->service?->name ?: 'Service non defini' }}
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Aucun acte ne correspond a votre recherche.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start">
            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:p-6">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Apercu</h2>
                <div class="mt-4 space-y-3">
                    <x-patient.fiche-field label="Nom" :value="$name ?: '—'" />
                    <x-patient.fiche-field label="Actes selectionnes" :value="(string) count($selectedActes)" />
                    <x-patient.fiche-field label="Montant cumule" :value="number_format($this->totalMontant, 2, ',', ' ') . ' $'" />
                    <x-patient.fiche-field label="Paiement" :value="$paiement_directe ? 'Direct' : 'Differe'" />
                </div>
            </section>

            <section class="rounded-[1.75rem] border border-indigo-200 bg-indigo-50/80 p-5 shadow-sm dark:border-indigo-500/20 dark:bg-indigo-500/10 sm:p-6">
                <h2 class="text-sm font-black text-indigo-900 dark:text-indigo-100">Verification</h2>
                <ul class="mt-3 space-y-2 text-sm text-indigo-900/80 dark:text-indigo-100/80">
                    <li>{{ $name !== '' ? '✓ Nom renseigne' : '○ Nom obligatoire' }}</li>
                    <li>{{ count($selectedActes) > 0 ? '✓ Actes selectionnes' : '○ Aucun acte selectionne' }}</li>
                    <li>{{ $categorisation_id ? '✓ Categorisation choisie' : '○ Categorisation optionnelle' }}</li>
                </ul>
            </section>

            <div class="flex flex-col gap-3">
                <flux:button type="submit" variant="primary" color="indigo" icon="save" class="w-full justify-center">
                    Enregistrer le paquet
                </flux:button>
                <flux:button href="{{ route('settings.paquet.index') }}" variant="ghost" class="w-full justify-center" wire:navigate>
                    Annuler
                </flux:button>
            </div>
        </aside>
    </form>
</section>
