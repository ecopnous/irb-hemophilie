<?php

use App\Models\prescription\Pharmacie;
use App\Services\PharmacyStockService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $this->pharmacie_id = Pharmacie::query()
            ->where('hopital_id', current_hopital_id())
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('nom')
            ->value('id');
    }

    public function updatedPharmacieId($value): void
    {
        $this->pharmacie_id = $value !== null && $value !== '' ? (int) $value : null;
        $this->medicament_id = null;
        $this->quantity = null;
        unset($this->pharmacyMedicaments, $this->currentStock);
    }

    public function updatedMedicamentId($value): void
    {
        $this->medicament_id = $value !== null && $value !== '' ? (int) $value : null;
        $this->quantity = null;
        unset($this->currentStock);
    }

    #[Computed]
    public function pharmacies(): Collection
    {
        return Pharmacie::query()
            ->where('hopital_id', current_hopital_id())
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('nom')
            ->get();
    }

    #[Computed]
    public function pharmacyMedicaments(): Collection
    {
        if (! $this->pharmacie_id) {
            return collect();
        }

        $pharmacie = Pharmacie::query()
            ->where('hopital_id', current_hopital_id())
            ->with(['medicaments' => fn ($q) => $q->orderBy('medicaments.name')])
            ->find($this->pharmacie_id);

        return $pharmacie?->medicaments ?? collect();
    }

    #[Computed]
    public function depreciationsCount(): int
    {
        $pharmacyIds = Pharmacie::query()
            ->where('hopital_id', current_hopital_id())
            ->pluck('id');

        if ($pharmacyIds->isEmpty()) {
            return 0;
        }

        return \App\Models\prescription\StockMovement::query()
            ->whereIn('pharmacie_id', $pharmacyIds)
            ->where('movement_type', 'depreciation')
            ->count();
    }

    #[Computed]
    public function currentStock(): int
    {
        if (! $this->pharmacie_id || ! $this->medicament_id) {
            return 0;
        }

        $medicament = $this->pharmacyMedicaments->firstWhere('id', $this->medicament_id);

        return (int) ($medicament?->pivot->quantiter ?? 0);
    }

    public function depreciate(PharmacyStockService $service): void
    {
        $stock = $this->currentStock;

        $validated = $this->validate([
            'pharmacie_id' => [
                'required',
                'integer',
                Rule::exists('pharmacies', 'id')->where(fn ($q) => $q->where('hopital_id', current_hopital_id())),
            ],
            'medicament_id' => ['required', 'integer', 'exists:medicaments,id'],
            'quantity' => ['required', 'integer', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($stock <= 0) {
            throw ValidationException::withMessages([
                'medicament_id' => 'Ce medicament n\'a pas de stock disponible dans cette pharmacie.',
            ]);
        }

        if ($validated['quantity'] > $stock) {
            throw ValidationException::withMessages([
                'quantity' => 'La quantite ne peut pas depasser le stock actuel (' . $stock . ').',
            ]);
        }

        $linked = \Illuminate\Support\Facades\DB::table('medicament_pharmacie')
            ->where('pharmacie_id', $validated['pharmacie_id'])
            ->where('medicament_id', $validated['medicament_id'])
            ->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'medicament_id' => 'Ce medicament n\'est pas rattache a la pharmacie selectionnee.',
            ]);
        }

        $service->moveStock($validated + [
            'movement_type' => 'depreciation',
            'reference' => 'DEPREC-' . now()->format('YmdHis'),
            'user_id' => auth()->id(),
        ]);

        $this->reset(['medicament_id', 'quantity', 'note']);
        unset($this->pharmacyMedicaments, $this->currentStock, $this->depreciationsCount);

        $this->dispatch('pg:eventRefresh-pharmacyMovementTable');
        session()->flash('success', 'Depreciation enregistree avec succes.');
    }
};
?>

<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Depreciations de stock</h1>
        <span class="rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">
            Total depreciations : {{ $this->depreciationsCount }}
        </span>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 font-bold text-slate-900 dark:text-white">Sortie depreciation</h2>

        @if ($this->pharmacies->isEmpty())
            <p class="text-sm text-amber-700 dark:text-amber-300">
                Aucune pharmacie active pour cet hopital.
                <a href="{{ route('pharmacie.pharmacies') }}" wire:navigate class="font-bold underline">Creer une pharmacie</a>
            </p>
        @else
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <x-select.styled
                    label="Pharmacie"
                    wire:model.live="pharmacie_id"
                    :options="$this->pharmacies->map(fn ($p) => ['label' => $p->nom, 'value' => $p->id])->values()->all()"
                    select="label:label|value:value"
                />

                <x-select.styled
                    label="Medicament"
                    wire:model.live="medicament_id"
                    :options="$this->pharmacyMedicaments->map(fn ($m) => [
                        'label' => $m->name . ' (' . ($m->reference ?: '-') . ') — Stock: ' . (int) $m->pivot->quantiter,
                        'value' => $m->id,
                        'disabled' => (int) $m->pivot->quantiter <= 0,
                    ])->values()->all()"
                    :disabled="!$pharmacie_id"
                    wire:key="deprec-medicament-{{ $pharmacie_id }}"
                    select="label:label|value:value"
                />

                <div>
                    <x-number
                        label="Quantite"
                        wire:model="quantity"
                        min="1"
                        :max="$this->currentStock > 0 ? $this->currentStock : null"
                    />
                    @if ($medicament_id)
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Stock disponible :
                            <span class="font-bold {{ $this->currentStock > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $this->currentStock }}
                            </span>
                        </p>
                    @endif
                    @error('quantity')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input label="Motif" wire:model="note" placeholder="Peremption, casse, retrait..." />
                    @error('note')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @error('pharmacie_id')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('medicament_id')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror

            <flux:button class="mt-3" wire:click="depreciate" variant="danger">
                Declarer depreciation
            </flux:button>
        @endif
    </div>

    <h3 class="mb-3 text-base font-black text-slate-900 dark:text-white">Historique des depreciations</h3>
        <livewire:pharmacy-movement-table movement-type="depreciation" />
</div>
