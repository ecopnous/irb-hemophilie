<?php

namespace App\Services;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function receptionQuery(array $filters = [], ?int $hopitalId = null): Builder
    {
        $hopitalId ??= current_hopital_id();

        $needsPatientJoin = $this->needsPatientJoin($filters);

        $query = Consultation::query()
            ->when($needsPatientJoin, function (Builder $builder) {
                $builder
                    ->leftJoin('dossier_patients as dp', 'dp.id', '=', 'consultations.dossier_patient_id')
                    ->select('consultations.*');
            })
            ->when(($filters['search'] ?? '') !== '', function (Builder $builder) use ($filters, $needsPatientJoin) {
                $term = '%' . $filters['search'] . '%';

                $builder->where(function (Builder $inner) use ($term, $needsPatientJoin) {
                    $inner->where('consultations.reference', 'like', $term);

                    if ($needsPatientJoin) {
                        $inner->orWhere('dp.nom', 'like', $term)
                            ->orWhere('dp.postnom', 'like', $term)
                            ->orWhere('dp.prenom', 'like', $term)
                            ->orWhere('dp.nin', 'like', $term)
                            ->orWhere('dp.ins', 'like', $term);
                    } else {
                        $inner->orWhereHas('dossierPatient', function (Builder $patientQuery) use ($term) {
                            $patientQuery->where('nom', 'like', $term)
                                ->orWhere('postnom', 'like', $term)
                                ->orWhere('prenom', 'like', $term)
                                ->orWhere('nin', 'like', $term)
                                ->orWhere('ins', 'like', $term);
                        });
                    }
                });
            })
            ->when(($filters['type'] ?? '') !== '', fn (Builder $builder) => $builder->where('consultations.type', $filters['type']))
            ->when(($filters['genre'] ?? '') !== '', function (Builder $builder) use ($filters, $needsPatientJoin) {
                if ($needsPatientJoin) {
                    $builder->where('dp.genre', $filters['genre']);
                } else {
                    $builder->whereHas('dossierPatient', fn (Builder $patientQuery) => $patientQuery->where('genre', $filters['genre']));
                }
            })
            ->when(filled($filters['user_id'] ?? null), fn (Builder $builder) => $builder->where('consultations.user_id', $filters['user_id']))
            ->when(filled($filters['departement_id'] ?? null), fn (Builder $builder) => $builder->where('consultations.departement_id', $filters['departement_id']))
            ->when(($filters['assignment'] ?? '') === 'assigned', fn (Builder $builder) => $builder->whereNotNull('consultations.user_id'))
            ->when(($filters['assignment'] ?? '') === 'unassigned', fn (Builder $builder) => $builder->whereNull('consultations.user_id'))
            ->when(filled($filters['province_id'] ?? null), function (Builder $builder) use ($filters, $needsPatientJoin) {
                if ($needsPatientJoin) {
                    $builder->where('dp.province_id', $filters['province_id']);
                } else {
                    $builder->whereHas('dossierPatient', fn (Builder $patientQuery) => $patientQuery->where('province_id', $filters['province_id']));
                }
            })
            ->when(filled($filters['ville_id'] ?? null), function (Builder $builder) use ($filters, $needsPatientJoin) {
                if ($needsPatientJoin) {
                    $builder->where('dp.ville_id', $filters['ville_id']);
                } else {
                    $builder->whereHas('dossierPatient', fn (Builder $patientQuery) => $patientQuery->where('ville_id', $filters['ville_id']));
                }
            })
            ->when(filled($filters['commune_id'] ?? null), function (Builder $builder) use ($filters, $needsPatientJoin) {
                if ($needsPatientJoin) {
                    $builder->where('dp.commune_id', $filters['commune_id']);
                } else {
                    $builder->whereHas('dossierPatient', fn (Builder $patientQuery) => $patientQuery->where('commune_id', $filters['commune_id']));
                }
            })
            ->when(filled($filters['age_min'] ?? null), function (Builder $builder) use ($filters, $needsPatientJoin) {
                $maxBirthDate = now()->subYears((int) $filters['age_min']);

                if ($needsPatientJoin) {
                    $builder->where('dp.date_naissance', '<=', $maxBirthDate);
                } else {
                    $builder->whereHas('dossierPatient', fn (Builder $patientQuery) => $patientQuery->where('date_naissance', '<=', $maxBirthDate));
                }
            })
            ->when(filled($filters['age_max'] ?? null), function (Builder $builder) use ($filters, $needsPatientJoin) {
                $minBirthDate = now()->subYears((int) $filters['age_max'] + 1);

                if ($needsPatientJoin) {
                    $builder->where('dp.date_naissance', '>=', $minBirthDate);
                } else {
                    $builder->whereHas('dossierPatient', fn (Builder $patientQuery) => $patientQuery->where('date_naissance', '>=', $minBirthDate));
                }
            })
            ->when(filled($filters['date_start'] ?? null), fn (Builder $builder) => $builder->where(
                'consultations.created_at',
                '>=',
                Carbon::parse($filters['date_start'])->startOfDay()
            ))
            ->when(filled($filters['date_end'] ?? null), fn (Builder $builder) => $builder->where(
                'consultations.created_at',
                '<=',
                Carbon::parse($filters['date_end'])->endOfDay()
            ))
            ->old()
            ->where('consultations.hopital_id', $hopitalId);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total:int, depistages:int, sans_medecin:int, consultations:int, programmees:int, aujourd_hui:int}
     */
    public function receptionStats(array $filters = [], ?int $hopitalId = null, bool $useCache = true): array
    {
        $hopitalId ??= current_hopital_id();
        $cacheKey = $this->statsCacheKey($hopitalId, $filters);

        $resolver = fn (): array => $this->aggregateStats($this->receptionQuery($filters, $hopitalId));

        if (!$useCache) {
            return $resolver();
        }

        return Cache::remember($cacheKey, now()->addMinute(), $resolver);
    }

    public function forgetHopitalCache(?int $hopitalId): void
    {
        if (!$hopitalId) {
            return;
        }

        Cache::forget("dashboard.overview.{$hopitalId}");
        Cache::forget($this->statsCacheKey($hopitalId, []));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function statsCacheKey(int $hopitalId, array $filters): string
    {
        ksort($filters);

        return 'dashboard.stats.' . $hopitalId . '.' . md5(json_encode($filters));
    }

    /**
     * @return array{total:int, depistages:int, sans_medecin:int, consultations:int, programmees:int, aujourd_hui:int}
     */
    public function aggregateStats(Builder $baseQuery): array
    {
        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();
        $driver = DB::connection()->getDriverName();

        $row = (clone $baseQuery)
            ->toBase()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN consultations.type = 'depistage' THEN 1 ELSE 0 END) as depistages,
                SUM(CASE WHEN consultations.user_id IS NULL THEN 1 ELSE 0 END) as sans_medecin,
                SUM(CASE WHEN consultations.type = 'consultation' THEN 1 ELSE 0 END) as consultations,
                SUM(CASE WHEN consultations.is_visite_program = ? THEN 1 ELSE 0 END) as programmees,
                SUM(CASE WHEN consultations.created_at >= ? AND consultations.created_at <= ? THEN 1 ELSE 0 END) as aujourd_hui
            ", [$driver === 'sqlite' ? 1 : true, $todayStart, $todayEnd])
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'depistages' => (int) ($row->depistages ?? 0),
            'sans_medecin' => (int) ($row->sans_medecin ?? 0),
            'consultations' => (int) ($row->consultations ?? 0),
            'programmees' => (int) ($row->programmees ?? 0),
            'aujourd_hui' => (int) ($row->aujourd_hui ?? 0),
        ];
    }

    /**
     * @return array{triage:int, laboratoire:int, facturation:int, imagerie:int}
     */
    public function overview(?int $hopitalId = null, bool $useCache = true): array
    {
        $hopitalId ??= current_hopital_id();

        $resolver = fn (): array => $this->overviewAggregate($hopitalId);

        if (!$useCache) {
            return $resolver();
        }

        return Cache::remember("dashboard.overview.{$hopitalId}", now()->addMinute(), $resolver);
    }

    /**
     * @return array{triage:int, laboratoire:int, facturation:int, imagerie:int}
     */
    private function overviewAggregate(int $hopitalId): array
    {
        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();

        $row = Consultation::query()
            ->where('hopital_id', $hopitalId)
            ->selectRaw("
                SUM(CASE WHEN created_at >= ? AND created_at <= ? AND user_id IS NULL AND type != 'depistage' THEN 1 ELSE 0 END) as triage,
                SUM(CASE WHEN laboratoire_id IS NOT NULL THEN 1 ELSE 0 END) as laboratoire,
                SUM(CASE WHEN facturation_id IS NOT NULL THEN 1 ELSE 0 END) as facturation,
                SUM(CASE WHEN imagerie_id IS NOT NULL THEN 1 ELSE 0 END) as imagerie
            ", [$todayStart, $todayEnd])
            ->first();

        return [
            'triage' => (int) ($row->triage ?? 0),
            'laboratoire' => (int) ($row->laboratoire ?? 0),
            'facturation' => (int) ($row->facturation ?? 0),
            'imagerie' => (int) ($row->imagerie ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function needsPatientJoin(array $filters): bool
    {
        return ($filters['genre'] ?? '') !== ''
            || filled($filters['province_id'] ?? null)
            || filled($filters['ville_id'] ?? null)
            || filled($filters['commune_id'] ?? null)
            || filled($filters['age_min'] ?? null)
            || filled($filters['age_max'] ?? null)
            || (($filters['search'] ?? '') !== '');
    }
}
