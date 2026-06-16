<?php

use App\Models\prescription\StockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mouvements de stock'), Layout('layouts::app.other.pharmacy')] class extends Component {
    #[Computed]
    public function totals(): array
    {
        $query = StockMovement::query();

        $in = (int) (clone $query)->where('movement_type', 'in')->sum('quantity');
        $out = (int) (clone $query)->whereIn('movement_type', ['out', 'depreciation'])->sum('quantity');

        return [
            'total' => $in + $out,
            'in' => $in,
            'out' => $out,
            'variation' => $in - $out,
        ];
    }
};
?>

<div class="mx-auto space-y-5 max-w-7xl">
    <h1 class="text-2xl font-black text-slate-900 dark:text-white">Mouvements de stock</h1>

    <div class="flex flex-wrap gap-3">
        <span class="rounded-xl bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">Total: {{ $this->totals['total'] }}</span>
        <span class="rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">Entrees: +{{ $this->totals['in'] }}</span>
        <span class="rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">Sorties: -{{ $this->totals['out'] }}</span>
        <span class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">Variation: {{ $this->totals['variation'] >= 0 ? '+' : '' }}{{ $this->totals['variation'] }}</span>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <livewire:pharmacy-movement-table />
    </div>
</div>
