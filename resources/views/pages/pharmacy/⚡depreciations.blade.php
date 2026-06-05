<?php

use App\Models\prescription\Medicament;
use App\Services\PharmacyStockService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Medicaments deprecies'), Layout('layouts::app.other.pharmacy')] class extends Component {
    public ?int $pharmacie_id = null;
    public ?int $medicament_id = null;
    public ?int $quantity = null;
    public ?string $note = null;

    public function mount(): void
    {
        $this->pharmacie_id = \App\Models\prescription\Pharmacie::query()->where('hopital_id', current_hopital_id())->value('id');
    }

    #[Computed]
    public function deprecatedCount(): int
    {
        return Medicament::query()->where(function ($q) {
            $q->where('is_active', false)->orWhereDate('expiration_date', '<', today());
        })->count();
    }

    public function depreciate(PharmacyStockService $service): void
    {
        $validated = $this->validate([
            'pharmacie_id' => ['required', 'integer', 'exists:pharmacies,id'],
            'medicament_id' => ['required', 'integer', 'exists:medicaments,id'],
            'quantity' => ['required', 'integer', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->moveStock($validated + [
            'movement_type' => 'depreciation',
            'reference' => 'DEPREC-' . now()->format('YmdHis'),
            'user_id' => auth()->id(),
        ]);

        $this->reset(['medicament_id', 'quantity', 'note']);
    }
};
?>

<div class="space-y-5 p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Liste des medicaments deprecies</h1>
        <span class="rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">Total deprecies: {{ $this->deprecatedCount }}</span>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 font-bold text-slate-900 dark:text-white">Sortie depreciation</h2>
        <div class="grid gap-3 md:grid-cols-4">
            <x-input label="Pharmacie ID" wire:model="pharmacie_id" />
            <x-input label="Medicament ID" wire:model="medicament_id" />
            <x-number label="Quantite" wire:model="quantity" />
            <x-input label="Motif" wire:model="note" />
        </div>
        <flux:button class="mt-3" wire:click="depreciate" variant="danger">Declarer depreciation</flux:button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <livewire:pharmacy-deprecated-table />
    </div>
</div>
