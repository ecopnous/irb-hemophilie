<?php

namespace App\Models\prescription;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicament extends Model
{
    protected $fillable = [
        'reference',
        'name',
        'classe',
        'fournisseur',
        'fabricant',
        'pays_provenance',
        'dci',
        'amm_numero',
        'amm_duree_validiter',
        'amm_organisme',
        'forme',
        'dosage',
        'conditionnement',
        'amm_date_fin',
        'amm_date',
        'is_active',
        'stock_min',
        'expiration_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amm_date_fin' => 'datetime',
        'amm_date' => 'datetime',
        'expiration_date' => 'date',
    ];

    public function pharmacies(): BelongsToMany
    {
        return $this->belongsToMany(Pharmacie::class, 'medicament_pharmacie')
            ->withPivot(['quantiter', 'montant'])
            ->withTimestamps();
    }

    public function prescriptions(): BelongsToMany
    {
        return $this->belongsToMany(Prescription::class, 'medicament_prescription')
            ->withPivot(['qte_jour', 'nbr', 'qte_servie', 'posologie'])
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
