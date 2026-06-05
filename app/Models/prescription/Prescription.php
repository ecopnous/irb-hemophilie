<?php

namespace App\Models\prescription;

use App\Models\Configs\Hopital;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $fillable = [
        'consultation_id',
        'hopital_id',
        'dossier_patient_id',
        'reference',
        'status',
        'served_at',
        'served_by',
    ];

    protected $casts = [
        'served_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $prescription) {
            if (!$prescription->reference) {
                $nextId = (static::max('id') ?? 0) + 1;
                $prescription->reference = 'PRES-' . now()->format('ymd') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function dossierPatient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class);
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    public function medicaments(): BelongsToMany
    {
        return $this->belongsToMany(Medicament::class, 'medicament_prescription')
            ->withPivot(['qte_jour', 'nbr', 'qte_servie', 'posologie'])
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
