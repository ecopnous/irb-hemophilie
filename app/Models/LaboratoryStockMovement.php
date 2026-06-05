<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryStockMovement extends Model
{
    protected $fillable = [
        'laboratory_consumable_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference',
        'lot_number',
        'expiration_date',
        'destination',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(LaboratoryConsumable::class, 'laboratory_consumable_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
