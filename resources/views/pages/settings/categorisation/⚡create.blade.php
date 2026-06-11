<?php

use App\Models\Configs\Categorisation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Nouvelle categorisation'), Layout('layouts::app.other.support_tech')] class extends Component {
    use Interactions;

    public string $name = '';
    public int $pourcentage = 0;
    public string $description = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'pourcentage' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'pourcentage.min' => 'Le pourcentage de prise en charge doit etre superieur a 0.',
            'pourcentage.required' => 'Le pourcentage de prise en charge est obligatoire.',
        ]);

        $categorisation = Categorisation::query()->create([
            'name' => $validated['name'],
            'pourcentage' => $validated['pourcentage'],
            'description' => $validated['description'] ?: null,
        ]);

        $this->toast()->success('Categorie enregistree', 'La categorisation a bien ete ajoutee.')->send();

        $this->redirectRoute('settings.categorisation.show', ['id' => $categorisation->id], navigate: true);
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Nouvelle categorisation</flux:heading>

    <x-header_default
        title="Nouvelle categorisation"
        subtitle="Definissez un niveau de prise en charge pour les assurances et paquets de soins"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
            ['label' => 'Categorisations', 'link' => 'settings/categorisation', 'icon' => 'squares-plus'],
            ['label' => 'Creation', 'icon' => 'plus'],
        ]"
    />

    <form wire:submit.prevent="save">
        <x-card>
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <flux:heading size="lg">Details de la categorie</flux:heading>
                    <flux:subheading class="mt-1">
                        Le nom et le pourcentage de prise en charge sont obligatoires.
                    </flux:subheading>
                </div>
                <flux:badge color="violet" inset>Creation</flux:badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-input wire:model="name" label="Nom de la categorie *" placeholder="Ex: Prise en charge A" clearable />
                <x-number wire:model="pourcentage" label="Pourcentage de prise en charge *" min="1" max="100" centralized />
            </div>

            @error('name')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('pourcentage')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror

            <div class="mt-4 rounded-2xl border border-violet-200 bg-violet-50/80 px-4 py-3 text-sm text-violet-900 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-100">
                Ce pourcentage sera utilise pour les assurances et les paquets de soins rattaches a cette categorie.
            </div>

            <div class="mt-4">
                <x-textarea wire:model="description" label="Description" maxlength="500" count
                    placeholder="Precisez les conditions, exclusions ou le public cible..." />
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <flux:button type="submit" icon="save" variant="primary" color="violet" class="w-full justify-center md:w-auto">
                    Enregistrer la categorie
                </flux:button>
                <x-button href="{{ route('settings.categorisation.index') }}" wire:navigate class="justify-center md:w-fit">
                    Retour a la liste
                </x-button>
            </div>
        </x-card>
    </form>
</section>
