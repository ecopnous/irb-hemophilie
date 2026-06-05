<?php

namespace App\Models;

use App\Models\Concerns\ScopesByHopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaboratoryConsumable extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'hopital_id',
        'name',
        'reference',
        'category',
        'unit',
        'current_stock',
        'stock_min',
        'storage_condition',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_stock' => 'integer',
        'stock_min' => 'integer',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(LaboratoryStockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->stock_min;
    }
}
