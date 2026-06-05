<?php

use App\Models\prescription\Pharmacie;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pharmacies'), Layout('layouts::app.other.pharmacy')] class extends Component {
    public string $nom = '';
    public bool $is_active = true;

    public function save(): void
    {
        $validated = $this->validate([
            'nom' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Pharmacie::query()->create([
            'nom' => $validated['nom'],
            'is_active' => $validated['is_active'],
            'hopital_id' => current_hopital_id(),
        ]);

        $this->reset('nom');
        $this->is_active = true;
    }
};
?>

<div class="space-y-5 p-6">
    <h1 class="text-2xl font-black text-slate-900 dark:text-white">Gestion des pharmacies</h1>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 font-bold text-slate-900 dark:text-white">Nouvelle pharmacie</h2>
        <div class="grid gap-3 md:grid-cols-3">
            <x-input label="Nom de la pharmacie" wire:model="nom" placeholder="Ex: Pharmacie principale" />
            <x-select.styled label="Statut" wire:model="is_active" :options="[
                ['label' => 'Active', 'value' => true],
                ['label' => 'Inactive', 'value' => false],
            ]" select="label:label|value:value" />
            <flux:button class="mt-6" wire:click="save" variant="primary" color="emerald">Creer pharmacie</flux:button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="mb-3 text-base font-black text-slate-900 dark:text-white">Liste des pharmacies</h3>
        <livewire:pharmacy-pharmacy-table />
    </div>
</div>
