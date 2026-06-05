<?php

use App\Models\Configs\Acte;
use App\Models\Configs\MedicalActPriceHistory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Grille tarifaire'), Layout('layouts::app.other.facturation')] class extends Component {
    public ?int $edit_id = null;
    public ?string $edit_name = null;
    public ?float $edit_price = null;
    public ?string $edit_code = null;
    public bool $edit_active = true;

    public function mount(): void
    {
        $acteId = request()->integer('acte');
        if ($acteId) {
            $this->startEdit($acteId);
        }
    }

    public function startEdit(int $id): void
    {
        $act = Acte::query()->findOrFail($id);
        $this->edit_id = $act->id;
        $this->edit_name = $act->name;
        $this->edit_price = (float) ($act->base_price ?? $act->montant);
        $this->edit_code = $act->code;
        $this->edit_active = (bool) $act->is_active;
    }

    public function saveEdit(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $this->validate([
            'edit_id' => ['required', 'integer', 'exists:actes,id'],
            'edit_name' => ['required', 'string', 'max:255'],
            'edit_price' => ['required', 'numeric', 'gt:0'],
            'edit_code' => ['nullable', 'string', 'max:50'],
            'edit_active' => ['boolean'],
        ]);

        $act = Acte::query()->findOrFail($validated['edit_id']);
        $oldPrice = (float) ($act->base_price ?? $act->montant);

        $act->forceFill([
            'name' => $validated['edit_name'],
            'code' => $validated['edit_code'],
            'base_price' => $validated['edit_price'],
            'montant' => $validated['edit_price'],
            'is_active' => $validated['edit_active'],
            'updated_by' => Auth::id(),
        ])->save();

        if ($oldPrice !== (float) $validated['edit_price']) {
            MedicalActPriceHistory::query()->create([
                'acte_id' => $act->id,
                'old_price' => $oldPrice,
                'new_price' => $validated['edit_price'],
                'changed_by' => Auth::id(),
                'changed_at' => now(),
            ]);
        }

        $this->reset(['edit_id', 'edit_name', 'edit_price', 'edit_code', 'edit_active']);
    }
};
?>

<div class="space-y-5 p-6">
    <h1 class="text-2xl font-black text-slate-900 dark:text-white">Grille tarifaire des actes medicaux</h1>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/70">
        <livewire:tariff-table />
    </div>

    @if($edit_id)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <h2 class="mb-3 font-bold text-emerald-900 dark:text-emerald-100">Edition acte #{{ $edit_id }}</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <x-input label="Nom" wire:model="edit_name" />
                <x-input label="Code" wire:model="edit_code" />
                <x-number step="0.01" label="Prix" wire:model="edit_price" />
                <x-select.styled label="Statut" wire:model="edit_active" :options="[
                    ['label' => 'Actif', 'value' => true],
                    ['label' => 'Inactif', 'value' => false],
                ]"
                    select="label:label|value:value" />
            </div>
            <flux:button class="mt-3" wire:click="saveEdit" variant="primary">Sauvegarder</flux:button>
        </div>
    @endif
</div>
