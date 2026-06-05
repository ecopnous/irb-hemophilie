<?php

namespace App\Models\facturation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCategory extends Model
{
    protected $fillable = [
        'hopital_id',
        'name',
        'code',
        'description',
        'default_useful_life_years',
        'default_depreciation_rate',
        'is_active',
    ];

    protected $casts = [
        'default_depreciation_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(InventoryAsset::class, 'inventory_category_id');
    }
}
