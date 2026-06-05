<?php

namespace App\Models;

use App\Models\Configs\Acte;
use App\Models\Configs\Assurance;
use App\Models\Configs\Departement;
use App\Models\Configs\Projet;
use App\Models\Configs\Service;
use App\Models\Concerns\ScopesByHopital;
use App\Models\facturation\Facturation;
use App\Models\hospitalisation\Hospitalisation;
use App\Models\other\Symptome;
use App\Models\prescription\StockMovement;
use App\Models\prescription\Prescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class Consultation extends Model
{
    use HasFactory;
    use ScopesByHopital;

    protected static array $periodContext = [];
    protected static ?bool $hasProjectPeriodColumn = null;

    protected $fillable = [
        'type', //*
        'type_fichier', //*
        'is_project_period',
        'is_visite_program',
        'consultation_source_id',
        'is_clore',
        'reference',
        'dossier_patient_id', //*
        'departement_id', //*
        'assurance_id',
        'projet_id',
        'service_id',
        'hopital_id', //*
        'user_id',
        'laboratoire_id',
        'imagerie_id',
        'facturation_id',
        'symptomes',
        'antecedents',
        'allergies',
        'histoire_maladie',
        'examen_clinique',
        'diagnostic_presomption',
        'diagnostic_certitude',
        'complement_anamnese',
        'plan_traitement_conduite',
        'prescription_medicale',
        'rendez_vous_medical',
        'issue',
        'autre_issue',
        'cause_issue',
        'poids',
        'temperature',
        'taille',
        'systolite',
        'perimetre_cranien',
        'perimetre_brachial',
        'frequence_cardiaque',
        'frequence_respiratoire',
        'diastolique',
        'prelevement_effectue',
        'saturation_oxygene',
        'glycemie',
        'mois',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_project_period' => 'boolean',
        'is_visite_program' => 'boolean',
        'is_clore' => 'boolean',
        'prelevement_effectue' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($patient) {
            $latest = self::latest()->first();
            $number = $latest ? $latest->id + 1 : 1;
            $l = str_starts_with((string) $patient->type, 'consultation') ? 'C' : 'D';

            $patient->reference = 'R-' . date('y') . $l . "-" . str_pad($number, 5, '0', STR_PAD_LEFT) . "";
            if (static::hasProjectPeriodColumn()) {
                $patient->is_project_period ??= static::shouldUseProjectPeriod($patient);
            } else {
                unset($patient->is_project_period);
            }

            $patient->mois ??= static::resolvePeriode($patient);
        });

        static::created(function () {
            static::$periodContext = [];
        });
    }

    public static function createWithPeriodContext(array $attributes, array $context = []): self
    {
        static::$periodContext = $context;

        try {
            return static::query()->create($attributes);
        } finally {
            static::$periodContext = [];
        }
    }

    protected static function resolvePeriode(self $consultation): string
    {
        if (static::shouldUseProjectPeriod($consultation)) {
            return static::nextProjectPeriode($consultation);
        }

        return match ($consultation->type) {
            'depistage' => static::nextSequenceForPrefix($consultation, 'D'),
            default => static::nextSequenceForPrefix($consultation, 'C'),
        };
    }

    protected static function shouldUseProjectPeriod(self $consultation): bool
    {
        return (bool) (($consultation->is_project_period ?? null) ?? static::$periodContext['use_project_period'] ?? false)
            && filled($consultation->projet_id);
    }

    protected static function nextProjectPeriode(self $consultation): string
    {
        $prefix = static::projectPrefix($consultation);

        $count = static::query()
            ->where('dossier_patient_id', $consultation->dossier_patient_id)
            ->where('projet_id', $consultation->projet_id)
            ->when(static::hasProjectPeriodColumn(), fn($query) => $query->where('is_project_period', true))
            ->count();

        return $prefix . ($count + 1);
    }

    protected static function nextSequenceForPrefix(self $consultation, string $prefix): string
    {
        $count = static::query()
            ->where('dossier_patient_id', $consultation->dossier_patient_id)
            ->where('type', $consultation->type)
            ->when(static::hasProjectPeriodColumn(), function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNull('is_project_period')
                        ->orWhere('is_project_period', false);
                });
            })
            ->count();

        return $prefix . ($count + 1);
    }

    protected static function hasProjectPeriodColumn(): bool
    {
        if (static::$hasProjectPeriodColumn !== null) {
            return static::$hasProjectPeriodColumn;
        }

        return static::$hasProjectPeriodColumn = Schema::hasColumn('consultations', 'is_project_period');
    }

    protected static function projectPrefix(self $consultation): string
    {
        $project = $consultation->projet ?: Projet::query()->find($consultation->projet_id);
        $source = trim((string) ($project?->name ?: $project?->reference ?: 'P'));
        $firstCharacter = mb_substr($source, 0, 1);

        return strtoupper($firstCharacter ?: 'P');
    }

    public function getDateDebutAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M, Y') : null;
    }

    public function getDateUpdateAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d M, Y') : null;
    }


    /**
     * ********************* Optimisation des requettes ********************
     ***************************************************************************
     */
    public function scopeProgrammed(Builder $query): Builder
    {
        return $query->where('is_visite_program', true);
    }

    public function scopeNotProgrammed(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereNull('is_visite_program')
                ->orWhere('is_visite_program', false);
        });
    }
    public function scopeToDay(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeOldDays(Builder $query, int $nbr_day): Builder
    {
        return $query->whereDate('created_at', '>=', now()->subDays(30));
    }
    public function scopeThisHopital(Builder $query): Builder
    {
        return $query->whereHopitalId(current_hopital_id());
    }
    public function scopeOld(Builder $query): Builder
    {
        return $query->whereDate('created_at', '<=', today());
    }



    /**
     * ********************* Relation avec les autres tables ********************
     ***************************************************************************
     */

    public function actes(): BelongsToMany
    {
        return $this->belongsToMany(Acte::class)
            ->withPivot(
                'ref',
                'montant',
                'prise_en_charge',
                'payer',
                'valide',
                'user_id',
                'note_clinique',
                'commentaire',
                'resultat',
                'clinique',
                'protocole',
                'cloture',
            );
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
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function symptomeItems(): BelongsToMany
    {
        return $this->belongsToMany(Symptome::class, 'consultation_symptome');
    }

    public function assurance(): BelongsTo
    {
        return $this->belongsTo(Assurance::class);
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function laboratoire(): HasOne
    {
        return $this->hasOne(Laboratoire::class);
    }

    public function imagerie(): HasOne
    {
        return $this->hasOne(Imagerie::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function facturation(): BelongsTo
    {
        return $this->belongsTo(Facturation::class);
    }

    public function hospitalisation(): BelongsTo
    {
        return $this->belongsTo(Hospitalisation::class);
    }

    public function consultationSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'consultation_source_id');
    }

    public function programmedRendezVous(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'consultation_source_id')
            ->where('is_visite_program', true)
            ->orderBy('created_at');
    }
}
