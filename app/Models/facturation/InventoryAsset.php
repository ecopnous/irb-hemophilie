<?php

namespace App\Models\facturation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class InventoryAsset extends Model
{
    protected $fillable = [
        'hopital_id',
        'inventory_location_id',
        'inventory_category_id',
        'inventory_service_id',
        'assigned_user_id',
        'inventory_number',
        'marque',
        'modele',
        'reference',
        'serial_number',
        'quantity',
        'status',
        'description',
        'observation',
        'acquired_at',
        'acquisition_cost',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'depreciation_rate',
        'currency',
    ];

    protected $casts = [
        'acquired_at' => 'date',
        'acquisition_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(InventoryService::class, 'inventory_service_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function depreciationBase(): float
    {
        return max((float) $this->acquisition_cost - (float) $this->salvage_value, 0);
    }

    public function monthsInService(): int
    {
        if (!$this->acquired_at) {
            return 0;
        }

        return max(0, Carbon::parse($this->acquired_at)->diffInMonths(now()));
    }

    public function resolvedUsefulLifeYears(): ?int
    {
        $assetYears = (int) ($this->useful_life_years ?? 0);
        if ($assetYears > 0) {
            return $assetYears;
        }

        $categoryYears = (int) ($this->category?->default_useful_life_years ?? 0);
        return $categoryYears > 0 ? $categoryYears : null;
    }

    public function monthlyDepreciationAmount(): float
    {
        $base = $this->depreciationBase();
        if ($base <= 0) {
            return 0;
        }

        if ($this->depreciation_method === 'degressif') {
            $rate = (float) ($this->depreciation_rate ?: ($this->category?->default_depreciation_rate ?? 0));
            if ($rate <= 0) {
                return 0;
            }

            return ((float) $this->acquisition_cost * $rate / 100) / 12;
        }

        $years = $this->resolvedUsefulLifeYears();
        if (!$years || $years <= 0) {
            return 0;
        }

        return $base / ($years * 12);
    }

    public function accumulatedDepreciationAmount(): float
    {
        $amount = $this->monthsInService() * $this->monthlyDepreciationAmount();
        return min($this->depreciationBase(), max(0, $amount));
    }

    public function netBookValue(): float
    {
        return max((float) $this->acquisition_cost - $this->accumulatedDepreciationAmount(), 0);
    }
}
