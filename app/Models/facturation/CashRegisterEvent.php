<?php

namespace App\Models\facturation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterEvent extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'event_type',
        'amount',
        'currency',
        'performed_by',
        'performed_at',
        'note',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'performed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
