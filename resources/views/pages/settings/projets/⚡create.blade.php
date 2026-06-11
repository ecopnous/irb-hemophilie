<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Projet;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Nouveau projet'), Layout('layouts::app.other.support_tech')] class extends Component {
    use Interactions;

    public string $name = '';
    public string $reference = '';
    public string $description = '';
    public ?int $assurance_id = null;

    public function mount(): void
    {
        $last = Projet::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;
        $this->reference = 'P-' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    #[Computed]
    public function assurances(): Collection
    {
        return Assurance::query()
            ->where(function ($q) {
                $q->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        if ($this->assurances->isEmpty()) {
            $this->toast()
                ->error('Assurance requise', 'Creez d\'abord une assurance avant d\'ajouter un projet.')
                ->send();

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255', 'unique:projets,reference'],
            'description' => ['nullable', 'string', 'max:1000'],
            'assurance_id' => ['required', 'integer', 'exists:assurances,id'],
        ], [
            'assurance_id.required' => 'L\'assurance est obligatoire pour creer un projet.',
        ]);

        $projet = Projet::query()->create([
            'name' => $validated['name'],
            'reference' => $validated['reference'] ?: null,
            'description' => $validated['description'] ?: null,
            'assurance_id' => $validated['assurance_id'],
        ]);

        $this->toast()->success('Projet enregistré', 'Le projet a bien été ajouté.')->send();

        $this->redirectRoute('settings.projet.show', ['id' => $projet->id], navigate: true);
    }
};
?>

<section class="w-full">
    <div class="mb-6">
        <x-breadcrumbs :items="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'icon' => 'cog-6-tooth'],
        ]" />
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
            Création du projet ou Campagne
        </h1>
    </div>

    @if ($this->assurances->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <p class="font-semibold">Assurance obligatoire</p>
            <p class="mt-1">Vous devez d'abord enregistrer une assurance avant de pouvoir creer un projet ou une campagne.</p>
            <x-button href="{{ route('settings.assurance.create') }}" wire:navigate class="mt-3" icon="plus">
                Creer une assurance
            </x-button>
        </div>
    @else
        <form wire:submit.prevent="save">
            <x-card>
                <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <flux:heading size="lg">Détails du projet</flux:heading>
                        <flux:subheading class="mt-1">
                            Renseignez l'assurance porteuse, le nom, la référence et la description du projet.
                        </flux:subheading>
                    </div>
                    <flux:badge color="sky" inset>Création</flux:badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-select.styled
                            wire:model="assurance_id"
                            label="Assurance *"
                            placeholder="Selectionnez l'assurance porteuse du projet"
                            :options="$this->assurances->map(fn ($a) => [
                                'label' => $a->name . ($a->reference ? ' (' . $a->reference . ')' : ''),
                                'value' => $a->id,
                            ])->values()->all()"
                            select="label:label|value:value"
                            searchable
                        />
                        @error('assurance_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-input wire:model="name" label="Nom du projet *" placeholder="Ex: Campagne de vaccination" clearable />
                    <x-input wire:model="reference" label="Référence du projet" placeholder="P-00001" readonly />
                </div>

                <div class="mt-4">
                    <x-textarea wire:model="description" label="Description" maxlength="1000" count
                        placeholder="Décrivez l'objectif, la cible et la durée du projet..." />
                </div>

                <div class="flex flex-col gap-3 mt-6">
                    <flux:button type="submit" variant="primary" color="sky" icon="save"
                        class="w-full justify-center">
                        Enregistrer le projet
                    </flux:button>
                    <x-button href="{{ route('settings.projet.index') }}" wire:navigate class="justify-center">
                        Retour à la liste
                    </x-button>
                </div>
            </x-card>
        </form>
    @endif
</section>
