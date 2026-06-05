<?php

use App\Models\Configs\Assurance;
use App\Models\Configs\Categorisation;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
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
    public $categories = [];

    public function mount(): void
    {
        $this->categories = Categorisation::query()->orderBy('name')->get();
        $last = Assurance::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;
        $this->reference = 'A-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function updatedLogo(): void
    {
        $this->validateOnly('logo', [
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        Flux::toast('Image importer avec succes', duration: 1000, variant: 'success');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'reference' => ['required', 'string', 'max:255', 'unique:assurances,reference'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:assurance,entreprise,organisation,partenaire,particulier'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'categorisation_id' => ['nullable', 'integer', 'exists:categorisations,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
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

        Flux::toast(variant: 'success', heading: 'Création réussie', text: 'Assurance enregistree avec succes.');
        $this->redirectRoute('settings.assurance.show', ['id' => $assurance->id], navigate: true);
    }
};
?>

<section class="w-full">
    <flux:heading class="sr-only">Nouvelle assurance</flux:heading>
    <x-header_default :title="__('Nouvelle Assurance')" subtitle="Renseigner les informations pour ajouter une nouvelle assurance"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Assurance', 'icon' => 'shield-check'],
        ]" />

    <x-card loarding>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-3">
                <x-input wire:model="reference" label="Reference *" readonly />
                <x-input wire:model="name" label="Nom *" clearable />
                <x-select.native wire:model.live="type" label="Type *" :options="[
                    ['label' => 'Assurance', 'value' => 'assurance'],
                    ['label' => 'Entreprise', 'value' => 'entreprise'],
                    ['label' => 'Organisation', 'value' => 'organisation'],
                    ['label' => 'Partenaire', 'value' => 'partenaire'],
                    ['label' => 'Particulier', 'value' => 'particulier'],
                ]" />
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Logo de l'assurance</label>
                    <input type="file" wire:model.live="logo" accept="image/*"
                        class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" />
                    <div wire:loading wire:target="logo" class="text-xs text-zinc-500">
                        Telechargement du logo en cours...
                    </div>
                </div>
                <x-input wire:model="email" label="Email" clearable />
                <x-select.styled wire:model.live="categorisation_id" label="Categorisation" :placeholders="[
                    'default' => 'Selectionner une categorisation',
                    'search' => 'Entrez le nom de la categorisation',
                    'empty' => 'Aucune categorisation trouvee',
                ]"
                    lazy="10" :options="$this->categories" select="label:name|value:id" searchable />
            </div>

            @if ($logo)
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                    <p class="mb-3 text-sm font-medium text-zinc-700">Apercu du logo</p>
                    <img src="{{ $logo->temporaryUrl() }}" alt="Apercu logo assurance"
                        class="h-24 w-24 rounded-xl object-cover ring-1 ring-zinc-200" />
                </div>
            @endif

            <div class="space-y-8">
                <x-textarea wire:model="description" label="Description" maxlength="500" count />
            </div>

            <div class="pt-2">
                <flux:button type="submit" icon="save" variant="primary" color="blue">
                    Enregistrer l'assurance
                </flux:button>
            </div>
        </form>
    </x-card>
</section>
