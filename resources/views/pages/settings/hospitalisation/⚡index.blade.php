<?php

use App\Models\Configs\Departement;
use App\Models\hospitalisation\Chambre;
use App\Models\hospitalisation\HospService;
use App\Models\hospitalisation\Lit;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Configuration hospitalisation'), Layout('layouts::app.other.hospital')] class extends Component {
    use Interactions;

    public array $serviceForm = [
        'name' => '',
        'departement_id' => null,
        'description' => '',
        'is_active' => true,
    ];

    public array $chambreForm = [
        'hosp_service_id' => null,
        'name' => '',
        'reference' => '',
        'type' => 'standard',
        'montant' => '',
        'unite' => 'jour',
        'description' => '',
        'is_active' => true,
    ];

    public array $litForm = [
        'chambre_id' => null,
        'name' => '',
        'reference' => '',
        'description' => '',
        'statut' => 'disponible',
        'is_active' => true,
    ];

    public function mount(): void
    {
        abort_unless(current_hopital_id(), 403, 'Aucun hopital courant en session.');
    }

    #[Computed]
    public function departements()
    {
        return Departement::query()->orderBy('name')->get();
    }

    #[Computed]
    public function services()
    {
        return HospService::query()
            ->with(['departement', 'chambres.lits'])
            ->whereHopitalId(current_hopital_id())
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function chambres()
    {
        return Chambre::query()
            ->with('service')
            ->whereHas('service', fn($query) => $query->where('hopital_id', current_hopital_id()))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $bedBase = Lit::query()->whereHas('chambre.service', fn($query) => $query->where('hopital_id', current_hopital_id()));

        return [
            'services' => $this->services->count(),
            'chambres' => $this->chambres->count(),
            'lits' => (clone $bedBase)->count(),
            'disponibles' => (clone $bedBase)->where('statut', 'disponible')->count(),
        ];
    }

    public function saveService(): void
    {
        $validated = $this->validate([
            'serviceForm.name' => ['required', 'string', 'max:255'],
            'serviceForm.departement_id' => ['required', 'integer', 'exists:departements,id'],
            'serviceForm.description' => ['nullable', 'string', 'max:2000'],
            'serviceForm.is_active' => ['required', 'boolean'],
        ]);

        HospService::query()->create([
            'name' => Str::title($validated['serviceForm']['name']),
            'departement_id' => $validated['serviceForm']['departement_id'],
            'description' => $validated['serviceForm']['description'] ?: null,
            'hopital_id' => current_hopital_id(),
            'is_active' => (bool) $validated['serviceForm']['is_active'],
        ]);

        $this->serviceForm = [
            'name' => '',
            'departement_id' => null,
            'description' => '',
            'is_active' => true,
        ];

        $this->toast()->success('Service d hospitalisation cree avec succes.')->send();
    }

    public function saveChambre(): void
    {
        $validated = $this->validate([
            'chambreForm.hosp_service_id' => ['required', 'integer', 'exists:hosp_services,id'],
            'chambreForm.name' => ['required', 'string', 'max:255'],
            'chambreForm.reference' => ['nullable', 'string', 'max:255'],
            'chambreForm.type' => ['required', 'in:standard,vip,privee'],
            'chambreForm.montant' => ['required', 'numeric', 'min:0'],
            'chambreForm.unite' => ['required', 'in:jour,semaine,mois,annee'],
            'chambreForm.description' => ['nullable', 'string', 'max:2000'],
            'chambreForm.is_active' => ['required', 'boolean'],
        ]);

        $service = HospService::query()
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($validated['chambreForm']['hosp_service_id']);

        $nextNumber = $service->chambres()->count() + 1;

        Chambre::query()->create([
            'hosp_service_id' => $service->id,
            'name' => Str::upper($validated['chambreForm']['name']),
            'reference' => $validated['chambreForm']['reference'] ?: 'CH-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT),
            'type' => $validated['chambreForm']['type'],
            'montant' => $validated['chambreForm']['montant'],
            'unite' => $validated['chambreForm']['unite'],
            'description' => $validated['chambreForm']['description'] ?: null,
            'is_active' => (bool) $validated['chambreForm']['is_active'],
        ]);

        $this->chambreForm = [
            'hosp_service_id' => null,
            'name' => '',
            'reference' => '',
            'type' => 'standard',
            'montant' => '',
            'unite' => 'jour',
            'description' => '',
            'is_active' => true,
        ];

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

        $chambre = Chambre::query()
            ->whereHas('service', fn($query) => $query->where('hopital_id', current_hopital_id()))
            ->findOrFail($validated['litForm']['chambre_id']);

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

        $this->toast()->success('Lit ajoute avec succes.')->send();
    }

    public function toggleService(int $serviceId): void
    {
        $service = HospService::query()
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($serviceId);

        $service->update(['is_active' => !$service->is_active]);
    }
};
?>

<section class="w-full space-y-6">
    <x-header_default :title="__('Hospitalisations')" :subtitle="__(
        'Structurez proprement les services d hospitalisation, les chambres et les lits pour fiabiliser les admissions.',
    )" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'icon' => 'cog-6-tooth'],
        ['label' => 'Hospitalisations', 'icon' => 'home-modern'],
    ]" />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Services</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['services'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Chambres</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['chambres'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Lits</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['lits'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Disponibles</p>
            <p class="mt-3 text-3xl font-black text-emerald-600">{{ $this->stats['disponibles'] }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Etape 1</p>
            <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Creer un service</h2>
            <div class="mt-5 space-y-4">
                <x-input wire:model="serviceForm.name" label="Nom du service *" />
                <x-select.native wire:model="serviceForm.departement_id" label="Departement de rattachement *"
                    :options="$this->departements->map(fn($departement) => ['label' => $departement->name, 'value' => $departement->id])->values()->all()" />
                <x-textarea wire:model="serviceForm.description" label="Description" rows="4" maxlength="2000" count />
                <flux:button variant="primary" color="sky" wire:click="saveService">Enregistrer le service</flux:button>
            </div>
        </div>

        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Etape 2</p>
            <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Ajouter une chambre</h2>
            <div class="mt-5 space-y-4">
                <x-select.native wire:model="chambreForm.hosp_service_id" label="Service *"
                    :options="$this->services->map(fn($service) => ['label' => $service->name, 'value' => $service->id])->values()->all()" />
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
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Etape 3</p>
            <h2 class="mt-2 text-xl font-black text-slate-900 dark:text-white">Ajouter un lit</h2>
            <div class="mt-5 space-y-4">
                <x-select.native wire:model="litForm.chambre_id" label="Chambre *"
                    :options="$this->chambres->map(fn($chambre) => ['label' => $chambre->name . ' - ' . ($chambre->service?->name ?: 'Service'), 'value' => $chambre->id])->values()->all()" />
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

    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Referentiel</p>
                <h2 class="mt-2 text-2xl font-black text-slate-900 dark:text-white">Organisation actuelle</h2>
            </div>
            <a href="{{ route('hospital.index') }}" wire:navigate
                class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:text-slate-200">
                Aller a la reception hospitaliere
            </a>
        </div>

        <div class="mt-6 space-y-4">
            @forelse ($this->services as $service)
                <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ $service->name }}</h3>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $service->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                    {{ $service->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                Departement: {{ $service->departement?->name ?: 'Non defini' }}
                            </p>
                            @if (filled($service->description))
                                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $service->description }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" wire:click="toggleService({{ $service->id }})"
                                class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 dark:border-slate-700 dark:text-slate-200">
                                {{ $service->is_active ? 'Desactiver' : 'Activer' }}
                            </button>
                            <a href="{{ route('hospital.configuration.show', $service->id) }}" wire:navigate
                                class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                                Ouvrir le detail
                            </a>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-3">
                        <div class="rounded-2xl bg-white px-4 py-3 text-sm dark:bg-slate-950/70">
                            <span class="text-slate-500 dark:text-slate-400">Chambres</span>
                            <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ $service->chambres->count() }}</p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 text-sm dark:bg-slate-950/70">
                            <span class="text-slate-500 dark:text-slate-400">Lits</span>
                            <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ $service->chambres->sum(fn($chambre) => $chambre->lits->count()) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 text-sm dark:bg-slate-950/70">
                            <span class="text-slate-500 dark:text-slate-400">Disponibles</span>
                            <p class="mt-1 text-xl font-black text-emerald-600">{{ $service->chambres->sum(fn($chambre) => $chambre->lits->where('statut', 'disponible')->count()) }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    Aucun service d hospitalisation n est encore configure.
                </div>
            @endforelse
        </div>
    </div>
</section>
