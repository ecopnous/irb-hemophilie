<?php

namespace App\Models;

use App\Models\Concerns\ScopesByHopital;
use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionBaseService extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'hopital_id',
        'code',
        'name',
        'category',
        'description',
        'price',
        'currency',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
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
}
