<?php
use TallStackUi\Traits\Interactions;
use App\Models\Configs\Categorisation;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Flux\Flux;

new #[Title('Categorisation'), Layout('layouts::app.other.support_tech')] class extends Component {
    use Interactions;

    public string $name = '';
    public int $pourcentage = 0;
    public string $description = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'pourcentage' => ['required', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Categorisation::create($validated);

        $this->reset(['name', 'pourcentage', 'description']);
        $this->pourcentage = 0;
        Flux::toast(variant: 'success', heading: 'Création réussie', text: 'Categorie enregistree avec succes.');
    }
};
?>

<section class="w-full">
    <x-header_default :title="__('Nouvelle Categorie')" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
        ['label' => 'categorisation', 'icon' => 'squares-plus'],
    ]" />

    <x-card header="Renseigne les informations ci-dessous pour ajouter une categorie" loading>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="flex flex-row gap-x-4">
                <div class="flex-1">
                    <x-input wire:model="name" label="Nom *" hint="Nom de la categories" clearable />
                </div>
                <div class="flex-1">
                    <x-number wire:model="pourcentage" label="Pourcentage *" min="0" max="100"
                        centralized />
                </div>
            </div>

            <div class="space-y-8">
                <x-textarea wire:model="description" label="Description" maxlength="500" count />
            </div>

            <div class="pt-4 flex justify-end">
                <div>
                    <flux:button type="submit" icon="save" variant="primary" color="blue">
                        Enregistrer la catégorie
                    </flux:button>
                </div>
            </div>
        </form>
    </x-card>
</section>
