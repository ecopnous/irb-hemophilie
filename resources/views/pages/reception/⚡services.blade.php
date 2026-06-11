<?php

use App\Models\ReceptionBaseService;
use App\Services\ReceptionCatalogService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Services de base reception'), Layout('layouts::app.other.reception')] class extends Component {
    public bool $showFormModal = false;

    public bool $showViewModal = false;

    public ?int $editingId = null;

    public ?int $viewingId = null;

    public string $formName = '';

    public string $formCode = '';

    public string $formCategory = 'accueil';

    public ?string $formDescription = null;

    public ?string $formPrice = '0';

    public bool $formIsActive = true;

    public function mount(): void
    {
        abort_unless(current_hopital_id(), 403, 'Selectionnez un hopital pour gerer les services de base.');
    }

    protected function baseQuery()
    {
        return ReceptionBaseService::query()->whereHopitalId(current_hopital_id());
    }

    #[Computed]
    public function stats(): array
    {
        $services = $this->baseQuery()->get();

        return [
            'total' => $services->count(),
            'active' => $services->where('is_active', true)->count(),
            'free' => $services->where('price', '<=', 0)->count(),
            'paid' => $services->where('price', '>', 0)->count(),
        ];
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return collect(app(ReceptionCatalogService::class)->serviceCategoryLabels())
            ->map(fn ($label, $value) => ['label' => $label, 'value' => $value])
            ->values()
            ->all();
    }

    #[Computed]
    public function viewedService(): ?ReceptionBaseService
    {
        if (! $this->viewingId) {
            return null;
        }

        return $this->baseQuery()->with('updatedBy')->find($this->viewingId);
    }

    public function importCatalog(): void
    {
        $created = app(ReceptionCatalogService::class)->seedBaseServicesForHopital((int) current_hopital_id());
        $this->dispatch('pg:eventRefresh-receptionBaseServiceTable');
        unset($this->stats);

        Flux::toast(
            heading: 'Catalogue importe',
            text: $created > 0 ? "{$created} services ajoutes." : 'Le catalogue est deja a jour.',
            variant: 'success',
        );
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    #[On('service-edit')]
    public function openEdit(int $id): void
    {
        $service = $this->baseQuery()->findOrFail($id);
        $this->editingId = $service->id;
        $this->formName = $service->name;
        $this->formCode = (string) ($service->code ?? '');
        $this->formCategory = $service->category;
        $this->formDescription = $service->description;
        $this->formPrice = number_format((float) $service->price, 2, '.', '');
        $this->formIsActive = (bool) $service->is_active;
        $this->showFormModal = true;
        $this->showViewModal = false;
    }

    #[On('service-view')]
    public function openView(int $id): void
    {
        $this->viewingId = $id;
        $this->showViewModal = true;
        unset($this->viewedService);
    }

    public function cancelForm(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->formName = '';
        $this->formCode = '';
        $this->formCategory = 'accueil';
        $this->formDescription = null;
        $this->formPrice = '0';
        $this->formIsActive = true;
        $this->resetValidation();
    }

    public function saveService(): void
    {
        $hopitalId = (int) current_hopital_id();

        $validated = $this->validate([
            'formName' => [
                'required', 'string', 'max:255',
                Rule::unique('reception_base_services', 'name')
                    ->where(fn ($q) => $q->where('hopital_id', $hopitalId))
                    ->ignore($this->editingId),
            ],
            'formCode' => ['nullable', 'string', 'max:50'],
            'formCategory' => ['required', 'in:accueil,administratif,medical,autre'],
            'formDescription' => ['nullable', 'string', 'max:1000'],
            'formPrice' => ['required', 'numeric', 'min:0'],
            'formIsActive' => ['boolean'],
        ]);

        $payload = [
            'name' => trim($validated['formName']),
            'code' => filled($validated['formCode']) ? trim($validated['formCode']) : null,
            'category' => $validated['formCategory'],
            'description' => $validated['formDescription'],
            'price' => round((float) $validated['formPrice'], 2),
            'currency' => 'USD',
            'is_active' => $validated['formIsActive'],
            'updated_by' => Auth::id(),
        ];

        if ($this->editingId) {
            $this->baseQuery()->findOrFail($this->editingId)->update($payload);
            Flux::toast('Service mis a jour.', variant: 'success');
        } else {
            ReceptionBaseService::query()->create($payload + ['hopital_id' => $hopitalId]);
            Flux::toast('Service ajoute.', variant: 'success');
        }

        $this->dispatch('pg:eventRefresh-receptionBaseServiceTable');
        $this->cancelForm();
        unset($this->stats);
    }

    public function toggleActive(int $id): void
    {
        $service = $this->baseQuery()->findOrFail($id);
        $service->update([
            'is_active' => ! $service->is_active,
            'updated_by' => Auth::id(),
        ]);

        $this->dispatch('pg:eventRefresh-receptionBaseServiceTable');
        unset($this->stats, $this->viewedService);

        Flux::toast($service->is_active ? 'Service reactive.' : 'Service desactive.', variant: 'success');
    }
};
?>

<section class="w-full space-y-6 p-4 md:p-6">
    <flux:heading class="sr-only">Services de base reception</flux:heading>

    <x-header_default
        title="Services de base"
        subtitle="Catalogue des prestations administratives et d accueil de la reception"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Reception', 'link' => 'dashboard', 'icon' => 'building-office-2'],
            ['label' => 'Services de base', 'icon' => 'briefcase'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="importCatalog">Importer catalogue</x-button>
            <x-button icon="plus" position="left" wire:click="openCreate">Nouveau service</x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-cyan-200 bg-cyan-50/80 p-5 shadow-sm dark:border-cyan-500/20 dark:bg-cyan-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-cyan-700 dark:text-cyan-300">Services</p>
            <p class="mt-3 text-3xl font-black text-cyan-900 dark:text-cyan-100">{{ $this->stats['total'] }}</p>
        </div>
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Actifs</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['active'] }}</p>
        </div>
        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Gratuits</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['free'] }}</p>
        </div>
        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Payants</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ $this->stats['paid'] }}</p>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 md:p-6">
        <livewire:reception-base-service-table />
    </div>

    <flux:modal wire:model.self="showFormModal" class="max-w-2xl">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le service' : 'Nouveau service' }}</flux:heading>
                <flux:subheading>Definissez une prestation de base proposee par la reception.</flux:subheading>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold">Intitule *</label>
                    <input type="text" wire:model="formName" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @error('formName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Code</label>
                    <input type="text" wire:model="formCode" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
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
                    <label class="mb-2 block text-sm font-semibold">Prix (USD)</label>
                    <input type="number" min="0" step="0.01" wire:model="formPrice" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold">Description</label>
                    <textarea wire:model="formDescription" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 text-sm font-semibold">
                        <input type="checkbox" wire:model="formIsActive" class="rounded border-slate-300" />
                        Service actif
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelForm">Annuler</flux:button>
                <flux:button variant="primary" wire:click="saveService">Enregistrer</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model.self="showViewModal" class="max-w-2xl">
        @if ($this->viewedService)
            @php $service = $this->viewedService; @endphp
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $service->name }}</flux:heading>
                    <flux:subheading>{{ $service->code ?: 'Sans code' }}</flux:subheading>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <p class="text-xs uppercase tracking-widest text-slate-400">Categorie</p>
                        <p class="mt-2 font-bold capitalize">{{ $service->category }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <p class="text-xs uppercase tracking-widest text-slate-400">Tarif</p>
                        <p class="mt-2 text-2xl font-black">{{ number_format((float) $service->price, 2, ',', ' ') }} $</p>
                    </div>
                </div>
                <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ $service->description ?: 'Aucune description renseignee.' }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <flux:badge :color="$service->is_active ? 'emerald' : 'zinc'" inset>
                        {{ $service->is_active ? 'Actif' : 'Inactif' }}
                    </flux:badge>
                    @if ($service->updatedBy)
                        <flux:badge color="zinc" inset>Mis a jour par {{ $service->updatedBy->name }}</flux:badge>
                    @endif
                </div>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showViewModal', false)">Fermer</flux:button>
                    <flux:button wire:click="toggleActive({{ $service->id }})">
                        {{ $service->is_active ? 'Desactiver' : 'Reactiver' }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="openEdit({{ $service->id }})">Modifier</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</section>
