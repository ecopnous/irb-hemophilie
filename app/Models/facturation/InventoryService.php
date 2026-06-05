<?php

namespace App\Models\facturation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryService extends Model
{
    protected $fillable = [
        'hopital_id',
        'name',
        'code',
        'manager_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(InventoryAsset::class, 'inventory_service_id');
    }
}
