<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PacquetSoin extends Model
{
    protected $table = 'pacquet_soins';

    protected $fillable = [
        'name',
        'description',
        'paiement_directe',
        'categorisation_id',
    ];

    protected $casts = [
        'paiement_directe' => 'boolean',
    ];

    public function categorisation(): BelongsTo
    {
        return $this->belongsTo(Categorisation::class);
    }

    public function actes(): BelongsToMany
    {
        return $this->belongsToMany(Acte::class, 'acte_pacquet_soin')
            ->withTimestamps();
    }
}
