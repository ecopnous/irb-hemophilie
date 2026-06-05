<?php

use App\Models\LaboratoryConsumable;
use App\Services\LaboratoryStockService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Stock laboratoire'), Layout('layouts::app.other.laboratoire')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public bool $lowOnly = false;

    public array $consumableForm = [];
    public array $movementForm = [];

    public function mount(): void
    {
        $this->resetConsumableForm();
        $this->resetMovementForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedLowOnly(): void
    {
        $this->resetPage();
    }

    public function createConsumable(): void
    {
        $validated = $this->validate([
            'consumableForm.name' => ['required', 'string', 'max:255'],
            'consumableForm.reference' => ['nullable', 'string', 'max:255'],
            'consumableForm.category' => ['required', 'in:reactif,bande,kit,controle,tube,consommable,equipement_circulant,autre'],
            'consumableForm.unit' => ['required', 'string', 'max:40'],
            'consumableForm.stock_min' => ['required', 'integer', 'min:0'],
            'consumableForm.storage_condition' => ['nullable', 'string', 'max:255'],
            'consumableForm.description' => ['nullable', 'string', 'max:1000'],
        ]);

        LaboratoryConsumable::query()->create($validated['consumableForm'] + [
            'hopital_id' => current_hopital_id(),
            'current_stock' => 0,
        ]);

        $this->resetConsumableForm();
    }

    public function saveMovement(LaboratoryStockService $service): void
    {
        $validated = $this->validate([
            'movementForm.laboratory_consumable_id' => ['required', 'integer', 'exists:laboratory_consumables,id'],
            'movementForm.movement_type' => ['required', 'in:in,out,adjustment,loss,expired,transfer'],
            'movementForm.quantity' => ['required', 'integer', 'gt:0'],
            'movementForm.reference' => ['nullable', 'string', 'max:255'],
            'movementForm.lot_number' => ['nullable', 'string', 'max:255'],
            'movementForm.expiration_date' => ['nullable', 'date'],
            'movementForm.destination' => ['nullable', 'string', 'max:255'],
            'movementForm.reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->moveStock($validated['movementForm'] + [
            'user_id' => auth()->id(),
        ]);

        $this->resetMovementForm();
    }

    #[Computed]
    public function consumables()
    {
        return LaboratoryConsumable::query()
            ->whereHopitalId(current_hopital_id())
            ->when($this->categoryFilter !== '', fn($q) => $q->where('category', $this->categoryFilter))
            ->when($this->lowOnly, fn($q) => $q->whereColumn('current_stock', '<=', 'stock_min'))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('storage_condition', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function consumableOptions(): array
    {
        return LaboratoryConsumable::query()
            ->whereHopitalId(current_hopital_id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($item) => [
                'label' => $item->name . ' - stock: ' . $item->current_stock . ' ' . $item->unit,
                'value' => (string) $item->id,
            ])
            ->all();
    }

    #[Computed]
    public function stats(): array
    {
        $items = LaboratoryConsumable::query()
            ->whereHopitalId(current_hopital_id())
            ->get();

        return [
            'items' => $items->count(),
            'stock' => (int) $items->sum('current_stock'),
            'low' => $items->filter(fn($item) => $item->isLowStock())->count(),
            'inactive' => $items->where('is_active', false)->count(),
        ];
    }

    public function categoryOptions(): array
    {
        return [
            ['label' => 'Tous', 'value' => ''],
            ['label' => 'Reactif', 'value' => 'reactif'],
            ['label' => 'Bande', 'value' => 'bande'],
            ['label' => 'Kit', 'value' => 'kit'],
            ['label' => 'Controle qualite', 'value' => 'controle'],
            ['label' => 'Tube / prelevement', 'value' => 'tube'],
            ['label' => 'Consommable', 'value' => 'consommable'],
            ['label' => 'Equipement circulant', 'value' => 'equipement_circulant'],
            ['label' => 'Autre', 'value' => 'autre'],
        ];
    }

    public function movementTypeOptions(): array
    {
        return [
            ['label' => 'Entree stock', 'value' => 'in'],
            ['label' => 'Sortie utilisation', 'value' => 'out'],
            ['label' => 'Ajustement inventaire', 'value' => 'adjustment'],
            ['label' => 'Perte / casse', 'value' => 'loss'],
            ['label' => 'Peremption', 'value' => 'expired'],
            ['label' => 'Transfert', 'value' => 'transfer'],
        ];
    }

    public function categoryLabel(string $category): string
    {
        return collect($this->categoryOptions())->firstWhere('value', $category)['label'] ?? ucfirst($category);
    }

    protected function resetConsumableForm(): void
    {
        $this->consumableForm = [
            'name' => '',
            'reference' => '',
            'category' => 'reactif',
            'unit' => 'unite',
            'stock_min' => 0,
            'storage_condition' => '',
            'description' => '',
        ];
    }

    protected function resetMovementForm(): void
    {
        $this->movementForm = [
            'laboratory_consumable_id' => null,
            'movement_type' => 'in',
            'quantity' => null,
            'reference' => '',
            'lot_number' => '',
            'expiration_date' => null,
            'destination' => '',
            'reason' => '',
        ];
    }
};
?>

<div class="space-y-5 p-6">
    <section
        class="overflow-hidden rounded-4xl border border-blue-100 bg-linear-to-br from-white via-blue-50/60 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="space-y-5 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Laboratoire', 'link' => route('laboratoire.index'), 'icon' => 'beaker'],
                        ['label' => 'Stock laboratoire', 'icon' => 'archive-box'],
                    ]" />
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-blue-600 dark:text-blue-300">
                        Consommables et reactifs
                    </p>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        Stock laboratoire
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Suivi des reactifs, bandes, kits, controles et equipements circulants sans valorisation prix.
                    </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-blue-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">References</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['items'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Alertes stock</p>
                        <p class="mt-1 text-2xl font-black text-amber-700 dark:text-amber-300">{{ $this->stats['low'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[1fr,1.15fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">Creer une reference stock</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <x-input wire:model="consumableForm.name" label="Nom *" />
                <x-input wire:model="consumableForm.reference" label="Reference interne / fournisseur" />
                <x-select.styled wire:model="consumableForm.category" label="Categorie *" :options="collect($this->categoryOptions())->reject(fn($item) => $item['value'] === '')->values()->all()"
                    select="label:label|value:value" />
                <x-input wire:model="consumableForm.unit" label="Unite (boite, flacon, bande...)" />
                <x-number wire:model="consumableForm.stock_min" label="Seuil minimum" min="0" />
                <x-input wire:model="consumableForm.storage_condition" label="Condition stockage" placeholder="2-8C, sec, abri lumiere..." />
                <div class="md:col-span-2">
                    <x-textarea wire:model="consumableForm.description" label="Description / usage" rows="3" />
                </div>
            </div>
            <flux:button class="mt-4 w-full" wire:click="createConsumable" variant="primary" color="blue" icon="plus">
                Ajouter la reference
            </flux:button>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">Saisir un mouvement</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-select.styled wire:model="movementForm.laboratory_consumable_id" label="Reference *"
                        :options="$this->consumableOptions" select="label:label|value:value" />
                </div>
                <x-select.styled wire:model="movementForm.movement_type" label="Type mouvement *" :options="$this->movementTypeOptions()"
                    select="label:label|value:value" />
                <x-number wire:model="movementForm.quantity" label="Quantite *" min="1" />
                <x-input wire:model="movementForm.reference" label="Reference document" placeholder="Bon, BL, requisition..." />
                <x-input wire:model="movementForm.lot_number" label="Numero lot" />
                <x-input type="date" wire:model="movementForm.expiration_date" label="Date expiration" />
                <x-input wire:model="movementForm.destination" label="Destination / poste" />
                <div class="md:col-span-2">
                    <x-textarea wire:model="movementForm.reason" label="Motif / commentaire" rows="3" />
                </div>
            </div>
            <flux:button class="mt-4 w-full" wire:click="saveMovement" variant="primary" color="emerald" icon="check">
                Enregistrer le mouvement
            </flux:button>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="grid gap-3 md:grid-cols-4">
            <x-input wire:model.live.debounce.400ms="search" label="Recherche" placeholder="Nom, reference, stockage..." />
            <x-select.styled wire:model.live="categoryFilter" label="Categorie" :options="$this->categoryOptions()"
                select="label:label|value:value" />
            <label class="flex items-end gap-2 pb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                <input type="checkbox" wire:model.live="lowOnly" class="rounded border-slate-300 text-blue-600">
                Stock bas uniquement
            </label>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-900/70">
                <tr class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Categorie</th>
                    <th class="px-4 py-3 text-right">Stock</th>
                    <th class="px-4 py-3 text-right">Seuil</th>
                    <th class="px-4 py-3 text-center">Etat</th>
                    <th class="px-4 py-3">Stockage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($this->consumables as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $item->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item->reference ?: 'Sans reference' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $this->categoryLabel($item->category) }}</td>
                        <td class="px-4 py-3 text-right font-black text-slate-900 dark:text-white">
                            {{ $item->current_stock }} {{ $item->unit }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $item->stock_min }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($item->isLowStock())
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">Stock bas</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Disponible</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $item->storage_condition ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                            Aucun consommable de laboratoire enregistre.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
            {{ $this->consumables->links() }}
        </div>
    </section>
</div>
