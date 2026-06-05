<?php

namespace App\Models\facturation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashOperation extends Model
{
    protected $fillable = [
        'operation_type',
        'event_type',
        'amount',
        'currency',
        'performed_at',
        'reference',
        'note',
        'created_by',
        'updated_by',
        'voided_at',
        'void_reason',
        'voided_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'performed_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
