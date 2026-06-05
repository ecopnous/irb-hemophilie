<?php

use App\Models\LaboratoryStockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Mouvements stock laboratoire'), Layout('layouts::app.other.laboratoire')] class extends Component {
    use WithPagination;

    public ?string $date_start = null;
    public ?string $date_end = null;
    public string $movement_type = '';
    public string $search = '';

    public function updatedDateStart(): void
    {
        $this->resetPage();
    }

    public function updatedDateEnd(): void
    {
        $this->resetPage();
    }

    public function updatedMovementType(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function movements()
    {
        return LaboratoryStockMovement::query()
            ->with(['consumable', 'creator'])
            ->whereHas('consumable', fn($q) => $q->whereHopitalId(current_hopital_id()))
            ->when($this->date_start, fn($q) => $q->whereDate('created_at', '>=', $this->date_start))
            ->when($this->date_end, fn($q) => $q->whereDate('created_at', '<=', $this->date_end))
            ->when($this->movement_type !== '', fn($q) => $q->where('movement_type', $this->movement_type))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('reference', 'like', $term)
                        ->orWhere('lot_number', 'like', $term)
                        ->orWhere('destination', 'like', $term)
                        ->orWhere('reason', 'like', $term)
                        ->orWhereHas('consumable', fn($sq) => $sq->where('name', 'like', $term)->orWhere('reference', 'like', $term));
                });
            })
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function totals(): array
    {
        $base = LaboratoryStockMovement::query()
            ->whereHas('consumable', fn($q) => $q->whereHopitalId(current_hopital_id()))
            ->when($this->date_start, fn($q) => $q->whereDate('created_at', '>=', $this->date_start))
            ->when($this->date_end, fn($q) => $q->whereDate('created_at', '<=', $this->date_end));

        $in = (int) (clone $base)->where('movement_type', 'in')->sum('quantity');
        $out = (int) (clone $base)->whereIn('movement_type', ['out', 'loss', 'expired', 'transfer'])->sum('quantity');
        $adjustments = (int) (clone $base)->where('movement_type', 'adjustment')->count();

        return [
            'in' => $in,
            'out' => $out,
            'variation' => $in - $out,
            'adjustments' => $adjustments,
        ];
    }

    public function movementTypeOptions(): array
    {
        return [
            ['label' => 'Tous', 'value' => ''],
            ['label' => 'Entree', 'value' => 'in'],
            ['label' => 'Sortie', 'value' => 'out'],
            ['label' => 'Ajustement', 'value' => 'adjustment'],
            ['label' => 'Perte / casse', 'value' => 'loss'],
            ['label' => 'Peremption', 'value' => 'expired'],
            ['label' => 'Transfert', 'value' => 'transfer'],
        ];
    }

    public function movementMeta(string $type): array
    {
        return match ($type) {
            'in' => ['label' => 'Entree', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', 'sign' => '+'],
            'out' => ['label' => 'Sortie', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300', 'sign' => '-'],
            'loss' => ['label' => 'Perte', 'class' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300', 'sign' => '-'],
            'expired' => ['label' => 'Peremption', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300', 'sign' => '-'],
            'transfer' => ['label' => 'Transfert', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'sign' => '-'],
            'adjustment' => ['label' => 'Ajustement', 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300', 'sign' => ''],
            default => ['label' => ucfirst($type), 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300', 'sign' => ''],
        };
    }
};
?>

<div class="space-y-5 p-6">
    <section
        class="overflow-hidden rounded-4xl border border-cyan-100 bg-linear-to-br from-white via-cyan-50/40 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="space-y-5 px-6 py-6 md:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Laboratoire', 'link' => route('laboratoire.index'), 'icon' => 'beaker'],
                ['label' => 'Mouvements stock', 'icon' => 'arrows-right-left'],
            ]" />
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-cyan-600 dark:text-cyan-300">
                        Traçabilité laboratoire
                    </p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        Mouvements de stock
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Journal des entrees, sorties, pertes, peremptions, transferts et ajustements.
                    </p>
                </div>
                <a href="{{ route('laboratoire.stock') }}" wire:navigate>
                    <flux:button variant="primary" color="cyan" icon="archive-box">
                        Gerer le stock
                    </flux:button>
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-4">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Entrees</p>
            <p class="mt-2 text-2xl font-black text-emerald-900 dark:text-emerald-100">+{{ $this->totals['in'] }}</p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/20 dark:bg-rose-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-700 dark:text-rose-300">Sorties</p>
            <p class="mt-2 text-2xl font-black text-rose-900 dark:text-rose-100">-{{ $this->totals['out'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Variation</p>
            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ $this->totals['variation'] >= 0 ? '+' : '' }}{{ $this->totals['variation'] }}</p>
        </div>
        <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-violet-700 dark:text-violet-300">Ajustements</p>
            <p class="mt-2 text-2xl font-black text-violet-900 dark:text-violet-100">{{ $this->totals['adjustments'] }}</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <div class="grid gap-3 md:grid-cols-5">
            <x-input type="date" wire:model.live="date_start" label="Date debut" />
            <x-input type="date" wire:model.live="date_end" label="Date fin" />
            <x-select.styled wire:model.live="movement_type" label="Type" :options="$this->movementTypeOptions()"
                select="label:label|value:value" />
            <div class="md:col-span-2">
                <x-input wire:model.live.debounce.400ms="search" label="Recherche" placeholder="Reference, lot, destination, produit..." />
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-900/70">
                <tr class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Reference stock</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3 text-right">Quantite</th>
                    <th class="px-4 py-3 text-right">Avant</th>
                    <th class="px-4 py-3 text-right">Apres</th>
                    <th class="px-4 py-3">Lot / expiration</th>
                    <th class="px-4 py-3">Destination / motif</th>
                    <th class="px-4 py-3">Agent</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($this->movements as $movement)
                    @php
                        $meta = $this->movementMeta($movement->movement_type);
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            {{ $movement->created_at?->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $movement->consumable?->name ?: '-' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $movement->reference ?: ($movement->consumable?->reference ?: '-') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-black text-slate-900 dark:text-white">
                            {{ $meta['sign'] }}{{ $movement->quantity }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $movement->quantity_before }}</td>
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $movement->quantity_after }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            <p>{{ $movement->lot_number ?: '-' }}</p>
                            <p class="text-xs">{{ $movement->expiration_date?->format('d/m/Y') ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            <p>{{ $movement->destination ?: '-' }}</p>
                            <p class="text-xs">{{ $movement->reason ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $movement->creator?->name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                            Aucun mouvement trouve.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
            {{ $this->movements->links() }}
        </div>
    </section>
</div>
