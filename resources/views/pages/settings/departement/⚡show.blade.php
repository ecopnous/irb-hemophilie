<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use App\Models\Configs\Service;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Département'), Layout('layouts::app.other.support_tech')] class extends Component {
    public Departement $departement;

    public string $activeTab = 'actes';

    public string $serviceName = '';
    public ?string $serviceDescription = null;

    public string $acteName = '';
    public $acteMontant;
    public $acteServiceId;
    public $acteUnite;
    public $acteMin;
    public $acteMax;
    public $acteHommeMin;
    public $acteHommeMax;
    public $acteFemmeMin;
    public $acteFemmeMax;

    public function mount(int $id): void
    {
        $this->loadDepartement($id);
    }

    protected function loadDepartement(int $id): void
    {
        $this->departement = Departement::query()
            ->with(['chef.departement'])
            ->withCount(['services', 'actes', 'users'])
            ->findOrFail($id);
    }

    #[Computed]
    public function chefAssigne(): bool
    {
        return (bool) $this->departement->user_id;
    }

    public function assignerChef(mixed $userId): void
    {
        if (is_array($userId)) {
            $userId = $userId['id'] ?? ($userId['value'] ?? 0);
        }

        $userId = (int) $userId;
        if ($userId < 1) {
            Flux::toast(variant: 'danger', heading: 'Assignation impossible', text: 'Sélection invalide.');

            return;
        }

        $this->departement->update(['user_id' => $userId]);
        $this->loadDepartement($this->departement->id);

        Flux::toast(
            variant: 'success',
            heading: 'Chef assigné',
            text: 'Dr ' . ($this->departement->chef?->name ?? 'sélectionné') . ' est maintenant chef de département.'
        );
    }

    public function retirerChef(): void
    {
        $this->departement->update(['user_id' => null]);
        $this->loadDepartement($this->departement->id);
        Flux::toast(variant: 'success', heading: 'Chef retiré', text: 'Le département n\'a plus de chef assigné.');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function resetServiceForm(): void
    {
        $this->serviceName = '';
        $this->serviceDescription = null;
        $this->resetValidation(['serviceName', 'serviceDescription']);
    }

    protected function resetActeForm(): void
    {
        $this->acteName = '';
        $this->acteMontant = null;
        $this->acteServiceId = null;
        $this->acteUnite = null;
        $this->acteMin = null;
        $this->acteMax = null;
        $this->acteHommeMin = null;
        $this->acteHommeMax = null;
        $this->acteFemmeMin = null;
        $this->acteFemmeMax = null;
        $this->resetValidation([
            'acteName', 'acteMontant', 'acteServiceId', 'acteUnite',
            'acteMin', 'acteMax', 'acteHommeMin', 'acteHommeMax', 'acteFemmeMin', 'acteFemmeMax',
        ]);
    }

    public function openServiceModal(): void
    {
        $this->resetServiceForm();
    }

    public function openActeModal(): void
    {
        $this->resetActeForm();
    }

    #[On('confirmed')]
    public function createServiceFromSelect(?string $term = null): void
    {
        if (blank($term)) {
            Flux::toast(variant: 'danger', heading: 'Service non créé', text: 'Entrez le nom du service.');

            return;
        }

        $service = Service::query()->create([
            'name' => $term,
            'departement_id' => $this->departement->id,
        ]);

        $this->acteServiceId = $service->id;
        $this->dispatch('pg:eventRefresh-departementServiceTable');
        $this->loadDepartement($this->departement->id);
        Flux::toast(variant: 'success', heading: 'Service créé', text: "Le service « {$term} » a été ajouté.");
    }

    public function saveService(): void
    {
        $validated = $this->validate([
            'serviceName' => ['required', 'string', 'max:255'],
            'serviceDescription' => ['nullable', 'string', 'max:500'],
        ]);

        Service::query()->create([
            'name' => $validated['serviceName'],
            'description' => $validated['serviceDescription'] ?: null,
            'departement_id' => $this->departement->id,
        ]);

        $this->resetServiceForm();
        $this->dispatch('pg:eventRefresh-departementServiceTable');
        $this->loadDepartement($this->departement->id);
        $this->dispatch('departement-service-saved');
        Flux::toast(variant: 'success', heading: 'Service enregistré', text: 'Le service a été ajouté au département.');
    }

    public function saveActe(): void
    {
        $rules = [
            'acteName' => ['required', 'string', 'max:500'],
            'acteMontant' => ['required', 'numeric', 'min:0'],
            'acteServiceId' => ['nullable', 'integer', 'exists:services,id'],
        ];

        if ($this->departement->ref === 'labo') {
            $rules = array_merge($rules, [
                'acteUnite' => ['nullable', 'string', 'max:500'],
                'acteMin' => ['nullable', 'numeric'],
                'acteMax' => ['nullable', 'numeric'],
                'acteHommeMin' => ['nullable', 'numeric'],
                'acteHommeMax' => ['nullable', 'numeric'],
                'acteFemmeMin' => ['nullable', 'numeric'],
                'acteFemmeMax' => ['nullable', 'numeric'],
            ]);
        }

        $validated = $this->validate($rules);

        Acte::query()->create([
            'name' => $validated['acteName'],
            'montant' => $validated['acteMontant'],
            'service_id' => $validated['acteServiceId'] ?? null,
            'departement_id' => $this->departement->id,
            'unite' => $validated['acteUnite'] ?? null,
            'min' => $validated['acteMin'] ?? 0,
            'max' => $validated['acteMax'] ?? 0,
            'homme_min' => $validated['acteHommeMin'] ?? null,
            'homme_max' => $validated['acteHommeMax'] ?? null,
            'femme_min' => $validated['acteFemmeMin'] ?? null,
            'femme_max' => $validated['acteFemmeMax'] ?? null,
        ]);

        $this->resetActeForm();
        $this->dispatch('pg:eventRefresh-departementActeTable');
        $this->dispatch('pg:eventRefresh-departementServiceTable');
        $this->loadDepartement($this->departement->id);
        $this->dispatch('departement-acte-saved');
        Flux::toast(variant: 'success', heading: 'Acte enregistré', text: 'L\'acte médical a été ajouté au catalogue.');
    }
};
?>

<div class="space-y-6">
    <x-header_default :title="ucfirst($departement->name)"
        :subtitle="'Référence ' . strtoupper($departement->ref) . ' · Configuration du département'"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings.departement.index', 'icon' => 'cog-6-tooth'],
            ['label' => 'Départements', 'link' => 'settings.departement.index', 'icon' => 'building-office-2'],
            ['label' => ucfirst($departement->name), 'icon' => 'swatch'],
        ]">
        <x-slot:actions>
            <flux:button href="{{ route('settings.departement.index') }}" variant="ghost" icon="arrow-left"
                wire:navigate>
                Retour
            </flux:button>
        </x-slot:actions>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-indigo-200 bg-indigo-50/80 p-5 shadow-sm dark:border-indigo-500/20 dark:bg-indigo-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-indigo-700 dark:text-indigo-300">Services</p>
            <p class="mt-3 text-3xl font-black text-indigo-900 dark:text-indigo-100">{{ $departement->services_count }}</p>
        </div>
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Actes</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $departement->actes_count }}</p>
        </div>
        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Personnel</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $departement->users_count }}</p>
        </div>
        <div @class([
            'rounded-3xl border p-5 shadow-sm',
            'border-emerald-200 bg-emerald-50/80 dark:border-emerald-500/20 dark:bg-emerald-500/10' => $this->chefAssigne,
            'border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-500/10' => ! $this->chefAssigne,
        ])>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Chef</p>
            <p class="mt-3 text-lg font-black text-slate-900 dark:text-white">
                {{ $this->chefAssigne ? trim(collect([$departement->chef?->name, $departement->chef?->prenom])->filter()->implode(' ')) : 'Non assigné' }}
            </p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
        <div class="space-y-4">
            <section
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                            <flux:icon.user-circle class="size-5" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Responsable</p>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Chef de département</h2>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    @if ($this->chefAssigne)
                        <div
                            class="rounded-2xl border border-emerald-200 bg-linear-to-br from-emerald-50 to-white p-5 dark:border-emerald-500/25 dark:from-emerald-500/10 dark:to-slate-900/40">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-sm font-black text-white">
                                    {{ $departement->chef->initials() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                                        Chef assigné
                                    </p>
                                    <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                                        {{ $departement->chef->name }} {{ $departement->chef->prenom }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        {{ $departement->chef->departement?->name ?? 'Département non renseigné' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div
                            class="rounded-2xl border border-dashed border-amber-300 bg-amber-50/50 p-5 text-center dark:border-amber-500/40 dark:bg-amber-500/5">
                            <flux:icon.exclamation-triangle class="mx-auto size-8 text-amber-500" />
                            <p class="mt-3 text-sm font-semibold text-amber-900 dark:text-amber-100">
                                Aucun chef assigné
                            </p>
                            <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-200/80">
                                Recherchez un médecin pour diriger ce département.
                            </p>
                        </div>
                    @endif

                    <x-command-palette id="chef-departement-search" :request="[
                        'url' => route('api.usersConnected'),
                        'method' => 'get',
                        'params' => [
                            'search' => '',
                            'hopital_id' => current_hopital_id(),
                            'departement_id' => $departement->id,
                        ],
                    ]" select="label:name|value:id|description:description|image:image"
                        x-on:select="$wire.assignerChef($event.detail.id ?? $event.detail.value)"
                        placeholder="Nom, matricule ou CNOM (min. 2 caractères)..." />

                    <flux:button class="w-full justify-center" variant="primary" icon="magnifying-glass" color="indigo"
                        x-on:click="$tsui.open.commandPalette('chef-departement-search')">
                        {{ $this->chefAssigne ? 'Changer de chef' : 'Rechercher un chef' }}
                    </flux:button>

                    @if ($this->chefAssigne)
                        <flux:button class="w-full justify-center" variant="ghost" icon="user-minus"
                            wire:click="retirerChef">
                            Retirer le chef
                        </flux:button>
                    @endif
                </div>
            </section>
            @if (filled($departement->description))
                <section
                    class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Description</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ $departement->description }}
                    </p>
                </section>
            @endif
        </div>

        <section
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Catalogue</p>
                        <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Services & actes médicaux</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="setTab('actes')"
                            @class([
                                'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' => $activeTab === 'actes',
                            ])>
                            Actes ({{ $departement->actes_count }})
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="setTab('services')"
                            @class([
                                'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' => $activeTab === 'services',
                            ])>
                            Services ({{ $departement->services_count }})
                        </flux:button>
                    </div>
                </div>
            </div>

            <div class="space-y-4 p-4 md:p-5">
                <div class="flex flex-wrap justify-end gap-2">
                    @if ($activeTab === 'actes')
                        <flux:button size="sm" variant="primary" color="emerald" icon="plus"
                            wire:click="openActeModal" x-on:click="$tsui.open.modal('departement-acte-modal')">
                            Nouvel acte
                        </flux:button>
                    @else
                        <flux:button size="sm" variant="primary" color="sky" icon="plus"
                            wire:click="openServiceModal" x-on:click="$tsui.open.modal('departement-service-modal')">
                            Nouveau service
                        </flux:button>
                    @endif
                </div>

                @if ($activeTab === 'actes')
                    <livewire:departement-acte-table :departement-id="$departement->id" :key="'actes-' . $departement->id" />
                @else
                    <livewire:departement-service-table :departement-id="$departement->id" :key="'services-' . $departement->id" />
                @endif
            </div>
        </section>
    </div>

    <x-modal id="departement-service-modal" title="Nouveau service" size="3xl" center persistent
        x-on:departement-service-saved.window="$tsui.close.modal('departement-service-modal')">
        <div class="space-y-4">
            <x-input label="Nom du service *" wire:model="serviceName" placeholder="Ex. Hématologie, Imagerie..." />
            <x-textarea wire:model="serviceDescription" label="Description" rows="3" maxlength="500" count />
        </div>
        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('departement-service-modal')">Annuler
                </flux:button>
                <flux:button variant="primary" color="sky" wire:click="saveService">Enregistrer</flux:button>
            </div>
        </x-slot:footer>
    </x-modal>

    <x-modal id="departement-acte-modal" title="Nouvel acte médical" size="5xl" center persistent
        x-on:departement-acte-saved.window="$tsui.close.modal('departement-acte-modal')">
        <div class="space-y-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-input label="Nom de l'acte *" wire:model="acteName" class="md:col-span-2" />
                <x-number step="0.01" label="Montant *" wire:model="acteMontant" />
                <x-select.styled label="Service (optionnel)" wire:model.live="acteServiceId"
                    :request="['url' => route('api.services'), 'params' => ['departement' => $departement->id]]"
                    select="label:name|value:id" placeholder="Choisir ou créer">
                    <x-slot:after>
                        <div class="mb-2 flex justify-center px-2">
                            <x-button x-on:click="show = false; $dispatch('confirmed', { term: search })">
                                <span x-html="`Créer le service <b>${search}</b>`"></span>
                            </x-button>
                        </div>
                    </x-slot:after>
                </x-select.styled>
            </div>

            @if ($departement->ref === 'labo')
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <p class="mb-4 text-xs font-black uppercase tracking-[0.2em] text-slate-400">Valeurs de référence
                        laboratoire</p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-input label="Unité" wire:model="acteUnite" placeholder="Ex. g/L" />
                        <x-number label="Valeur min." wire:model="acteMin" />
                        <x-number label="Valeur max." wire:model="acteMax" />
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-number label="Homme (min)" wire:model="acteHommeMin" />
                        <x-number label="Homme (max)" wire:model="acteHommeMax" />
                        <x-number label="Femme (min)" wire:model="acteFemmeMin" />
                        <x-number label="Femme (max)" wire:model="acteFemmeMax" />
                    </div>
                </div>
            @endif
        </div>
        <x-slot:footer>
            <div class="flex w-full justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$tsui.close.modal('departement-acte-modal')">Annuler
                </flux:button>
                <flux:button variant="primary" color="emerald" wire:click="saveActe">Enregistrer l'acte</flux:button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
