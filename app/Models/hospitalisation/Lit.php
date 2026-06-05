<?php

namespace App\Models\hospitalisation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lit extends Model
{
    protected $fillable = [
        'name',
        'reference',
        'description',
        'statut',
        'is_active',
        'chambre_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }

    public function hospitalisations(): HasMany
    {
        return $this->hasMany(Hospitalisation::class);
    }

    public function isDisponible(): bool
    {
        return $this->statut === 'disponible' && $this->is_active;
    }
}
