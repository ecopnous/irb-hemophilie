<?php

use App\Models\prescription\Pharmacie;
use App\Models\prescription\Prescription;
use App\Models\prescription\StockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tableau de bord pharmacie'), Layout('layouts::app.other.pharmacy')] class extends Component {
    #[Computed]
    public function stats(): array
    {
        $today = StockMovement::query()->whereDate('created_at', today());
        $week = StockMovement::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        $month = StockMovement::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);

        return [
            'today_prescriptions' => Prescription::query()->whereDate('created_at', today())->count(),
            'week_prescriptions' => Prescription::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month_prescriptions' => Prescription::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'pharmacies' => Pharmacie::query()->where('is_active', true)->count(),
            'today_in' => (clone $today)->where('movement_type', 'in')->sum('quantity'),
            'today_out' => (clone $today)->whereIn('movement_type', ['out', 'depreciation'])->sum('quantity'),
            'week_in' => (clone $week)->where('movement_type', 'in')->sum('quantity'),
            'week_out' => (clone $week)->whereIn('movement_type', ['out', 'depreciation'])->sum('quantity'),
            'month_in' => (clone $month)->where('movement_type', 'in')->sum('quantity'),
            'month_out' => (clone $month)->whereIn('movement_type', ['out', 'depreciation'])->sum('quantity'),
        ];
    }
};
?>

<div class="space-y-5 p-6">
    <div>
        <h1 class="text-3xl font-black text-slate-900 dark:text-white">Tableau de Bord Pharmacie</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Vue d'ensemble des activites</p>
    </div>

    <div class="grid gap-3 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs text-slate-500">Aujourd'hui</p>
            <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['today_prescriptions'] }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Prescriptions</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs text-slate-500">Cette semaine</p>
            <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['week_prescriptions'] }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Prescriptions</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs text-slate-500">Ce mois</p>
            <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['month_prescriptions'] }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Prescriptions</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs text-slate-500">Pharmacies</p>
            <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['pharmacies'] }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Actives</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <p class="font-semibold text-emerald-800 dark:text-emerald-200">Mouvements aujourd'hui</p>
            <p class="mt-2 text-sm">Entrees: <span class="font-bold">{{ $this->stats['today_in'] }}</span></p>
            <p class="text-sm">Sorties: <span class="font-bold">{{ $this->stats['today_out'] }}</span></p>
        </div>
        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-500/30 dark:bg-sky-500/10">
            <p class="font-semibold text-sky-800 dark:text-sky-200">Mouvements semaine</p>
            <p class="mt-2 text-sm">Entrees: <span class="font-bold">{{ $this->stats['week_in'] }}</span></p>
            <p class="text-sm">Sorties: <span class="font-bold">{{ $this->stats['week_out'] }}</span></p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="font-semibold text-amber-800 dark:text-amber-200">Mouvements mois</p>
            <p class="mt-2 text-sm">Entrees: <span class="font-bold">{{ $this->stats['month_in'] }}</span></p>
            <p class="text-sm">Sorties: <span class="font-bold">{{ $this->stats['month_out'] }}</span></p>
        </div>
    </div>
</div>
