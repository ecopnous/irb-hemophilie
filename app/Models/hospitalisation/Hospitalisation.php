<?php

namespace App\Models\hospitalisation;

use App\Models\Concerns\ScopesByHopital;
use App\Models\Configs\Departement;
use App\Models\Configs\Hopital;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospitalisation extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'montant',
        'payer',
        'unite',
        'moyen_paiement',
        'autre_moyen_paiement',
        'consultation_id',
        'dossier_patient_id',
        'departement_id',
        'hosp_service_id',
        'chambre_id',
        'lit_id',
        'date_entree',
        'date_sortie',
        'date_paiement',
        'hopital_id',
        'statut',
        'motif',
        'note_sortie',
        'total_amount',
        'paid_amount',
        'due_amount',
        'currency',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'payer' => 'boolean',
        'date_entree' => 'datetime',
        'date_sortie' => 'datetime',
        'date_paiement' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $hospitalisation) {
            if ($hospitalisation->lit_id && !$hospitalisation->chambre_id) {
                $hospitalisation->chambre_id = $hospitalisation->lit?->chambre_id;
            }

            if ($hospitalisation->chambre_id && !$hospitalisation->hosp_service_id) {
                $hospitalisation->hosp_service_id = $hospitalisation->chambre?->hosp_service_id;
            }

            if ($hospitalisation->chambre_id && (blank($hospitalisation->montant) || (float) $hospitalisation->montant === 0.0)) {
                $hospitalisation->montant = $hospitalisation->chambre?->montant ?? 0;
                $hospitalisation->unite = $hospitalisation->unite ?: ($hospitalisation->chambre?->unite ?? 'jour');
            }

            if (blank($hospitalisation->total_amount) || (float) $hospitalisation->total_amount === 0.0) {
                $hospitalisation->total_amount = (float) ($hospitalisation->montant ?? 0);
            }
            $hospitalisation->paid_amount = (float) ($hospitalisation->paid_amount ?? 0);
            $hospitalisation->due_amount = max(0, (float) $hospitalisation->total_amount - (float) $hospitalisation->paid_amount);
            $hospitalisation->payer = $hospitalisation->due_amount <= 0;
            $hospitalisation->currency = $hospitalisation->currency ?: 'USD';

            if ($hospitalisation->date_sortie && $hospitalisation->statut === 'active') {
                $hospitalisation->statut = 'terminee';
            }
        });

        static::saved(function (self $hospitalisation) {
            $originalLitId = $hospitalisation->getOriginal('lit_id');

            if ($originalLitId && $originalLitId !== $hospitalisation->lit_id) {
                static::refreshLitStatut($originalLitId);
            }

            if ($hospitalisation->lit_id) {
                static::refreshLitStatut($hospitalisation->lit_id);
            }
        });

        static::deleted(function (self $hospitalisation) {
            if ($hospitalisation->lit_id) {
                static::refreshLitStatut($hospitalisation->lit_id);
            }
        });
    }

    public static function refreshLitStatut(?int $litId): void
    {
        if (!$litId) {
            return;
        }

        $hasActiveHospitalisation = static::query()
            ->where('lit_id', $litId)
            ->where('statut', 'active')
            ->whereNull('date_sortie')
            ->exists();

        Lit::query()
            ->whereKey($litId)
            ->update([
                'statut' => $hasActiveHospitalisation ? 'occupe' : 'disponible',
            ]);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function dossierPatient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(HospService::class, 'hosp_service_id');
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }

    public function lit(): BelongsTo
    {
        return $this->belongsTo(Lit::class);
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HospitalisationPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->statut === 'active' && $this->date_sortie === null;
    }
}
