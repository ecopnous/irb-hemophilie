<?php

use App\Models\facturation\FinanceClient;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Clients'), Layout('layouts::app.other.facturation')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public string $statusFilter = 'active';

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $formName = '';
    public string $formType = 'particulier';
    public ?string $formEmail = null;
    public ?string $formPhone = null;
    public ?string $formAddress = null;
    public ?string $formTaxId = null;
    public bool $formIsActive = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function baseQuery()
    {
        return FinanceClient::query()
            ->where('hopital_id', current_hopital_id())
            ->withCount('documents');
    }

    #[Computed]
    public function stats(): array
    {
        $clients = $this->baseQuery()->get();

        return [
            'total' => $clients->count(),
            'particulier' => $clients->where('type', 'particulier')->count(),
            'institution' => $clients->where('type', 'institution')->count(),
            'active' => $clients->where('is_active', true)->count(),
        ];
    }

    #[Computed]
    public function rows()
    {
        return $this->baseQuery()
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('tax_id', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(12);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $client = $this->baseQuery()->findOrFail($id);

        $this->editingId = $client->id;
        $this->formName = $client->name;
        $this->formType = $client->type;
        $this->formEmail = $client->email;
        $this->formPhone = $client->phone;
        $this->formAddress = $client->address;
        $this->formTaxId = $client->tax_id;
        $this->formIsActive = (bool) $client->is_active;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->formName = '';
        $this->formType = 'particulier';
        $this->formEmail = null;
        $this->formPhone = null;
        $this->formAddress = null;
        $this->formTaxId = null;
        $this->formIsActive = true;
        $this->resetValidation();
    }

    public function saveClient(): void
    {
        $validated = $this->validate([
            'formName' => ['required', 'string', 'max:255'],
            'formType' => ['required', 'in:particulier,institution'],
            'formEmail' => ['nullable', 'email', 'max:255'],
            'formPhone' => ['nullable', 'string', 'max:50'],
            'formAddress' => ['nullable', 'string', 'max:500'],
            'formTaxId' => ['nullable', 'string', 'max:100'],
            'formIsActive' => ['boolean'],
        ], [
            'formName.required' => 'Le nom du client est obligatoire.',
        ]);

        $payload = [
            'name' => trim($validated['formName']),
            'type' => $validated['formType'],
            'email' => filled($validated['formEmail']) ? trim($validated['formEmail']) : null,
            'phone' => filled($validated['formPhone']) ? trim($validated['formPhone']) : null,
            'address' => filled($validated['formAddress']) ? trim($validated['formAddress']) : null,
            'tax_id' => filled($validated['formTaxId']) ? trim($validated['formTaxId']) : null,
            'is_active' => $validated['formIsActive'],
        ];

        if ($this->editingId) {
            $this->baseQuery()->findOrFail($this->editingId)->update($payload);
            session()->flash('message', 'Client mis a jour avec succes.');
        } else {
            FinanceClient::query()->create($payload + [
                'hopital_id' => current_hopital_id(),
            ]);
            session()->flash('message', 'Client cree avec succes.');
        }

        $this->cancelForm();
    }

    public function toggleActive(int $id): void
    {
        $client = $this->baseQuery()->findOrFail($id);
        $client->update(['is_active' => ! $client->is_active]);

        session()->flash('message', $client->is_active
            ? 'Client reactive.'
            : 'Client desactive.');
    }
};
?>

<div class="space-y-5 p-6">
    @if (session()->has('message'))
        <div
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <section
        class="overflow-hidden rounded-4xl border border-violet-100 bg-linear-to-br from-white via-violet-50/70 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="space-y-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Facturation', 'link' => route('facturation.index'), 'icon' => 'banknotes'],
                        ['label' => 'Clients', 'icon' => 'user-group'],
                    ]" />
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-violet-600 dark:text-violet-300">
                        Relation commerciale
                    </p>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Clients</h1>
                    <p class="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                        Annuaire des clients externes pour vos devis et factures hors dossier patient.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button wire:click="openCreate" variant="primary" color="violet" icon="plus">
                        Nouveau client
                    </flux:button>
                    <a href="{{ route('facturation.documents.create', ['type' => 'devis']) }}" wire:navigate>
                        <flux:button variant="ghost" icon="document-plus">Nouveau devis</flux:button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    class="rounded-2xl border border-violet-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Total</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['total'] }}</p>
                </div>
                <div
                    class="rounded-2xl border border-violet-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Particuliers</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['particulier'] }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-violet-100 bg-white/85 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Institutions</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->stats['institution'] }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-emerald-100 bg-emerald-50/90 px-4 py-3 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <p class="text-xs uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Actifs</p>
                    <p class="mt-1 text-2xl font-black text-emerald-900 dark:text-emerald-100">
                        {{ $this->stats['active'] }}</p>
                </div>
            </div>
        </div>
    </section>

    @if ($showForm)
        <section class="rounded-3xl border border-violet-200 bg-white p-5 shadow-sm dark:border-violet-500/20 dark:bg-slate-950/70">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600 dark:text-violet-300">
                        {{ $editingId ? 'Edition' : 'Creation' }}
                    </p>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">
                        {{ $editingId ? 'Modifier le client' : 'Nouveau client' }}
                    </h2>
                </div>
                <flux:button wire:click="cancelForm" variant="ghost" icon="x-mark">Fermer</flux:button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-input wire:model="formName" label="Nom / Raison sociale *" />
                <x-select.styled wire:model="formType" label="Type *" :options="[
                    ['label' => 'Particulier', 'value' => 'particulier'],
                    ['label' => 'Institution', 'value' => 'institution'],
                ]" select="label:label|value:value" />
                <x-input wire:model="formPhone" label="Telephone" placeholder="+243..." />
                <x-input wire:model="formEmail" label="Email" type="email" />
                <x-input wire:model="formTaxId" label="NIF / ID fiscal" />
                <x-select.styled wire:model="formIsActive" label="Statut" :options="[
                    ['label' => 'Actif', 'value' => true],
                    ['label' => 'Inactif', 'value' => false],
                ]" select="label:label|value:value" />
                <div class="md:col-span-2">
                    <x-textarea wire:model="formAddress" label="Adresse" rows="2"
                        placeholder="Adresse complete, ville, pays..." />
                </div>
            </div>

            @error('formName')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="mt-4 flex justify-end gap-2">
                <flux:button wire:click="cancelForm" variant="ghost">Annuler</flux:button>
                <flux:button wire:click="saveClient" variant="primary" color="violet" icon="check">
                    {{ $editingId ? 'Enregistrer' : 'Creer le client' }}
                </flux:button>
            </div>
        </section>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="grid gap-3 md:grid-cols-3">
            <x-input wire:model.live.debounce.300ms="search" label="Recherche"
                placeholder="Nom, email, telephone, NIF..." />
            <x-select.styled wire:model.live="typeFilter" label="Type" :options="[
                ['label' => 'Tous', 'value' => ''],
                ['label' => 'Particuliers', 'value' => 'particulier'],
                ['label' => 'Institutions', 'value' => 'institution'],
            ]" select="label:label|value:value" />
            <x-select.styled wire:model.live="statusFilter" label="Statut" :options="[
                ['label' => 'Actifs', 'value' => 'active'],
                ['label' => 'Inactifs', 'value' => 'inactive'],
                ['label' => 'Tous', 'value' => ''],
            ]" select="label:label|value:value" />
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->rows as $client)
            <article wire:key="client-{{ $client->id }}"
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-violet-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-950/70 dark:hover:border-violet-500/30">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-lg font-black text-slate-900 dark:text-white"
                                title="{{ $client->name }}">
                                {{ $client->name }}
                            </h3>
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $client->type === 'institution' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' }}">
                                {{ $client->typeLabel() }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ $client->documents_count }} document(s) ·
                            {{ $client->is_active ? 'Actif' : 'Inactif' }}
                        </p>
                    </div>
                    <span
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl {{ $client->is_active ? 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                        <flux:icon.user-group class="size-5" />
                    </span>
                </div>

                <dl class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Telephone</dt>
                        <dd class="text-right font-medium">{{ $client->phone ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Email</dt>
                        <dd class="truncate text-right font-medium" title="{{ $client->email }}">
                            {{ $client->email ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">NIF</dt>
                        <dd class="text-right font-medium">{{ $client->tax_id ?: '—' }}</dd>
                    </div>
                    @if ($client->address)
                        <div>
                            <dt class="text-slate-400">Adresse</dt>
                            <dd class="mt-1 text-xs leading-5">{{ $client->address }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <flux:button size="sm" wire:click="openEdit({{ $client->id }})" variant="ghost"
                        icon="pencil-square">Modifier</flux:button>
                    <flux:button size="sm" wire:click="toggleActive({{ $client->id }})" variant="ghost"
                        icon="{{ $client->is_active ? 'pause' : 'play' }}">
                        {{ $client->is_active ? 'Desactiver' : 'Reactiver' }}
                    </flux:button>
                    <a href="{{ route('facturation.documents.create', ['type' => 'devis', 'client' => $client->id]) }}"
                        wire:navigate class="ml-auto">
                        <flux:button size="sm" variant="primary" color="violet" icon="document-plus">
                            Devis
                        </flux:button>
                    </a>
                </div>
            </article>
        @empty
            <div
                class="col-span-full rounded-3xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center dark:border-slate-700 dark:bg-slate-900/40">
                <flux:icon.user-group class="mx-auto size-10 text-slate-400" />
                <p class="mt-3 text-base font-bold text-slate-700 dark:text-slate-200">Aucun client trouve</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Creez votre premier client pour emettre des devis et factures.
                </p>
                <flux:button class="mt-4" wire:click="openCreate" variant="primary" color="violet" icon="plus">
                    Ajouter un client
                </flux:button>
            </div>
        @endforelse
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950/70">
        {{ $this->rows->links() }}
    </div>
</div>
