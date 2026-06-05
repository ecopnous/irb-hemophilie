<?php

use App\Models\hospitalisation\Chambre;
use App\Models\hospitalisation\HospService;
use App\Models\hospitalisation\Lit;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Detail service hospitalisation'), Layout('layouts::app.other.hospital')] class extends Component {
    use Interactions;

    public HospService $service;
    public array $serviceForm = [];
    public array $chambreForm = [];
    public array $litForm = [];

    public function mount(int $id): void
    {
        abort_unless(current_hopital_id(), 403, 'Aucun hopital courant en session.');

        $this->service = HospService::query()
            ->with(['departement', 'chambres.lits'])
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($id);

        $this->serviceForm = [
            'name' => $this->service->name,
            'description' => (string) ($this->service->description ?? ''),
            'is_active' => $this->service->is_active,
        ];

        $this->chambreForm = [
            'name' => '',
            'reference' => '',
            'type' => 'standard',
            'montant' => '',
            'unite' => 'jour',
            'description' => '',
            'is_active' => true,
        ];

        $this->litForm = [
            'chambre_id' => null,
            'name' => '',
            'reference' => '',
            'description' => '',
            'statut' => 'disponible',
            'is_active' => true,
        ];
    }

    public function refreshService(): void
    {
        $this->service = HospService::query()
            ->with(['departement', 'chambres.lits'])
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($this->service->id);
    }

    public function saveService(): void
    {
        $validated = $this->validate([
            'serviceForm.name' => ['required', 'string', 'max:255'],
            'serviceForm.description' => ['nullable', 'string', 'max:2000'],
            'serviceForm.is_active' => ['required', 'boolean'],
        ]);

        $this->service->update([
            'name' => Str::title($validated['serviceForm']['name']),
            'description' => $validated['serviceForm']['description'] ?: null,
            'is_active' => (bool) $validated['serviceForm']['is_active'],
        ]);

        $this->refreshService();
        $this->toast()->success('Service mis a jour avec succes.')->send();
    }

    public function saveChambre(): void
    {
        $validated = $this->validate([
            'chambreForm.name' => ['required', 'string', 'max:255'],
            'chambreForm.reference' => ['nullable', 'string', 'max:255'],
            'chambreForm.type' => ['required', 'in:standard,vip,privee'],
            'chambreForm.montant' => ['required', 'numeric', 'min:0'],
            'chambreForm.unite' => ['required', 'in:jour,semaine,mois,annee'],
            'chambreForm.description' => ['nullable', 'string', 'max:2000'],
            'chambreForm.is_active' => ['required', 'boolean'],
        ]);

        $nextNumber = $this->service->chambres()->count() + 1;

        Chambre::query()->create([
            'hosp_service_id' => $this->service->id,
            'name' => Str::upper($validated['chambreForm']['name']),
            'reference' => $validated['chambreForm']['reference'] ?: 'CH-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT),
            'type' => $validated['chambreForm']['type'],
            'montant' => $validated['chambreForm']['montant'],
            'unite' => $validated['chambreForm']['unite'],
            'description' => $validated['chambreForm']['description'] ?: null,
            'is_active' => (bool) $validated['chambreForm']['is_active'],
        ]);

        $this->chambreForm = [
            'name' => '',
            'reference' => '',
            'type' => 'standard',
            'montant' => '',
            'unite' => 'jour',
            'description' => '',
            'is_active' => true,
        ];

        $this->refreshService();
        $this->toast()->success('Chambre ajoutee avec succes.')->send();
    }

    public function saveLit(): void
    {
        $validated = $this->validate([
            'litForm.chambre_id' => ['required', 'integer', 'exists:chambres,id'],
            'litForm.name' => ['required', 'string', 'max:255'],
            'litForm.reference' => ['nullable', 'string', 'max:255'],
            'litForm.description' => ['nullable', 'string', 'max:2000'],
            'litForm.statut' => ['required', 'in:disponible,occupe,maintenance,hors_service'],
            'litForm.is_active' => ['required', 'boolean'],
        ]);

        $chambre = $this->service->chambres->firstWhere('id', (int) $validated['litForm']['chambre_id']);
        abort_unless($chambre, 404);

        $nextNumber = $chambre->lits()->count() + 1;

        Lit::query()->create([
            'chambre_id' => $chambre->id,
            'name' => Str::upper($validated['litForm']['name']),
            'reference' => $validated['litForm']['reference'] ?: 'L-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT),
            'description' => $validated['litForm']['description'] ?: null,
            'statut' => $validated['litForm']['statut'],
            'is_active' => (bool) $validated['litForm']['is_active'],
        ]);

        $this->litForm = [
            'chambre_id' => null,
            'name' => '',
            'reference' => '',
            'description' => '',
            'statut' => 'disponible',
            'is_active' => true,
        ];

        $this->refreshService();
        $this->toast()->success('Lit ajoute avec succes.')->send();
    }

    public function toggleChambre(int $chambreId): void
    {
        $chambre = $this->service->chambres->firstWhere('id', $chambreId);
        abort_unless($chambre, 404);

        $chambre->update(['is_active' => !$chambre->is_active]);
        $this->refreshService();
    }

    public function toggleLit(int $litId): void
    {
        $lit = Lit::query()
            ->whereHas('chambre', fn($query) => $query->where('hosp_service_id', $this->service->id))
            ->findOrFail($litId);

        $lit->update(['is_active' => !$lit->is_active]);
        $this->refreshService();
    }
};
?>

<section class="w-full space-y-6">
    <x-header_default :title="$service->name" :subtitle="__(
        'Pilotez les chambres, les lits et l activation du service depuis une seule vue detaillee.',
    )" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Hospitalisation', 'link' => 'hospital.index', 'icon' => 'home-modern'],
        ['label' => 'Configuration', 'link' => 'hospital.configuration', 'icon' => 'wrench-screwdriver'],
        ['label' => $service->name, 'icon' => 'building-office-2'],
    ]">
        <x-slot:actions>
            <x-button href="{{ route('hospital.configuration') }}" wire:navigate outline>
                Retour
            </x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <div class="space-y-6">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Service</p>
                <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Parametres principaux</h2>
                <div class="mt-5 space-y-4">
                    <x-input wire:model="serviceForm.name" label="Nom du service *" />
                    <x-textarea wire:model="serviceForm.description" label="Description" rows="4" maxlength="2000" count />
                    <x-select.native wire:model="serviceForm.is_active" label="Statut"
                        :options="[['label' => 'Actif', 'value' => 1], ['label' => 'Inactif', 'value' => 0]]" />
                    <flux:button variant="primary" color="sky" wire:click="saveService">Mettre a jour le service</flux:button>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Ajouter une chambre</p>
                <div class="mt-5 space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="chambreForm.name" label="Nom / numero *" />
                        <x-input wire:model="chambreForm.reference" label="Reference interne" />
                    </div>
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-select.native wire:model="chambreForm.type" label="Type *"
                            :options="[['label' => 'Standard', 'value' => 'standard'], ['label' => 'VIP', 'value' => 'vip'], ['label' => 'Privee', 'value' => 'privee']]" />
                        <x-input wire:model="chambreForm.montant" type="number" step="0.01" label="Montant *" />
                        <x-select.native wire:model="chambreForm.unite" label="Unite *"
                            :options="[['label' => 'Jour', 'value' => 'jour'], ['label' => 'Semaine', 'value' => 'semaine'], ['label' => 'Mois', 'value' => 'mois'], ['label' => 'Annee', 'value' => 'annee']]" />
                    </div>
                    <x-textarea wire:model="chambreForm.description" label="Description" rows="4" maxlength="2000" count />
                    <flux:button variant="primary" color="emerald" wire:click="saveChambre">Ajouter la chambre</flux:button>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Ajouter un lit</p>
                <div class="mt-5 space-y-4">
                    <x-select.native wire:model="litForm.chambre_id" label="Chambre *"
                        :options="$service->chambres->map(fn($chambre) => ['label' => $chambre->name, 'value' => $chambre->id])->values()->all()" />
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="litForm.name" label="Nom du lit *" />
                        <x-input wire:model="litForm.reference" label="Reference interne" />
                    </div>
                    <x-select.native wire:model="litForm.statut" label="Statut initial *"
                        :options="[['label' => 'Disponible', 'value' => 'disponible'], ['label' => 'Occupe', 'value' => 'occupe'], ['label' => 'Maintenance', 'value' => 'maintenance'], ['label' => 'Hors service', 'value' => 'hors_service']]" />
                    <x-textarea wire:model="litForm.description" label="Description" rows="4" maxlength="2000" count />
                    <flux:button variant="primary" color="violet" wire:click="saveLit">Ajouter le lit</flux:button>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($service->chambres as $chambre)
                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ $chambre->name }}</h3>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $chambre->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                    {{ $chambre->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                {{ $chambre->reference ?: 'Sans reference' }} · {{ ucfirst($chambre->type) }} ·
                                {{ number_format((float) $chambre->montant, 2, ',', ' ') }} / {{ $chambre->unite }}
                            </p>
                            @if (filled($chambre->description))
                                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $chambre->description }}</p>
                            @endif
                        </div>
                        <button type="button" wire:click="toggleChambre({{ $chambre->id }})"
                            class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 dark:border-slate-700 dark:text-slate-200">
                            {{ $chambre->is_active ? 'Desactiver' : 'Activer' }}
                        </button>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @forelse ($chambre->lits as $lit)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white">{{ $lit->name }}</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $lit->reference ?: 'Sans reference' }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $lit->statut === 'disponible' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                        {{ str_replace('_', ' ', ucfirst($lit->statut)) }}
                                    </span>
                                </div>
                                @if (filled($lit->description))
                                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $lit->description }}</p>
                                @endif
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                        {{ $lit->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                    <button type="button" wire:click="toggleLit({{ $lit->id }})"
                                        class="text-sm font-semibold text-sky-600 hover:text-sky-700 dark:text-sky-300">
                                        {{ $lit->is_active ? 'Desactiver' : 'Activer' }}
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 px-6 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400 md:col-span-2">
                                Aucun lit configure dans cette chambre.
                            </div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    Aucune chambre n est encore configuree pour ce service.
                </div>
            @endforelse
        </div>
    </div>
</section>
