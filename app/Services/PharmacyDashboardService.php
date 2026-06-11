<?php

namespace App\Services;

use App\Models\prescription\Medicament;
use App\Models\prescription\Pharmacie;
use App\Models\prescription\Prescription;
use App\Models\prescription\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PharmacyDashboardService
{
    /**
     * @return array<int, int>
     */
    public function pharmacyIds(int $hopitalId): array
    {
        return Pharmacie::query()
            ->where('hopital_id', $hopitalId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(int $hopitalId): array
    {
        $pharmacyIds = $this->pharmacyIds($hopitalId);
        $today = today();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $prescriptionBase = Prescription::query()->where('hopital_id', $hopitalId);
        $movementBase = StockMovement::query()->when(
            $pharmacyIds !== [],
            fn ($q) => $q->whereIn('pharmacie_id', $pharmacyIds),
            fn ($q) => $q->whereRaw('1 = 0')
        );

        $criticalStock = $this->criticalStockCount($pharmacyIds);
        $outOfStock = $this->outOfStockCount($pharmacyIds);
        $stockValue = $this->stockValue($pharmacyIds);

        return [
            'pharmacies' => Pharmacie::query()->where('hopital_id', $hopitalId)->where('is_active', true)->count(),
            'medicaments' => Medicament::query()->where('is_active', true)->count(),
            'prescriptions_today' => (clone $prescriptionBase)->whereDate('created_at', $today)->count(),
            'prescriptions_week' => (clone $prescriptionBase)->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'prescriptions_month' => (clone $prescriptionBase)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'prescriptions_pending' => (clone $prescriptionBase)->whereIn('status', ['draft', 'partial'])->count(),
            'prescriptions_draft' => (clone $prescriptionBase)->where('status', 'draft')->count(),
            'prescriptions_partial' => (clone $prescriptionBase)->where('status', 'partial')->count(),
            'prescriptions_served' => (clone $prescriptionBase)->where('status', 'served')->count(),
            'today_in' => (clone $movementBase)->whereDate('created_at', $today)->where('movement_type', 'in')->sum('quantity'),
            'today_out' => (clone $movementBase)->whereDate('created_at', $today)->whereIn('movement_type', ['out', 'depreciation'])->sum('quantity'),
            'week_in' => (clone $movementBase)->whereBetween('created_at', [$weekStart, $weekEnd])->where('movement_type', 'in')->sum('quantity'),
            'week_out' => (clone $movementBase)->whereBetween('created_at', [$weekStart, $weekEnd])->whereIn('movement_type', ['out', 'depreciation'])->sum('quantity'),
            'month_in' => (clone $movementBase)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->where('movement_type', 'in')
                ->sum('quantity'),
            'month_out' => (clone $movementBase)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->whereIn('movement_type', ['out', 'depreciation'])
                ->sum('quantity'),
            'movements_today' => (clone $movementBase)->whereDate('created_at', $today)->count(),
            'depreciations_month' => (clone $movementBase)
                ->where('movement_type', 'depreciation')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('quantity'),
            'critical_stock' => $criticalStock,
            'out_of_stock' => $outOfStock,
            'expiring_soon' => Medicament::query()
                ->where('is_active', true)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '<=', now()->addDays(90))
                ->whereDate('expiration_date', '>=', $today)
                ->count(),
            'expired' => Medicament::query()
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '<', $today)
                ->count(),
            'stock_value' => $stockValue,
        ];
    }

    /**
     * @return Collection<int, Prescription>
     */
    public function pendingPrescriptions(int $hopitalId, int $limit = 8): Collection
    {
        return Prescription::query()
            ->with(['dossierPatient', 'consultation', 'medicaments'])
            ->where('hopital_id', $hopitalId)
            ->whereIn('status', ['draft', 'partial'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function lowStockItems(int $hopitalId, int $limit = 8): Collection
    {
        $pharmacyIds = $this->pharmacyIds($hopitalId);

        if ($pharmacyIds === []) {
            return collect();
        }

        return DB::table('medicament_pharmacie')
            ->join('medicaments', 'medicaments.id', '=', 'medicament_pharmacie.medicament_id')
            ->join('pharmacies', 'pharmacies.id', '=', 'medicament_pharmacie.pharmacie_id')
            ->whereIn('medicament_pharmacie.pharmacie_id', $pharmacyIds)
            ->whereColumn('medicament_pharmacie.quantiter', '<=', 'medicaments.stock_min')
            ->select([
                'medicaments.id as medicament_id',
                'medicaments.name',
                'medicaments.reference',
                'medicaments.stock_min',
                'medicament_pharmacie.quantiter',
                'pharmacies.nom as pharmacie_nom',
            ])
            ->orderBy('medicament_pharmacie.quantiter')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function recentMovements(int $hopitalId, int $limit = 10): Collection
    {
        $pharmacyIds = $this->pharmacyIds($hopitalId);

        return StockMovement::query()
            ->with(['pharmacie', 'medicament', 'consultation'])
            ->when(
                $pharmacyIds !== [],
                fn ($q) => $q->whereIn('pharmacie_id', $pharmacyIds),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<int, int>  $pharmacyIds
     */
    private function criticalStockCount(array $pharmacyIds): int
    {
        if ($pharmacyIds === []) {
            return 0;
        }

        return (int) DB::table('medicament_pharmacie')
            ->join('medicaments', 'medicaments.id', '=', 'medicament_pharmacie.medicament_id')
            ->whereIn('medicament_pharmacie.pharmacie_id', $pharmacyIds)
            ->whereColumn('medicament_pharmacie.quantiter', '<=', 'medicaments.stock_min')
            ->count();
    }

    /**
     * @param  array<int, int>  $pharmacyIds
     */
    private function outOfStockCount(array $pharmacyIds): int
    {
        if ($pharmacyIds === []) {
            return 0;
        }

        return (int) DB::table('medicament_pharmacie')
            ->whereIn('pharmacie_id', $pharmacyIds)
            ->where('quantiter', '<=', 0)
            ->count();
    }

    /**
     * @param  array<int, int>  $pharmacyIds
     */
    private function stockValue(array $pharmacyIds): float
    {
        if ($pharmacyIds === []) {
            return 0.0;
        }

        return (float) DB::table('medicament_pharmacie')
            ->whereIn('pharmacie_id', $pharmacyIds)
            ->sum(DB::raw('COALESCE(quantiter, 0) * COALESCE(montant, 0)'));
    }
}
