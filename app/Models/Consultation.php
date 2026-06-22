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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Consultation extends Model
{
    use HasFactory;
    use ScopesByHopital;

    protected static array $periodContext = [];
    protected static ?bool $hasProjectPeriodColumn = null;

    protected $fillable = [
        'type', //*
        'type_visite', //*
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
        static::creating(function (self $consultation) {
            $lockKey = 'consultation-create:' . ($consultation->hopital_id ?? 'global');

            Cache::lock($lockKey, 10)->block(5, function () use ($consultation) {
                if (blank($consultation->reference)) {
                    $consultation->reference = static::generateReference($consultation);
                }

                if (static::hasProjectPeriodColumn()) {
                    $consultation->is_project_period ??= static::shouldUseProjectPeriod($consultation);
                } else {
                    unset($consultation->is_project_period);
                }

                $consultation->mois ??= static::resolvePeriode($consultation);
            });
        });

        static::created(function () {
            static::$periodContext = [];
        });
    }

    protected static function generateReference(self $consultation): string
    {
        $letter = str_starts_with((string) $consultation->type, 'consultation') ? 'C' : 'D';
        $number = (int) DB::table('consultations')->max('id') + 1;

        return sprintf('R-%s%s-%05d', date('y'), $letter, $number);
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

        $query = static::query()
            ->where('dossier_patient_id', $consultation->dossier_patient_id)
            ->where('projet_id', $consultation->projet_id)
            ->when(static::hasProjectPeriodColumn(), fn ($q) => $q->where('is_project_period', true));

        return $prefix . (static::maxMoisSequence($query, $prefix) + 1);
    }

    protected static function nextSequenceForPrefix(self $consultation, string $prefix): string
    {
        $query = static::query()
            ->where('dossier_patient_id', $consultation->dossier_patient_id)
            ->where('type', $consultation->type)
            ->when(static::hasProjectPeriodColumn(), function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNull('is_project_period')
                        ->orWhere('is_project_period', false);
                });
            });

        return $prefix . (static::maxMoisSequence($query, $prefix) + 1);
    }

    protected static function maxMoisSequence(Builder $query, string $prefix): int
    {
        $prefixLength = mb_strlen($prefix);
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['sqlite', 'mysql', 'pgsql'], true)) {
            $max = (clone $query)
                ->where('mois', 'like', $prefix . '%')
                ->selectRaw(static::moisNumericMaxExpression($prefixLength) . ' as max_seq')
                ->value('max_seq');

            return (int) ($max ?? 0);
        }

        return (clone $query)
            ->where('mois', 'like', $prefix . '%')
            ->pluck('mois')
            ->map(fn (?string $mois) => (int) mb_substr((string) $mois, $prefixLength))
            ->max() ?? 0;
    }

    protected static function moisNumericMaxExpression(int $prefixLength): string
    {
        $start = $prefixLength + 1;
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "MAX(CAST(SUBSTR(mois, {$start}) AS INTEGER))",
            'mysql' => "MAX(CAST(SUBSTRING(mois, {$start}) AS UNSIGNED))",
            'pgsql' => "MAX(CAST(SUBSTRING(mois FROM {$start}) AS INTEGER))",
            default => 'MAX(0)',
        };
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
        return $query->whereBetween('created_at', [
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
        ]);
    }

    public function scopeOldDays(Builder $query, int $nbr_day): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($nbr_day)->startOfDay());
    }
    public function scopeThisHopital(Builder $query): Builder
    {
        return $query->whereHopitalId(current_hopital_id());
    }
    public function scopeOld(Builder $query): Builder
    {
        return $query->where('created_at', '<=', today()->endOfDay());
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

    public function clinicalExam(): HasOne
    {
        return $this->hasOne(ConsultationClinicalExam::class);
    }

    public function clinicalExamValues(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsultationClinicalExamValue::class);
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

    public function effectiveAssurance(): ?Assurance
    {
        $this->loadMissing(['projet.assurance.categorisation', 'assurance.categorisation']);

        return $this->projet?->assurance ?? $this->assurance;
    }

    public function coverageRate(): float
    {
        return (float) ($this->effectiveAssurance()?->categorisation?->pourcentage ?? 0);
    }

    public function coverageCategoryName(): string
    {
        return (string) ($this->effectiveAssurance()?->categorisation?->name ?? 'N/A');
    }

    public function scopeForAssurance(Builder $query, int $assuranceId): Builder
    {
        return $query->where(function (Builder $inner) use ($assuranceId) {
            $inner->where('assurance_id', $assuranceId)
                ->orWhereHas('projet', fn (Builder $projetQuery) => $projetQuery->where('assurance_id', $assuranceId));
        });
    }
}
