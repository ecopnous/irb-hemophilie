<?php

use App\Models\Configs\GroupeExamen;
use App\Models\Configs\Service;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Nouveau groupe d\'examens'), Layout('layouts::app.other.laboratoire')] class extends Component {
    public string $name = '';
    public string $description = '';
    public ?int $service_id = null;
    public bool $is_active = true;
    public string $searchActe = '';
    public array $selectedActes = [];

    #[Computed]
    public function labServices(): Collection
    {
        return Service::query()
            ->whereHas('departement', fn ($query) => $query->where('ref', 'labo'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function actes()
    {
        return GroupeExamen::labActesQuery()
            ->with('service')
            ->when($this->service_id, fn ($query) => $query->where('service_id', $this->service_id))
            ->when($this->searchActe, function ($query) {
                $query->where(function ($inner) {
                    $inner
                        ->where('name', 'like', "%{$this->searchActe}%")
                        ->orWhereHas('service', fn ($service) => $service->where('name', 'like', "%{$this->searchActe}%"));
                });
            })
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($acte) => $acte->service?->name ?: 'Sans service');
    }

    #[Computed]
    public function selectedActesCollection(): Collection
    {
        if ($this->selectedActes === []) {
            return collect();
        }

        return GroupeExamen::labActesQuery()
            ->whereIn('id', $this->selectedActes)
            ->with('service')
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
        $this->service_id = $this->service_id ? (int) $this->service_id : null;
        $this->selectedActes = GroupeExamen::normalizeActeIds($this->selectedActes);

        $validated = $this->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'service_id' => ['nullable', 'integer', 'exists:services,id'],
                'is_active' => ['boolean'],
                ...GroupeExamen::acteSelectionRules(),
            ],
            [
                'selectedActes.min' => 'Sélectionnez au moins un examen de laboratoire.',
                'selectedActes.*.in' => 'Un examen sélectionné n\'appartient pas au laboratoire.',
            ],
        );

        $groupe = GroupeExamen::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'service_id' => $validated['service_id'],
            'is_active' => $validated['is_active'],
        ]);

        $groupe->actes()->sync(GroupeExamen::normalizeActeIds($validated['selectedActes']));

        Flux::toast(variant: 'success', heading: 'Groupe enregistré', text: 'Le groupe d\'examens a été créé avec succès.');

        $this->redirectRoute('laboratoire.groupes.show', ['id' => $groupe->id], navigate: true);
    }
};
?>

<div class="space-y-6 mx-auto max-w-7xl pb-28">
    <div class="space-y-2">
        <x-breadcrumbs :items="[
            ['label' => 'Laboratoire', 'link' => 'laboratoire.index', 'icon' => 'beaker'],
            ['label' => 'Groupes d\'examens', 'link' => 'laboratoire.groupes.index', 'icon' => 'rectangle-group'],
            ['label' => 'Nouveau groupe', 'icon' => 'plus'],
        ]" />
        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Nouveau groupe d'examens</h1>
    </div>

    @if ($errors->any())
        <div
            class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_360px]">
        <div class="space-y-6">
            <section
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Informations du groupe</h2>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <x-input wire:model="name" label="Nom du groupe *" placeholder="Ex: Bilan hépatique" />
                    <x-select.native wire:model.live="service_id" label="Service laboratoire"
                        :options="collect([['label' => 'Tous les services', 'value' => null]])
                            ->merge($this->labServices->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]))
                            ->values()
                            ->all()" />
                    <div class="md:col-span-2">
                        <x-textarea wire:model="description" label="Description" maxlength="1000" count
                            placeholder="Indication clinique, protocole, remarques..." />
                    </div>
                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                        <input type="checkbox" wire:model="is_active"
                            class="size-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                        Groupe actif (disponible à la prescription)
                    </label>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div
                    class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Examens inclus</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Uniquement les actes du département
                            laboratoire.</p>
                    </div>
                    <div class="w-full md:max-w-sm">
                        <x-input wire:model.live.debounce.300ms="searchActe" icon="magnifying-glass"
                            placeholder="Rechercher un examen..." />
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    @forelse ($this->actes as $serviceName => $actes)
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                            <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">{{ $serviceName }}
                                <span class="text-slate-400">({{ $actes->count() }})</span></p>
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($actes as $acte)
                                    <label wire:key="groupe-acte-{{ $acte->id }}"
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 transition hover:border-sky-300 dark:border-slate-700 dark:bg-slate-900">
                                        <input type="checkbox" value="{{ $acte->id }}" wire:model="selectedActes"
                                            class="mt-1 size-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex justify-between gap-2">
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                                    {{ $acte->name }}</p>
                                                <span
                                                    class="whitespace-nowrap text-xs font-bold text-sky-700 dark:text-sky-300">
                                                    {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">Aucun examen de laboratoire trouvé.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-4">
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Aperçu</p>
                <p class="mt-3 text-xl font-black text-slate-900 dark:text-white">{{ $name ?: 'Nom du groupe' }}</p>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900">
                        <p class="text-xs text-slate-500">Examens</p>
                        <p class="text-2xl font-black">{{ count($selectedActes) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900">
                        <p class="text-xs text-slate-500">Montant</p>
                        <p class="text-lg font-black">{{ number_format($this->totalMontant, 2, ',', ' ') }} $</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <flux:button type="submit" variant="primary" color="sky" icon="check" class="w-full justify-center">
                    Enregistrer le groupe
                </flux:button>
                <flux:button href="{{ route('laboratoire.groupes.index') }}" wire:navigate variant="subtle"
                    class="w-full justify-center">
                    Annuler
                </flux:button>
            </div>
        </aside>
    </form>
</div>
