<?php

namespace App\Models\hospitalisation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalisationPayment extends Model
{
    protected $fillable = [
        'hospitalisation_id',
        'amount',
        'currency',
        'payment_mode',
        'reference',
        'paid_at',
        'comment',
        'created_by',
        'updated_by',
        'voided_at',
        'void_reason',
        'voided_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function hospitalisation(): BelongsTo
    {
        return $this->belongsTo(Hospitalisation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
