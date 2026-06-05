<?php

use App\Models\prescription\Medicament;
use App\Models\prescription\Pharmacie;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Medicaments'), Layout('layouts::app.other.pharmacy')] class extends Component {
    public string $reference = '';
    public string $name = '';
    public string $classe = '';
    public ?string $fournisseur = null;
    public ?string $fabricant = null;
    public ?string $pays_provenance = null;
    public ?string $dci = null;
    public ?string $new_dci = null;
    public ?string $amm_numero = null;
    public ?string $amm_date = null;
    public ?int $amm_duree_validiter = null;
    public ?string $amm_date_fin = null;
    public string $forme = '';
    public string $dosage = '';
    public ?string $conditionnement = null;
    public bool $is_active = true;
    public int $stock_min = 0;
    public ?string $expiration_date = null;

    public function save(): void
    {
        $validated = $this->validate([
            'reference' => ['required', 'string', 'max:255', 'unique:medicaments,reference'],
            'name' => ['required', 'string', 'max:255'],
            'classe' => ['required', 'string', 'max:255'],
            'fournisseur' => ['nullable', 'string', 'max:255'],
            'fabricant' => ['nullable', 'string', 'max:255'],
            'pays_provenance' => ['nullable', 'string', 'max:255'],
            'dci' => ['nullable', 'string', 'max:255'],
            'new_dci' => ['nullable', 'string', 'max:500'],
            'amm_numero' => ['nullable', 'string', 'max:255'],
            'amm_date' => ['nullable', 'date'],
            'amm_duree_validiter' => ['nullable', 'integer', 'min:0'],
            'amm_date_fin' => ['nullable', 'date'],
            'forme' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:255'],
            'conditionnement' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'stock_min' => ['required', 'integer', 'min:0'],
            'expiration_date' => ['nullable', 'date'],
        ]);

        $validated['dci'] = trim((string) ($validated['new_dci'] ?: $validated['dci'])) ?: null;
        unset($validated['new_dci']);

        $medicament = Medicament::query()->create($validated);

        $pharmacies = Pharmacie::query()->where('hopital_id', current_hopital_id())->get();
        foreach ($pharmacies as $pharmacie) {
            $pharmacie->medicaments()->syncWithoutDetaching([
                $medicament->id => [
                    'quantiter' => 0,
                    'montant' => 0,
                ],
            ]);
        }
        $this->reset([
            'reference', 'name', 'classe', 'fournisseur', 'fabricant', 'pays_provenance', 'dci', 'new_dci',
            'amm_numero', 'amm_date', 'amm_duree_validiter', 'amm_date_fin', 'forme', 'dosage', 'conditionnement',
            'expiration_date',
        ]);
        $this->is_active = true;
        $this->stock_min = 0;
    }
};
?>

<div class="space-y-5 p-6">
    <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-xs text-sky-800">
        Note: un stock avec quantite 0 sera automatiquement cree dans toutes les pharmacies de votre etablissement.
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-lg font-black text-slate-900 dark:text-white">Informations du medicament</h2>
        <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Informations principales</p>

        <div class="grid gap-3 md:grid-cols-2">
            <x-input label="Numero de reference *" wire:model="reference" placeholder="Ex: MED-001" />
            <x-input label="Nom du produit *" wire:model="name" placeholder="Ex: Paracetamol 500mg" />
        </div>

        <p class="mt-5 mb-2 text-xs font-semibold text-slate-600 dark:text-slate-300">Classification et origine</p>
        <div class="grid gap-3 md:grid-cols-2">
            <x-input label="Classe therapeutique *" wire:model="classe" placeholder="Toutes les classes..." />
            <x-input label="EPVG (Fournisseur)" wire:model="fournisseur" placeholder="Selectionner un fournisseur..." />
            <x-input label="Fabricant" wire:model="fabricant" placeholder="Selectionner un fabricant..." />
            <x-input label="Pays de provenance" wire:model="pays_provenance" placeholder="Ex: France" />
        </div>

        <p class="mt-5 mb-2 text-xs font-semibold text-slate-600 dark:text-slate-300">Denominations Communes Internationales</p>
        <div class="grid gap-3 md:grid-cols-2">
            <x-input label="DCIs" wire:model="dci" placeholder="DCIs..." />
            <x-input label="Nouvelles DCIs (texte libre)" wire:model="new_dci" placeholder="Ex: Paracetamol, Ibuprofen..." />
        </div>

        <p class="mt-5 mb-2 text-xs font-semibold text-slate-600 dark:text-slate-300">Autorisation de Mise sur le Marche</p>
        <div class="grid gap-3 md:grid-cols-4">
            <x-input label="Numero AMM" wire:model="amm_numero" />
            <x-input type="date" label="Date d'enregistrement" wire:model="amm_date" />
            <x-number label="Duree de validite (annees)" wire:model="amm_duree_validiter" />
            <x-input type="date" label="Date de fin de validite" wire:model="amm_date_fin" />
        </div>

        <p class="mt-5 mb-2 text-xs font-semibold text-slate-600 dark:text-slate-300">Caracteristiques du medicament</p>
        <div class="grid gap-3 md:grid-cols-3">
            <x-input label="Forme galenique *" wire:model="forme" placeholder="Ex: Comprime..." />
            <x-input label="Dosage *" wire:model="dosage" placeholder="Ex: 500mg" />
            <x-input label="Conditionnement" wire:model="conditionnement" placeholder="Ex: Boite de 20 comprimes" />
            <x-number label="Stock minimum" wire:model="stock_min" />
            <x-input type="date" label="Date d'expiration" wire:model="expiration_date" />
            <x-select.styled label="Statut" wire:model="is_active" :options="[
                ['label' => 'Actif', 'value' => true],
                ['label' => 'Inactif', 'value' => false],
            ]" select="label:label|value:value" />
        </div>

        <flux:button class="mt-4" wire:click="save" variant="primary" color="emerald">Ajouter medicament</flux:button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="mb-3 text-base font-black text-slate-900 dark:text-white">Liste des medicaments</h3>
        <livewire:pharmacy-medicine-table />
    </div>
</div>
