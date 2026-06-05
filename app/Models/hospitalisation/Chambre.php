<?php

namespace App\Models\hospitalisation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chambre extends Model
{
    protected $fillable = [
        'name',
        'reference',
        'type',
        'montant',
        'unite',
        'description',
        'hosp_service_id',
        'is_active',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(HospService::class, 'hosp_service_id');
    }

    public function lits(): HasMany
    {
        return $this->hasMany(Lit::class);
    }

    public function hospitalisations(): HasMany
    {
        return $this->hasMany(Hospitalisation::class);
    }
}
