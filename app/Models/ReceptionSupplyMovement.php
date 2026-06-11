<?php

namespace App\Models;

use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionSupplyMovement extends Model
{
    protected $fillable = [
        'reception_supply_id',
        'hopital_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function supply(): BelongsTo
    {
        return $this->belongsTo(ReceptionSupply::class, 'reception_supply_id');
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
