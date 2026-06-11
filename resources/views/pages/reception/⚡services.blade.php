<?php

use App\Models\ReceptionBaseSupply;
use App\Services\ReceptionCatalogService;
use App\Services\ReceptionBaseSupplyStockService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Service de base reception'), Layout('layouts::app.other.reception')] class extends Component {
    public bool $showFormModal = false;

    public bool $showViewModal = false;

    public bool $showMovementModal = false;

    public ?int $editingId = null;

    public ?int $viewingId = null;

    public string $formDesignation = '';

    public string $formReference = '';

    public string $formCategory = 'nettoyage';

    public string $formUnit = 'pce';

    public int $formPlannedStock = 0;

    public int $formCurrentStock = 0;

    public int $formStockMin = 0;

    public ?string $formNotes = null;

    public bool $formIsActive = true;

    public string $movementType = 'entree';

    public int $movementQuantity = 1;

    public ?string $movementReference = null;

    public ?string $movementReason = null;

    public function mount(): void
    {
        abort_unless(current_hopital_id(), 403, 'Selectionnez un hopital pour gerer le service de base.');
    }

    protected function baseQuery()
    {
        return ReceptionBaseSupply::query()->whereHopitalId(current_hopital_id());
    }

    #[Computed]
    public function stats(): array
    {
        $items = $this->baseQuery()->get();

        return [
            'total' => $items->count(),
            'active' => $items->where('is_active', true)->count(),
            'low_stock' => $items->filter(fn ($item) => $item->isLowStock())->count(),
            'gap_total' => $items->sum(fn ($item) => $item->stockGap()),
        ];
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return collect(app(ReceptionCatalogService::class)->baseSupplyCategoryLabels())
            ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
            ->values()
            ->all();
    }

    #[Computed]
    public function viewedSupply(): ?ReceptionBaseSupply
    {
        if (! $this->viewingId) {
            return null;
        }

        return $this->baseQuery()
            ->with(['updatedBy', 'movements.createdBy'])
            ->find($this->viewingId);
    }

    public function importCatalog(): void
    {
        $created = app(ReceptionCatalogService::class)->seedBaseSuppliesForHopital((int) current_hopital_id());
        $this->dispatch('pg:eventRefresh-receptionBaseSupplyTable');
        unset($this->stats);

        Flux::toast(
            heading: 'Catalogue importe',
            text: $created > 0 ? "{$created} articles ajoutes." : 'Le catalogue est deja a jour.',
            variant: 'success',
        );
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    #[On('base-supply-edit')]
    public function openEdit(int $id): void
    {
        $this->showViewModal = false;
        $item = $this->baseQuery()->findOrFail($id);
        $this->editingId = $item->id;
        $this->formDesignation = $item->designation;
        $this->formReference = (string) ($item->reference ?? '');
        $this->formCategory = $item->category;
        $this->formUnit = $item->unit;
        $this->formPlannedStock = (int) $item->planned_stock;
        $this->formCurrentStock = (int) $item->current_stock;
        $this->formStockMin = (int) $item->stock_min;
        $this->formNotes = $item->notes;
        $this->formIsActive = (bool) $item->is_active;
        $this->showFormModal = true;
    }

    #[On('base-supply-view')]
    public function openView(int $id): void
    {
        $this->viewingId = $id;
        $this->showViewModal = true;
        unset($this->viewedSupply);
    }

    public function openMovement(int $id): void
    {
        $this->viewingId = $id;
        $this->movementType = 'entree';
        $this->movementQuantity = 1;
        $this->movementReference = null;
        $this->movementReason = null;
        $this->showMovementModal = true;
        unset($this->viewedSupply);
    }

    public function cancelForm(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->formDesignation = '';
        $this->formReference = '';
        $this->formCategory = 'nettoyage';
        $this->formUnit = 'pce';
        $this->formPlannedStock = 0;
        $this->formCurrentStock = 0;
        $this->formStockMin = 0;
        $this->formNotes = null;
        $this->formIsActive = true;
        $this->resetValidation();
    }

    public function saveSupply(): void
    {
        $hopitalId = (int) current_hopital_id();

        $validated = $this->validate([
            'formDesignation' => [
                'required', 'string', 'max:255',
                Rule::unique('reception_base_supplies', 'designation')
                    ->where(fn ($q) => $q->where('hopital_id', $hopitalId))
                    ->ignore($this->editingId),
            ],
            'formReference' => ['nullable', 'string', 'max:50'],
            'formCategory' => ['required', 'in:nettoyage,hygiene,entretien,consommable,autre'],
            'formUnit' => ['required', 'string', 'max:60'],
            'formPlannedStock' => ['required', 'integer', 'min:0'],
            'formCurrentStock' => ['required', 'integer', 'min:0'],
            'formStockMin' => ['required', 'integer', 'min:0'],
            'formNotes' => ['nullable', 'string', 'max:1000'],
            'formIsActive' => ['boolean'],
        ]);

        $payload = [
            'designation' => trim($validated['formDesignation']),
            'reference' => filled($validated['formReference']) ? trim($validated['formReference']) : null,
            'category' => $validated['formCategory'],
            'unit' => trim($validated['formUnit']),
            'planned_stock' => $validated['formPlannedStock'],
            'current_stock' => $validated['formCurrentStock'],
            'stock_min' => $validated['formStockMin'],
            'notes' => $validated['formNotes'],
            'is_active' => $validated['formIsActive'],
            'updated_by' => Auth::id(),
        ];

        if ($this->editingId) {
            $this->baseQuery()->findOrFail($this->editingId)->update($payload);
            Flux::toast('Article mis a jour.', variant: 'success');
        } else {
            ReceptionBaseSupply::query()->create($payload + ['hopital_id' => $hopitalId]);
            Flux::toast('Article ajoute au catalogue.', variant: 'success');
        }

        $this->dispatch('pg:eventRefresh-receptionBaseSupplyTable');
        $this->cancelForm();
        unset($this->stats);
    }

    public function saveMovement(): void
    {
        $validated = $this->validate([
            'viewingId' => ['required', 'integer'],
            'movementType' => ['required', 'in:entree,sortie,ajustement'],
            'movementQuantity' => ['required', 'integer', 'min:1'],
            'movementReference' => ['nullable', 'string', 'max:100'],
            'movementReason' => ['nullable', 'string', 'max:500'],
        ]);

        $supply = $this->baseQuery()->findOrFail($validated['viewingId']);

        try {
            app(ReceptionBaseSupplyStockService::class)->applyMovement(
                $supply,
                $validated['movementType'],
                $validated['movementQuantity'],
                $validated['movementReference'],
                $validated['movementReason'],
            );
        } catch (\InvalidArgumentException $e) {
            Flux::toast($e->getMessage(), variant: 'danger');

            return;
        }

        $this->showMovementModal = false;
        $this->dispatch('pg:eventRefresh-receptionBaseSupplyTable');
        unset($this->stats, $this->viewedSupply);

        Flux::toast('Mouvement de stock enregistre.', variant: 'success');
    }
};
?>

<section class="w-full space-y-6 p-4 md:p-6">
    <flux:heading class="sr-only">Service de base reception</flux:heading>

    <x-header_default
        title="Service de base"
        subtitle="Equipements menagers et produits d entretien : stocks, mouvements et alertes"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Reception', 'link' => 'dashboard', 'icon' => 'building-office-2'],
            ['label' => 'Service de base', 'icon' => 'sparkles'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="importCatalog">Importer catalogue menager</x-button>
            <x-button icon="plus" position="left" wire:click="openCreate">Nouveau produit</x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Produits</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['total'] }}</p>
        </div>
        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Actifs</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['active'] }}</p>
        </div>
        <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">Stock bas</p>
            <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">{{ $this->stats['low_stock'] }}</p>
        </div>
        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Manquants</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['gap_total'] }}</p>
            <p class="mt-1 text-xs text-violet-700/80">Ecart stock prevu / reel</p>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 md:p-6">
        <livewire:reception-base-supply-table />
    </div>

    <flux:modal wire:model.self="showFormModal" class="max-w-2xl">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le produit' : 'Nouveau produit' }}</flux:heading>
                <flux:subheading>Produits menagers, hygiene et entretien des locaux.</flux:subheading>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold">Designation *</label>
                    <input type="text" wire:model="formDesignation" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @error('formDesignation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Reference</label>
                    <input type="text" wire:model="formReference" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Categorie *</label>
                    <select wire:model="formCategory" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach ($this->categoryOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Unite *</label>
                    <input type="text" wire:model="formUnit" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Stock prevu</label>
                    <input type="number" min="0" wire:model="formPlannedStock" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">En stock</label>
                    <input type="number" min="0" wire:model="formCurrentStock" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Seuil alerte</label>
                    <input type="number" min="0" wire:model="formStockMin" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold">Notes</label>
                    <textarea wire:model="formNotes" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 text-sm font-semibold">
                        <input type="checkbox" wire:model="formIsActive" class="rounded border-slate-300" />
                        Produit actif
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelForm">Annuler</flux:button>
                <flux:button variant="primary" wire:click="saveSupply">Enregistrer</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model.self="showViewModal" class="max-w-3xl">
        @if ($this->viewedSupply)
            @php $item = $this->viewedSupply; @endphp
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $item->designation }}</flux:heading>
                    <flux:subheading>{{ $item->reference ?: 'Sans reference' }} · {{ $item->unit }}</flux:subheading>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <p class="text-xs uppercase tracking-widest text-slate-400">Stock prevu</p>
                        <p class="mt-2 text-2xl font-black">{{ $item->planned_stock }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <p class="text-xs uppercase tracking-widest text-slate-400">En stock</p>
                        <p class="mt-2 text-2xl font-black {{ $item->isLowStock() ? 'text-amber-600' : 'text-emerald-600' }}">{{ $item->current_stock }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <p class="text-xs uppercase tracking-widest text-slate-400">Manquant</p>
                        <p class="mt-2 text-2xl font-black">{{ $item->stockGap() }}</p>
                    </div>
                </div>
                @if ($item->notes)
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $item->notes }}</p>
                @endif
                <div>
                    <p class="mb-3 text-xs font-black uppercase tracking-[0.18em] text-slate-400">Derniers mouvements</p>
                    <div class="max-h-56 space-y-2 overflow-y-auto">
                        @forelse ($item->movements->take(10) as $movement)
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-sm dark:border-slate-800">
                                <span class="capitalize text-slate-600 dark:text-slate-300">{{ $movement->movement_type }} · {{ $movement->quantity }}</span>
                                <span class="font-semibold">{{ $movement->quantity_before }} → {{ $movement->quantity_after }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Aucun mouvement enregistre.</p>
                        @endforelse
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showViewModal', false)">Fermer</flux:button>
                    <flux:button wire:click="openMovement({{ $item->id }})">Mouvement stock</flux:button>
                    <flux:button variant="primary" wire:click="openEdit({{ $item->id }})">Modifier</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model.self="showMovementModal" class="max-w-lg">
        <div class="space-y-5">
            <flux:heading size="lg">Mouvement de stock</flux:heading>
            <div class="grid gap-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold">Type</label>
                    <select wire:model="movementType" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <option value="entree">Entree</option>
                        <option value="sortie">Sortie</option>
                        <option value="ajustement">Ajustement (stock reel)</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Quantite</label>
                    <input type="number" min="1" wire:model="movementQuantity" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Reference document</label>
                    <input type="text" wire:model="movementReference" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Motif</label>
                    <textarea wire:model="movementReason" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showMovementModal', false)">Annuler</flux:button>
                <flux:button variant="primary" wire:click="saveMovement">Valider</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
