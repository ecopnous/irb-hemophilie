<?php

namespace App\Models\hospitalisation;

use App\Models\Concerns\ScopesByHopital;
use App\Models\Configs\Departement;
use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospService extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'name',
        'description',
        'departement_id',
        'hopital_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function chambres(): HasMany
    {
        return $this->hasMany(Chambre::class);
    }

    public function lits()
    {
        return $this->hasManyThrough(Lit::class, Chambre::class);
    }
}
