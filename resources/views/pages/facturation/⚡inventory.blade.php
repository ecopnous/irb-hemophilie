<?php

use App\Models\facturation\InventoryAsset;
use App\Models\facturation\InventoryCategory;
use App\Models\facturation\InventoryLocation;
use App\Models\Configs\Departement;
use App\Models\Configs\Service;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

new #[Title('Inventaire des equipements'), Layout('layouts::app.other.facturation')] class extends Component {
    use WithPagination;

    public string $pageMode = 'list';
    public string $activeTab = 'equipements';
    public string $search = '';
    public string $statusFilter = '';

    public array $locationForm = [];
    public array $categoryForm = [];
    public array $assetForm = [];

    public function mount(): void
    {
        $this->pageMode = request()->routeIs('facturation.inventory.create') ? 'create' : 'list';

        $this->locationForm = [
            'name' => '',
            'code' => '',
            'type' => 'espace_libre',
            'building' => '',
            'floor' => '',
            'departement_id' => null,
            'service_id' => null,
            'description' => '',
        ];

        $this->categoryForm = [
            'name' => '',
            'code' => '',
            'description' => '',
            'default_useful_life_years' => null,
            'default_depreciation_rate' => null,
        ];

        $this->assetForm = [
            'inventory_number' => '',
            'marque' => '',
            'modele' => '',
            'reference' => '',
            'serial_number' => '',
            'quantity' => 1,
            'status' => 'en_service',
            'description' => '',
            'observation' => '',
            'acquired_at' => now()->format('Y-m-d'),
            'acquisition_cost' => null,
            'salvage_value' => 0,
            'useful_life_years' => null,
            'depreciation_method' => 'lineaire',
            'depreciation_rate' => null,
            'currency' => 'USD',
            'inventory_location_id' => null,
            'inventory_category_id' => null,
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function updatedLocationFormDepartementId(): void
    {
        $this->locationForm['service_id'] = null;
    }

    public function createLocation(): void
    {
        $this->locationForm['departement_id'] = filled($this->locationForm['departement_id'] ?? null) ? (int) $this->locationForm['departement_id'] : null;
        $this->locationForm['service_id'] = filled($this->locationForm['service_id'] ?? null) ? (int) $this->locationForm['service_id'] : null;

        $validated = $this->validate([
            'locationForm.name' => ['required', 'string', 'max:255'],
            'locationForm.code' => ['nullable', 'string', 'max:40'],
            'locationForm.type' => ['required', 'in:bureau,couloir,espace_libre,laboratoire,stock,salle_technique,autre'],
            'locationForm.building' => ['nullable', 'string', 'max:255'],
            'locationForm.floor' => ['nullable', 'string', 'max:50'],
            'locationForm.departement_id' => ['nullable', 'exists:departements,id'],
            'locationForm.service_id' => [
                'nullable',
                Rule::exists('services', 'id')->where(function ($query) {
                    $departementId = $this->locationForm['departement_id'] ?? null;
                    if (filled($departementId)) {
                        $query->where('departement_id', $departementId);
                    }
                }),
            ],
            'locationForm.description' => ['nullable', 'string', 'max:1000'],
        ]);

        InventoryLocation::query()->create(
            array_merge($validated['locationForm'], [
                'hopital_id' => current_hopital_id(),
                'name' => trim((string) $validated['locationForm']['name']),
            ]),
        );

        $this->locationForm = [
            'name' => '',
            'code' => '',
            'type' => 'espace_libre',
            'building' => '',
            'floor' => '',
            'departement_id' => null,
            'service_id' => null,
            'description' => '',
        ];
    }

    public function createCategory(): void
    {
        $validated = $this->validate([
            'categoryForm.name' => ['required', 'string', 'max:255'],
            'categoryForm.code' => ['nullable', 'string', 'max:40'],
            'categoryForm.description' => ['nullable', 'string', 'max:1000'],
            'categoryForm.default_useful_life_years' => ['nullable', 'integer', 'min:1', 'max:60'],
            'categoryForm.default_depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        InventoryCategory::query()->create(
            array_merge($validated['categoryForm'], [
                'hopital_id' => current_hopital_id(),
                'name' => trim((string) $validated['categoryForm']['name']),
            ]),
        );

        $this->categoryForm = [
            'name' => '',
            'code' => '',
            'description' => '',
            'default_useful_life_years' => null,
            'default_depreciation_rate' => null,
        ];
    }

    public function createAsset(): void
    {
        $validated = $this->validate([
            'assetForm.inventory_number' => ['nullable', 'string', 'max:100', 'unique:inventory_assets,inventory_number'],
            'assetForm.marque' => ['nullable', 'string', 'max:255'],
            'assetForm.modele' => ['required', 'string', 'max:255'],
            'assetForm.reference' => ['nullable', 'string', 'max:255'],
            'assetForm.serial_number' => ['nullable', 'string', 'max:255'],
            'assetForm.quantity' => ['required', 'integer', 'min:1'],
            'assetForm.status' => ['required', 'in:en_service,en_panne,en_arret,hors_service,sorti'],
            'assetForm.description' => ['required', 'string', 'max:1000'],
            'assetForm.observation' => ['nullable', 'string', 'max:1000'],
            'assetForm.acquired_at' => ['nullable', 'date'],
            'assetForm.acquisition_cost' => ['required', 'numeric', 'min:0'],
            'assetForm.salvage_value' => ['nullable', 'numeric', 'min:0'],
            'assetForm.useful_life_years' => ['nullable', 'integer', 'min:1', 'max:60'],
            'assetForm.depreciation_method' => ['required', 'in:lineaire,degressif'],
            'assetForm.depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'assetForm.currency' => ['required', 'string', 'size:3'],
            'assetForm.inventory_location_id' => ['required', 'exists:inventory_locations,id'],
            'assetForm.inventory_category_id' => ['required', 'exists:inventory_categories,id'],
        ]);

        $payload = $validated['assetForm'];
        $payload['inventory_number'] = filled($payload['inventory_number']) ? trim((string) $payload['inventory_number']) : $this->generateInventoryNumber();
        $payload['hopital_id'] = current_hopital_id();
        $payload['currency'] = strtoupper((string) $payload['currency']);
        $payload['salvage_value'] = $payload['salvage_value'] ?? 0;
        $payload['depreciation_rate'] = $payload['depreciation_method'] === 'degressif' ? $payload['depreciation_rate'] : null;

        InventoryAsset::query()->create($payload);

        $this->assetForm = [
            'inventory_number' => '',
            'marque' => '',
            'modele' => '',
            'reference' => '',
            'serial_number' => '',
            'quantity' => 1,
            'status' => 'en_service',
            'description' => '',
            'observation' => '',
            'acquired_at' => now()->format('Y-m-d'),
            'acquisition_cost' => null,
            'salvage_value' => 0,
            'useful_life_years' => null,
            'depreciation_method' => 'lineaire',
            'depreciation_rate' => null,
            'currency' => 'USD',
            'inventory_location_id' => null,
            'inventory_category_id' => null,
        ];
    }

    #[Computed]
    public function locations()
    {
        return InventoryLocation::query()
            ->with(['departement', 'service'])
            ->where('hopital_id', current_hopital_id())
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function departements()
    {
        return Departement::query()->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return InventoryCategory::query()->where('hopital_id', current_hopital_id())->orderBy('name')->get();
    }

    #[Computed]
    public function assets()
    {
        return InventoryAsset::query()
            ->with(['location', 'category'])
            ->where('hopital_id', current_hopital_id())
            ->when($this->statusFilter !== '', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('inventory_number', 'like', $term)->orWhere('marque', 'like', $term)->orWhere('modele', 'like', $term)->orWhere('serial_number', 'like', $term)->orWhere('reference', 'like', $term)->orWhere('description', 'like', $term)->orWhereHas('location', fn($sq) => $sq->where('name', 'like', $term))->orWhereHas('category', fn($sq) => $sq->where('name', 'like', $term));
                });
            })
            ->latest('created_at')
            ->paginate(20);
    }

    #[Computed]
    public function stats(): array
    {
        $items = InventoryAsset::query()->with('category')->where('hopital_id', current_hopital_id())->get();

        return [
            'count' => $items->count(),
            'acquisition' => (float) $items->sum(fn(InventoryAsset $asset) => (float) $asset->acquisition_cost),
            'depreciation' => (float) $items->sum(fn(InventoryAsset $asset) => $asset->accumulatedDepreciationAmount()),
            'net' => (float) $items->sum(fn(InventoryAsset $asset) => $asset->netBookValue()),
        ];
    }

    public function locationTypeOptions(): array
    {
        return [['label' => 'Bureau', 'value' => 'bureau'], ['label' => 'Couloir', 'value' => 'couloir'], ['label' => 'Espace libre', 'value' => 'espace_libre'], ['label' => 'Laboratoire', 'value' => 'laboratoire'], ['label' => 'Stock', 'value' => 'stock'], ['label' => 'Salle technique', 'value' => 'salle_technique'], ['label' => 'Autre', 'value' => 'autre']];
    }

    public function statusOptions(): array
    {
        return [['label' => 'Tous', 'value' => ''], ['label' => 'En service', 'value' => 'en_service'], ['label' => 'En panne', 'value' => 'en_panne'], ['label' => 'En arret', 'value' => 'en_arret'], ['label' => 'Hors service', 'value' => 'hors_service'], ['label' => 'Sorti', 'value' => 'sorti']];
    }

    public function assetStatusOptions(): array
    {
        return collect($this->statusOptions())->reject(fn($item) => $item['value'] === '')->values()->all();
    }

    public function depreciationMethodOptions(): array
    {
        return [['label' => 'Lineaire', 'value' => 'lineaire'], ['label' => 'Degressif', 'value' => 'degressif']];
    }

    public function locationSelectOptions(): array
    {
        return $this->locations->map(fn($item) => ['label' => $item->name . ' (' . ucfirst(str_replace('_', ' ', $item->type)) . ')', 'value' => (string) $item->id])->all();
    }

    public function categorySelectOptions(): array
    {
        return $this->categories->map(fn($item) => ['label' => $item->name, 'value' => (string) $item->id])->all();
    }

    public function departementSelectOptions(): array
    {
        return $this->departements
            ->map(fn($item) => ['label' => $item->name, 'value' => (string) $item->id])
            ->prepend(['label' => 'Aucun departement', 'value' => ''])
            ->values()
            ->all();
    }

    public function serviceByDepartementOptions(): array
    {
        $departementId = $this->locationForm['departement_id'] ?? null;
        if (!filled($departementId)) {
            return [['label' => 'Aucun service', 'value' => '']];
        }

        return Service::query()
            ->where('departement_id', $departementId)
            ->orderBy('name')
            ->get()
            ->map(fn($item) => ['label' => $item->name, 'value' => (string) $item->id])
            ->prepend(['label' => 'Aucun service', 'value' => ''])
            ->values()
            ->all();
    }

    protected function generateInventoryNumber(): string
    {
        $prefix = 'INV-' . now()->format('y');
        $seed = InventoryAsset::query()->where('hopital_id', current_hopital_id())->count() + 1;

        do {
            $number = $prefix . '-' . str_pad((string) $seed, 5, '0', STR_PAD_LEFT);
            $exists = InventoryAsset::query()->where('inventory_number', $number)->exists();
            $seed++;
        } while ($exists);

        return $number;
    }
};
?>

<div class="space-y-5">
    <section
        class="overflow-hidden rounded-4xl border border-indigo-100 bg-linear-to-br from-white via-indigo-50/60 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="space-y-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Facturation', 'link' => route('facturation.index'), 'icon' => 'banknotes'],
                        ['label' => 'Inventaire', 'icon' => 'building-office-2'],
                    ]" />
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-indigo-600 dark:text-indigo-300">
                        Inventaire immobilisations
                    </p>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        Gestion des equipements et amortissements
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Emplacements, categories et valorisation comptable (depreciation / amortissement).
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        class="rounded-2xl border border-indigo-100 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Actifs</p>
                        <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                            {{ number_format($this->stats['count']) }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-indigo-100 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Valeur acquisition
                        </p>
                        <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                            {{ number_format($this->stats['acquisition'], 2, ',', ' ') }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-indigo-100 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Depreciation cumulee
                        </p>
                        <p class="mt-2 text-2xl font-black text-amber-700 dark:text-amber-300">
                            {{ number_format($this->stats['depreciation'], 2, ',', ' ') }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-indigo-100 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Valeur nette
                            comptable</p>
                        <p class="mt-2 text-2xl font-black text-emerald-700 dark:text-emerald-300">
                            {{ number_format($this->stats['net'], 2, ',', ' ') }}</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-indigo-100/80 pt-4 dark:border-slate-800">
                <flux:button :href="route('facturation.inventory')"
                    :variant="$pageMode === 'list' ? 'primary' : 'ghost'" color="indigo" wire:navigate>
                    Page liste
                </flux:button>
                <flux:button :href="route('facturation.inventory.create')"
                    :variant="$pageMode === 'create' ? 'primary' : 'ghost'" color="indigo" wire:navigate>
                    Page creation
                </flux:button>
                @if ($pageMode === 'list')
                    <flux:button :href="route('facturation.inventory.report.pdf')" target="_blank" variant="ghost"
                        icon="printer">
                        Rapport PDF
                    </flux:button>
                @endif
            </div>

            <div class="flex flex-wrap gap-2 border-t border-indigo-100/80 pt-4 dark:border-slate-800">
                <button type="button" wire:click="setActiveTab('equipements')"
                    class="rounded-xl border px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'equipements' ? 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300' }}">
                    Equipements
                </button>
                <button type="button" wire:click="setActiveTab('emplacements')"
                    class="rounded-xl border px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'emplacements' ? 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300' }}">
                    Emplacements
                </button>
                <button type="button" wire:click="setActiveTab('categories')"
                    class="rounded-xl border px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'categories' ? 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300' }}">
                    Categories
                </button>
            </div>
        </div>
    </section>

    @if ($activeTab === 'equipements')
        <section class="grid gap-5 {{ $pageMode === 'create' ? 'xl:grid-cols-1' : 'xl:grid-cols-1' }}">
            @if ($pageMode === 'create')
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Ajouter un equipement</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Renseigner les donnees techniques et
                        comptables.</p>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <x-input wire:model="assetForm.inventory_number" label="N d inventaire (optionnel)" />
                        <x-input wire:model="assetForm.marque" label="Marque" />
                        <x-input wire:model="assetForm.modele" label="Modele *" />
                        <x-input wire:model="assetForm.reference" label="Reference" />
                        <x-input wire:model="assetForm.serial_number" label="Numero de serie" />
                        <x-number wire:model="assetForm.quantity" min="1" label="Nombre *" />

                        <x-select.styled wire:model="assetForm.inventory_location_id" label="Emplacement *"
                            :options="$this->locationSelectOptions()" select="label:label|value:value" />
                        <x-select.styled wire:model="assetForm.inventory_category_id" label="Categorie *"
                            :options="$this->categorySelectOptions()" select="label:label|value:value" />
                        <x-select.styled wire:model="assetForm.status" label="Etat *" :options="$this->assetStatusOptions()"
                            select="label:label|value:value" />

                        <x-date wire:model="assetForm.acquired_at" label="Date acquisition" />
                        <x-number wire:model="assetForm.acquisition_cost" step="0.01" min="0"
                            label="Montant acquisition *" />
                        <x-number wire:model="assetForm.salvage_value" step="0.01" min="0"
                            label="Valeur residuelle" />
                        <x-number wire:model="assetForm.useful_life_years" min="1" max="60"
                            label="Duree de vie (ans)" />
                        <x-select.styled wire:model.live="assetForm.depreciation_method" label="Methode depreciation *"
                            :options="$this->depreciationMethodOptions()" select="label:label|value:value" />
                        @if (($assetForm['depreciation_method'] ?? 'lineaire') === 'degressif')
                            <x-number wire:model="assetForm.depreciation_rate" step="0.01" min="0"
                                max="100" label="Taux degressif (%)" />
                        @endif
                        <x-input wire:model="assetForm.currency" label="Devise" />
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3">
                        <x-textarea wire:model="assetForm.description" label="Description *" rows="3" />
                        <x-textarea wire:model="assetForm.observation" label="Observation" rows="2" />
                    </div>

                    <flux:button class="mt-4 w-full" variant="primary" color="indigo" wire:click="createAsset">
                        Enregistrer l equipement
                    </flux:button>
                </div>
            @else
                <div class="space-y-4">
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <x-input wire:model.live.debounce.400ms="search"
                                placeholder="Recherche inventaire, marque, modele, serie..." label="Recherche" />
                            <x-select.styled wire:model.live="statusFilter" label="Etat" :options="$this->statusOptions()"
                                select="label:label|value:value" />
                        </div>
                    </div>

                    <div
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-900/70">
                                <tr
                                    class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                    <th class="px-4 py-3">Equipement</th>
                                    <th class="px-4 py-3">Affectation</th>
                                    <th class="px-4 py-3 text-right">Montant</th>
                                    <th class="px-4 py-3 text-right">Depreciation</th>
                                    <th class="px-4 py-3 text-right">VNC</th>
                                    <th class="px-4 py-3 text-center">Etat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                @forelse($this->assets as $asset)
                                    <tr>
                                        <td class="px-4 py-3 align-top">
                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                {{ $asset->inventory_number }}</p>
                                            <p class="text-slate-600 dark:text-slate-300">
                                                {{ trim(($asset->marque ?: '-') . ' ' . ($asset->modele ?: '-')) }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Ref:
                                                {{ $asset->reference ?: '-' }} | SN:
                                                {{ $asset->serial_number ?: '-' }}</p>
                                        </td>
                                        <td class="px-4 py-3 align-top text-slate-600 dark:text-slate-300">
                                            <p>{{ $asset->location?->name ?: '-' }}</p>
                                            <p class="text-xs">{{ $asset->category?->name ?: '-' }}</p>
                                            <p class="text-xs">{{ $asset->acquired_at?->format('d/m/Y') ?: '-' }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900 dark:text-white">
                                            {{ number_format((float) $asset->acquisition_cost, 2, ',', ' ') }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <p class="font-semibold text-amber-700 dark:text-amber-300">
                                                {{ number_format($asset->accumulatedDepreciationAmount(), 2, ',', ' ') }}
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                                Mensuel:
                                                {{ number_format($asset->monthlyDepreciationAmount(), 2, ',', ' ') }}
                                            </p>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-black text-emerald-700 dark:text-emerald-300">
                                            {{ number_format($asset->netBookValue(), 2, ',', ' ') }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                                {{ str_replace('_', ' ', $asset->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                            Aucun equipement enregistre.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                            {{ $this->assets->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </section>
    @endif

    @if ($activeTab === 'emplacements')
        <section class="grid gap-5">
            @if ($pageMode === 'create')
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Nouvel emplacement</h2>
                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <x-input wire:model="locationForm.name" label="Nom *" />
                        <x-input wire:model="locationForm.code" label="Code" />
                        <x-select.styled wire:model="locationForm.type" label="Type *" :options="$this->locationTypeOptions()"
                            select="label:label|value:value" />
                        <x-input wire:model="locationForm.building" label="Batiment" />
                        <x-input wire:model="locationForm.floor" label="Niveau / etage" />
                        <x-select.styled wire:model.live="locationForm.departement_id" label="Departement (optionnel)"
                            :options="$this->departementSelectOptions()" select="label:label|value:value" />
                        <x-select.styled wire:model="locationForm.service_id" label="Service (optionnel)"
                            :options="$this->serviceByDepartementOptions()" select="label:label|value:value" />
                    </div>
                    <div class="mt-3">
                        <x-textarea wire:model="locationForm.description" label="Description" rows="3" />
                    </div>
                    <flux:button class="mt-4 w-full" variant="primary" wire:click="createLocation">Ajouter
                        emplacement</flux:button>
                </div>
            @else
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                            <tr
                                class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                <th class="px-4 py-3">Nom</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Departement</th>
                                <th class="px-4 py-3">Service</th>
                                <th class="px-4 py-3">Batiment</th>
                                <th class="px-4 py-3">Niveau</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse($this->locations as $location)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                        {{ $location->name }}</td>
                                    <td class="px-4 py-3">{{ str_replace('_', ' ', $location->type) }}</td>
                                    <td class="px-4 py-3">{{ $location->departement?->name ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $location->service?->name ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $location->building ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $location->floor ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Aucun
                                        emplacement.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    @if ($activeTab === 'categories')
        <section class="grid gap-5">
            @if ($pageMode === 'create')
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Nouvelle categorie</h2>
                    <div class="mt-4 grid grid-cols-1 gap-3">
                        <x-input wire:model="categoryForm.name" label="Nom *" />
                        <x-input wire:model="categoryForm.code" label="Code" />
                        <x-number wire:model="categoryForm.default_useful_life_years" min="1" max="60"
                            label="Duree de vie par defaut (ans)" />
                        <x-number wire:model="categoryForm.default_depreciation_rate" min="0" max="100"
                            step="0.01" label="Taux de depreciation degressif (%)" />
                        <x-textarea wire:model="categoryForm.description" label="Description" rows="3" />
                    </div>
                    <flux:button class="mt-4 w-full" variant="primary" wire:click="createCategory">Ajouter categorie
                    </flux:button>
                </div>
            @else
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                            <tr
                                class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                <th class="px-4 py-3">Categorie</th>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Duree vie</th>
                                <th class="px-4 py-3">Taux degressif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse($this->categories as $category)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                        {{ $category->name }}</td>
                                    <td class="px-4 py-3">{{ $category->code ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $category->default_useful_life_years ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        {{ $category->default_depreciation_rate ? number_format((float) $category->default_depreciation_rate, 2, ',', ' ') . '%' : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4"
                                        class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Aucune
                                        categorie.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
