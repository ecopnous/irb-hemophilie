<?php

use App\Models\prescription\Medicament;
use App\Models\prescription\Pharmacie;
use App\Services\PharmacyStockService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Stock medicaments'), Layout('layouts::app.other.pharmacy')] class extends Component {
    public ?int $pharmacie_id = null;
    public ?int $medicament_id = null;
    public string $movement_type = 'in';
    public ?int $quantity = null;
    public ?string $reference = null;
    public ?string $note = null;

    public function mount(): void
    {
        $this->pharmacie_id = Pharmacie::query()->where('hopital_id', current_hopital_id())->value('id');
    }

    #[Computed]
    public function pharmacies()
    {
        return Pharmacie::query()->where('hopital_id', current_hopital_id())->orderBy('nom')->get();
    }

    #[Computed]
    public function medicaments()
    {
        return Medicament::query()->orderBy('name')->limit(500)->get();
    }

    public function saveMovement(PharmacyStockService $service): void
    {
        $validated = $this->validate([
            'pharmacie_id' => ['required', 'integer', 'exists:pharmacies,id'],
            'medicament_id' => ['required', 'integer', 'exists:medicaments,id'],
            'movement_type' => ['required', 'in:in,out,adjustment,depreciation'],
            'quantity' => ['required', 'integer', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->moveStock($validated + ['user_id' => auth()->id()]);
        $this->reset(['medicament_id', 'quantity', 'reference', 'note']);
    }
};
?>

<div class="mx-auto space-y-5 max-w-7xl">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Stock medicaments</h1>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 font-bold text-slate-900 dark:text-white">Saisie mouvement stock</h2>
        <div class="grid gap-3 md:grid-cols-3">
            <x-select.styled label="Pharmacie" wire:model="pharmacie_id" :options="$this->pharmacies->map(fn($p) => ['label' => $p->nom, 'value' => $p->id])->values()->all()"
                select="label:label|value:value" />
            <x-select.styled label="Medicament" wire:model="medicament_id" :options="$this->medicaments->map(fn($m) => ['label' => $m->name . ' (' . $m->reference . ')', 'value' => $m->id])->values()->all()"
                select="label:label|value:value" />
            <x-select.styled label="Type" wire:model="movement_type" :options="[
                ['label' => 'Entree', 'value' => 'in'],
                ['label' => 'Sortie', 'value' => 'out'],
                ['label' => 'Ajustement', 'value' => 'adjustment'],
                ['label' => 'Depreciation', 'value' => 'depreciation'],
            ]"
                select="label:label|value:value" />
            <x-number label="Quantite" wire:model="quantity" />
            <x-input label="Reference" wire:model="reference" />
            <x-input label="Note" wire:model="note" />
        </div>
        <flux:button class="mt-3" wire:click="saveMovement" variant="primary" color="emerald">Enregistrer</flux:button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <livewire:pharmacy-stock-table />
    </div>
</div>
