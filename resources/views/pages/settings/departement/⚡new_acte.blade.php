<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Service;
use App\Models\Configs\Departement;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Flux\Flux;
use TallStackUi\Traits\Interactions;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Title('Nouvel acte médical'), Layout('layouts::app.other.support_tech')] class extends Component {
    use Interactions;

    public $departement;
    public $name, $montant;
    public $service_id;
    public $unite, $min, $max, $homme_min, $homme_max, $femme_min, $femme_max;

    public function mount($id)
    {
        $this->departement = Departement::findOrFail($id);
    }

    // Cette méthode écoute l'événement 'confirmed' envoyé par le JS
    #[On('confirmed')]
    public function createNewService($term)
    {
        if (empty($term)) {
            Flux::toast(variant: 'danger', heading: 'Service non créer.', text: 'Entrez le nom du service pour créer un nouveau');
            return;
        }

        // 1. Création en base de données
        $newService = Service::create([
            'name' => $term,
            'departement_id' => $this->departement->id,
        ]);

        // 2. Notification de succès (Optionnel avec TallStackUi)
        Flux::toast(variant: 'success', heading: 'Création service.', text: 'Le Service ' . $term . ' a été créer avec succes.');

        // 3. On sélectionne automatiquement le nouvel élément
        $this->service_id = $newService->id;
    }

    public function save(): void
    {
        try {
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:500'],
                'unite' => ['nullable', 'string', 'max:500'],
                'min' => ['nullable', 'numeric'],
                'max' => ['nullable', 'numeric'],
                'homme_min' => ['nullable', 'numeric'],
                'homme_max' => ['nullable', 'numeric'],
                'femme_min' => ['nullable', 'numeric'],
                'femme_max' => ['nullable', 'numeric'],
                'montant' => ['required', 'numeric', 'min:0'],
                'service_id' => ['nullable', 'integer', 'exists:services,id'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher le toast d'erreur
            $this->dialog()->error('Invalide', 'Veuillez corriger les erreurs du formulaire.')->send();
            throw $e;
        }

        $validated['departement_id'] = $this->departement->id;
        Acte::query()->create($validated);
        $this->dialog()->question('Acte enregistré avec succes', 'Quel est votre prochaine action ?')->confirm('Afficher les actes', 'confirmed')->cancel('Voir détail', 'cancelled')->send();
    }

    public function confirmed(string $message): void
    {
        $this->redirectRoute('settings.departement.show', ['id' => $this->departement->id], navigate: true);
    }

    public function cancelled(string $message): void
    {
        $this->redirectRoute('settings.departement.show', ['id' => $this->departement->id], navigate: true);
    }
};
?>

<div>
    <div class="max-w-7xl mx-auto mb-8">
        <div>
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Support technique', 'link' => 'settings.departement.index', 'icon' => 'cog-6-tooth'],
                [
                    'label' => 'Département',
                    'link' => 'settings/departement/show/{{ $departement->id }}',
                    'icon' => 'swatch',
                ],
                ['label' => 'Nouvel acte'],
            ]" />
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
                Nouvel acte médical
            </h1>
            <p class="text-sm font-mono text-gray-500 dark:text-slate-400 mt-1">{{ ucwords($departement->name) }}</p>
        </div>
    </div>
    <div class="max-w-7xl mx-auto mb-8">
        <x-card header="Informations sur l'acte médical ::: {{ ucwords($departement->name) }}" loading>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <x-input label="Nom de l'acte *" wire:model="name" placeholder="Entrez le nom de l'acte" />
                <x-number step="0.1" label="Montant" wire:model="montant" placeholder="Entrez le montant" />
                <x-select.styled label="Service (optionel)" wire:model.live="service_id" :request="['url' => route('api.services'), 'params' => ['departement' => $departement->id]]"
                    select="label:name|value:id" placeholder="Choisir ou créer" hint='Entrez le nom et appui sur créer'>
                    <x-slot:after>
                        <div class="px-2 mb-2 flex justify-center items-center">
                            <x-button x-on:click="show = false; $dispatch('confirmed', { term: search })">
                                <span x-html="`Créer le service <b>${search}</b>`"></span>
                            </x-button>
                        </div>
                    </x-slot:after>
                </x-select.styled>
            </div>
            @if ($departement->ref == 'labo')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <x-input label="Unité de reference" wire:model="unite" placeholder="Ex: kg" />
                    <x-number label="Valeur Minimum" wire:model="min" />
                    <x-number label="Valeur Maximum" wire:model="max" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <x-number label="Homme (Valeur Max)" wire:model="homme_max" />
                    <x-number label="Homme (Valeur Min)" wire:model="homme_min" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <x-number label="Femme (Valeur Min)" wire:model="femme_min" />
                    <x-number label="Femme (Valeur Max)" wire:model="femme_max" />
                </div>
            @endif
            <div class="flex justify-end">
                <flux:button wire:click="save" variant="primary" color="indigo" icon="save">
                    Sauvegarder l'acte
                </flux:button>
            </div>
        </x-card>
    </div>
</div>
