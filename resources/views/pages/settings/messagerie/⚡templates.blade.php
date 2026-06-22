<?php

use App\Enums\ClinicalMessageCategory;
use App\Models\ClinicalMessageTemplate;
use App\Models\Configs\Departement;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Modeles de messagerie'), Layout('layouts::app.other.support_tech')] class extends Component {
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $formName = '';

    public string $formCategory = 'suivi';

    public string $formSubject = '';

    public string $formBody = '';

    public ?int $formDepartementId = null;

    public bool $formIsActive = true;

    public int $formSortOrder = 0;

    #[Computed]
    public function templates(): Collection
    {
        $hopitalId = current_hopital_id();

        return ClinicalMessageTemplate::query()
            ->with('departement:id,name')
            ->where(function ($query) use ($hopitalId): void {
                $query->whereNull('hopital_id');

                if ($hopitalId !== null) {
                    $query->orWhere('hopital_id', $hopitalId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return ClinicalMessageCategory::options();
    }

    #[Computed]
    public function departementOptions(): Collection
    {
        return Departement::query()->orderBy('name')->get(['id', 'name']);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $template = ClinicalMessageTemplate::query()
            ->forContext(current_hopital_id())
            ->findOrFail($id);

        $this->editingId = $template->id;
        $this->formName = $template->name;
        $this->formCategory = $template->category->value;
        $this->formSubject = $template->subject;
        $this->formBody = $template->body;
        $this->formDepartementId = $template->departement_id;
        $this->formIsActive = $template->is_active;
        $this->formSortOrder = $template->sort_order;
        $this->showFormModal = true;
    }

    public function saveTemplate(): void
    {
        $validated = $this->validate([
            'formName' => ['required', 'string', 'max:120'],
            'formCategory' => ['required', 'in:' . implode(',', array_column(ClinicalMessageCategory::options(), 'value'))],
            'formSubject' => ['required', 'string', 'max:255'],
            'formBody' => ['required', 'string', 'max:15000'],
            'formDepartementId' => ['nullable', 'integer', 'exists:departements,id'],
            'formIsActive' => ['boolean'],
            'formSortOrder' => ['integer', 'min:0', 'max:9999'],
        ]);

        $payload = [
            'hopital_id' => current_hopital_id(),
            'departement_id' => $validated['formDepartementId'] ?: null,
            'category' => $validated['formCategory'],
            'name' => $validated['formName'],
            'subject' => $validated['formSubject'],
            'body' => $validated['formBody'],
            'is_active' => $validated['formIsActive'],
            'sort_order' => $validated['formSortOrder'],
        ];

        if ($this->editingId) {
            ClinicalMessageTemplate::query()
                ->where('hopital_id', current_hopital_id())
                ->whereKey($this->editingId)
                ->update($payload);
        } else {
            ClinicalMessageTemplate::query()->create($payload);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->templates);

        Flux::toast(variant: 'success', heading: 'Modele enregistre', text: 'Le modele de message a ete sauvegarde.');
    }

    public function toggleActive(int $id): void
    {
        $template = ClinicalMessageTemplate::query()
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);

        $template->update(['is_active' => ! $template->is_active]);
        unset($this->templates);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->formName = '';
        $this->formCategory = ClinicalMessageCategory::Suivi->value;
        $this->formSubject = '';
        $this->formBody = '';
        $this->formDepartementId = null;
        $this->formIsActive = true;
        $this->formSortOrder = 0;
        $this->resetValidation();
    }
};
?>

<section class="w-full space-y-6">
    <x-header_default
        title="Modeles de messagerie clinique"
        subtitle="Templates reutilisables pour les messages patients, par categorie et service"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Modeles messagerie', 'icon' => 'envelope'],
        ]"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                Nouveau modele
            </flux:button>
        </x-slot>
    </x-header_default>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <p class="text-sm text-slate-500">
                Variables disponibles :
                <code class="text-xs">{{ '{{patient_prenom}}' }}</code>,
                <code class="text-xs">{{ '{{patient_nom}}' }}</code>,
                <code class="text-xs">{{ '{{patient_nin}}' }}</code>,
                <code class="text-xs">{{ '{{medecin}}' }}</code>,
                <code class="text-xs">{{ '{{date_consultation}}' }}</code>,
                <code class="text-xs">{{ '{{examens_labo}}' }}</code>,
                <code class="text-xs">{{ '{{examens_imagerie}}' }}</code>
            </p>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($this->templates as $template)
                <div class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $template->name }}</p>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-slate-800">
                                {{ $template->category->label() }}
                            </span>
                            @if ($template->hopital_id === null)
                                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-bold text-violet-700">Global</span>
                            @endif
                            @if (! $template->is_active)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">Inactif</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $template->subject }}</p>
                        @if ($template->departement)
                            <p class="mt-1 text-xs text-slate-400">Service : {{ $template->departement->name }}</p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @if ($template->hopital_id === current_hopital_id())
                            <flux:button size="sm" variant="ghost" wire:click="openEditModal({{ $template->id }})">
                                Modifier
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $template->id }})">
                                {{ $template->is_active ? 'Desactiver' : 'Activer' }}
                            </flux:button>
                        @else
                            <span class="text-xs text-slate-400">Modele institutionnel</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Aucun modele disponible.</p>
            @endforelse
        </div>
    </div>

    <flux:modal wire:model.self="showFormModal" class="max-w-2xl">
        <form wire:submit="saveTemplate" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Modifier le modele' : 'Nouveau modele' }}</flux:heading>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-semibold">Nom du modele *</label>
                    <input type="text" wire:model="formName" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @error('formName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Categorie *</label>
                    <select wire:model="formCategory" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach ($this->categoryOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Service (optionnel)</label>
                    <select wire:model="formDepartementId" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <option value="">Tous les services</option>
                        @foreach ($this->departementOptions as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold">Objet *</label>
                <input type="text" wire:model="formSubject" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold">Corps du message *</label>
                <textarea wire:model="formBody" rows="8" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"></textarea>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="formIsActive" class="rounded" /> Actif
                </label>
                <div class="flex gap-2">
                    <flux:button type="button" variant="ghost" wire:click="$set('showFormModal', false)">Annuler</flux:button>
                    <flux:button type="submit" variant="primary">Enregistrer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</section>
