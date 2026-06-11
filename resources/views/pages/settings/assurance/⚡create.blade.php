<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Categorisation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;
use Flux\Flux;

new #[Title('Nouvelle assurance'), Layout('layouts::app.other.support_tech')] class extends Component {
    use Interactions;
    use WithFileUploads;

    public string $reference = '';
    public string $name = '';
    public string $type = 'assurance';
    public string $email = '';
    public string $description = '';
    public ?int $categorisation_id = null;
    public $logo = null;

    public function mount(): void
    {
        $last = Assurance::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;
        $this->reference = 'A-' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    #[Computed]
    public function categories(): Collection
    {
        return Categorisation::query()->orderBy('name')->get();
    }

    public function updatedLogo(): void
    {
        $this->validateOnly('logo', [
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        Flux::toast('Image importee avec succes', duration: 1000, variant: 'success');
    }

    public function save(): void
    {
        if ($this->categories->isEmpty()) {
            $this->toast()
                ->error('Categorisation requise', 'Creez d\'abord une categorisation avant d\'ajouter une assurance.')
                ->send();

            return;
        }

        $validated = $this->validate([
            'reference' => ['required', 'string', 'max:255', 'unique:assurances,reference'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:assurance,entreprise,organisation,partenaire,particulier'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'categorisation_id' => ['required', 'integer', 'exists:categorisations,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ], [
            'categorisation_id.required' => 'La categorisation est obligatoire pour creer une assurance.',
        ]);

        $logoPath = $this->logo ? $this->logo->storePublicly('assurances', 'public') : null;

        $assurance = Assurance::query()->create([
            'reference' => $validated['reference'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'email' => $validated['email'] ?: null,
            'description' => $validated['description'] ?: null,
            'categorisation_id' => $validated['categorisation_id'],
            'logo' => $logoPath,
        ]);

        $this->toast()->success('Assurance enregistree', 'L\'assurance a bien ete ajoutee.')->send();

        $this->redirectRoute('settings.assurance.show', ['id' => $assurance->id], navigate: true);
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Nouvelle assurance</flux:heading>

    <x-header_default
        title="Nouvelle assurance"
        subtitle="Enregistrez un partenaire payeur avec sa categorisation de prise en charge"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Assurances', 'link' => 'settings/assurance', 'icon' => 'shield-check'],
            ['label' => 'Creation', 'icon' => 'plus'],
        ]"
    />

    @if ($this->categories->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <p class="font-semibold">Categorisation obligatoire</p>
            <p class="mt-1">Vous devez d'abord enregistrer une categorisation avant de pouvoir creer une assurance.</p>
            <x-button href="{{ route('settings.categorisation.create') }}" wire:navigate class="mt-3" icon="plus">
                Creer une categorisation
            </x-button>
        </div>
    @else
        <form wire:submit.prevent="save">
            <x-card>
                <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <flux:heading size="lg">Details de l'assurance</flux:heading>
                        <flux:subheading class="mt-1">
                            Renseignez la categorisation, l'identite et les coordonnees du partenaire payeur.
                        </flux:subheading>
                    </div>
                    <flux:badge color="blue" inset>Creation</flux:badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-select.styled
                            wire:model="categorisation_id"
                            label="Categorisation *"
                            placeholder="Selectionnez la categorisation de prise en charge"
                            :options="$this->categories->map(fn ($c) => [
                                'label' => $c->name . ' (' . $c->pourcentage . '%)',
                                'value' => $c->id,
                            ])->values()->all()"
                            select="label:label|value:value"
                            searchable
                        />
                        @error('categorisation_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-input wire:model="reference" label="Reference *" readonly />
                    <x-input wire:model="name" label="Nom *" placeholder="Ex: CNSS, Mutuelle sante..." clearable />

                    <x-select.native wire:model.live="type" label="Type *" :options="[
                        ['label' => 'Assurance', 'value' => 'assurance'],
                        ['label' => 'Entreprise', 'value' => 'entreprise'],
                        ['label' => 'Organisation', 'value' => 'organisation'],
                        ['label' => 'Partenaire', 'value' => 'partenaire'],
                        ['label' => 'Particulier', 'value' => 'particulier'],
                    ]" />

                    <x-input wire:model="email" label="Email" placeholder="contact@assurance.cd" clearable />
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-[220px_1fr]">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Logo</label>
                        <input type="file" wire:model.live="logo" accept="image/*"
                            class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        <div wire:loading wire:target="logo" class="text-xs text-zinc-500">
                            Telechargement en cours...
                        </div>
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Apercu logo"
                                class="mt-2 h-24 w-24 rounded-xl object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" />
                        @endif
                    </div>

                    <x-textarea wire:model="description" label="Description" maxlength="500" count
                        placeholder="Decrivez le partenaire, les conditions ou le perimetre de couverture..." />
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <flux:button type="submit" icon="save" variant="primary" color="blue" class="w-full justify-center md:w-auto">
                        Enregistrer l'assurance
                    </flux:button>
                    <x-button href="{{ route('settings.assurance.index') }}" wire:navigate class="justify-center md:w-fit">
                        Retour a la liste
                    </x-button>
                </div>
            </x-card>
        </form>
    @endif
</section>
