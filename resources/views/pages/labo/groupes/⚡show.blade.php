<?php

use App\Models\Configs\GroupeExamen;
use App\Models\Configs\Service;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Groupe d\'examens'), Layout('layouts::app.other.laboratoire')] class extends Component {
    public GroupeExamen $groupe;

    public string $name = '';
    public string $description = '';
    public ?int $service_id = null;
    public bool $is_active = true;
    public string $searchActe = '';
    public array $selectedActes = [];
    public bool $editing = false;

    public function mount(int $id): void
    {
        $this->loadGroupe($id);
    }

    protected function loadGroupe(int $id): void
    {
        $this->groupe = GroupeExamen::query()
            ->with(['service', 'actes.service'])
            ->findOrFail($id);

        $this->name = (string) $this->groupe->name;
        $this->description = (string) ($this->groupe->description ?? '');
        $this->service_id = $this->groupe->service_id;
        $this->is_active = (bool) $this->groupe->is_active;
        $this->selectedActes = $this->groupe->actes->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

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
    public function montantTotal(): float
    {
        return (float) $this->groupe->actes->sum('montant');
    }

    public function startEditing(): void
    {
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->loadGroupe($this->groupe->id);
        $this->editing = false;
        $this->searchActe = '';
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

        $this->groupe->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'service_id' => $validated['service_id'],
            'is_active' => $validated['is_active'],
        ]);

        $this->groupe->actes()->sync(GroupeExamen::normalizeActeIds($validated['selectedActes']));
        $this->groupe->load(['service', 'actes.service']);
        $this->editing = false;
        $this->searchActe = '';

        Flux::toast(variant: 'success', heading: 'Groupe mis à jour', text: 'Les modifications ont été enregistrées.');
    }

    public function deleteGroupe(): void
    {
        $this->groupe->delete();

        Flux::toast(variant: 'success', heading: 'Groupe supprimé', text: 'Le groupe d\'examens a été retiré.');

        $this->redirectRoute('laboratoire.groupes.index', navigate: true);
    }
};
?>

<div class="space-y-6 mx-auto max-w-7xl pb-28">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-2">
            <x-breadcrumbs :items="[
                ['label' => 'Laboratoire', 'link' => 'laboratoire.index', 'icon' => 'beaker'],
                ['label' => 'Groupes d\'examens', 'link' => 'laboratoire.groupes.index', 'icon' => 'rectangle-group'],
                ['label' => $groupe->name, 'icon' => 'document'],
            ]" />
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">{{ $groupe->name }}</h1>
                <span @class([
                    'rounded-full px-3 py-1 text-xs font-bold',
                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' => $groupe->is_active,
                    'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => ! $groupe->is_active,
                ])>
                    {{ $groupe->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if (! $editing)
                <flux:button wire:click="startEditing" variant="primary" color="sky" icon="pencil">
                    Modifier
                </flux:button>
            @endif
            <flux:button href="{{ route('laboratoire.groupes.index') }}" wire:navigate variant="subtle">
                Retour
            </flux:button>
        </div>
    </div>

    @if ($editing)
        <form wire:submit.prevent="save" class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_360px]">
            <div class="space-y-6">
                <section
                    class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Modifier le groupe</h2>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-2">
                        <x-input wire:model="name" label="Nom *" />
                        <x-select.native wire:model.live="service_id" label="Service"
                            :options="collect([['label' => 'Tous les services', 'value' => null]])
                                ->merge($this->labServices->map(fn ($s) => ['label' => $s->name, 'value' => $s->id]))
                                ->values()
                                ->all()" />
                        <div class="md:col-span-2">
                            <x-textarea wire:model="description" label="Description" maxlength="1000" count />
                        </div>
                        <label class="flex items-center gap-3 text-sm font-medium">
                            <input type="checkbox" wire:model="is_active" class="size-4 rounded text-sky-600" />
                            Groupe actif
                        </label>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <x-input wire:model.live.debounce.300ms="searchActe" icon="magnifying-glass"
                            placeholder="Rechercher un examen..." />
                    </div>
                    <div class="max-h-[28rem] space-y-4 overflow-y-auto p-5">
                        @foreach ($this->actes as $serviceName => $actes)
                            <div wire:key="edit-service-{{ md5($serviceName) }}">
                                <p class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $serviceName }}</p>
                                <div class="grid gap-2 md:grid-cols-2">
                                    @foreach ($actes as $acte)
                                        <label wire:key="edit-acte-{{ $acte->id }}"
                                            class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                                            <input type="checkbox" value="{{ $acte->id }}"
                                                wire:model="selectedActes" class="mt-0.5 size-4" />
                                            <span class="text-sm">{{ $acte->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="space-y-3">
                <flux:button type="submit" variant="primary" color="sky" icon="check" class="w-full justify-center">
                    Enregistrer
                </flux:button>
                <flux:button type="button" wire:click="cancelEditing" variant="subtle" class="w-full justify-center">
                    Annuler
                </flux:button>
                <flux:button type="button" wire:click="deleteGroupe" wire:confirm="Supprimer ce groupe d'examens ?"
                    variant="danger" class="w-full justify-center">
                    Supprimer
                </flux:button>
            </aside>
        </form>
    @else
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_320px]">
            <section
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Examens du groupe</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $groupe->actes->count() }} examen(s)</p>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($groupe->actes->sortBy('name') as $acte)
                        <div class="flex items-center justify-between gap-4 px-5 py-4" wire:key="show-acte-{{ $acte->id }}">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $acte->name }}</p>
                                <p class="text-xs text-slate-500">{{ $acte->service?->name ?: 'Sans service' }}</p>
                            </div>
                            <span class="text-sm font-bold text-sky-700 dark:text-sky-300">
                                {{ number_format((float) $acte->montant, 2, ',', ' ') }} $
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-slate-500">Aucun examen associé.</p>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-4">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Synthèse</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Service</dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">
                                {{ $groupe->service?->name ?: 'Tous services' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Montant cumulé</dt>
                            <dd class="text-xl font-black text-slate-900 dark:text-white">
                                {{ number_format($this->montantTotal, 2, ',', ' ') }} $</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Créé le</dt>
                            <dd class="font-medium">{{ $groupe->created_at?->format('d/m/Y H:i') ?: '-' }}</dd>
                        </div>
                    </dl>
                    @if ($groupe->description)
                        <p class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-300">
                            {{ $groupe->description }}
                        </p>
                    @endif
                </div>
            </aside>
        </div>
    @endif
</div>
