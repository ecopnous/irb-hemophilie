<?php

namespace App\Models\Configs;

use App\Models\Consultation;
use App\Models\consultation\ConsultInitiale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acte extends Model
{
    protected $fillable = [
        'code',
        'name',
        'montant',
        'base_price',
        'is_active',
        'departement_id',
        'service_id',
        'updated_by',
        'unite',
        'min',
        'max',
        'homme_min',
        'homme_max',
        'femme_min',
        'femme_max',
        'is_delete',
    ];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function paquets(): BelongsToMany
    {
        return $this->belongsToMany(PacquetSoin::class, 'acte_pacquet_soin')
            ->withTimestamps();
    }

    public function consultations()
    {
        return $this->belongsToMany(Consultation::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(MedicalActPriceHistory::class);
    }
}
