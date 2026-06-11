<?php

namespace App\Models;

use App\Models\Concerns\ScopesByHopital;
use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceptionSupply extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'hopital_id',
        'reference',
        'designation',
        'category',
        'unit',
        'planned_stock',
        'current_stock',
        'stock_min',
        'notes',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'planned_stock' => 'integer',
        'current_stock' => 'integer',
        'stock_min' => 'integer',
        'is_active' => 'boolean',
    ];

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ReceptionSupplyMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_min > 0 && $this->current_stock <= $this->stock_min;
    }

    public function stockGap(): int
    {
        return max(0, $this->planned_stock - $this->current_stock);
    }
}
