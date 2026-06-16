<?php

namespace App\Services;

use App\Models\Configs\Departement;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\hospitalisation\Hospitalisation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PatientEvolutionMetricsService
{
    /**
     * @param  array{
     *     period?: string,
     *     date_start?: ?string,
     *     date_end?: ?string,
     *     departement_id?: string,
     *     consultation_type?: string,
     *     user_id?: string,
     *     compare_mode?: string,
     * }  $filters
     * @return array<string, mixed>
     */
    public function dashboard(int $patientId, array $filters = []): array
    {
        return Cache::remember(
            $this->cacheKey($patientId, $filters),
            now()->addMinutes(2),
            fn () => $this->serializable($this->buildDashboard($patientId, $filters)),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function forgetCache(int $patientId, array $filters = []): void
    {
        Cache::forget($this->cacheKey($patientId, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Consultation>
     */
    public function consultationsForAnalysis(int $patientId, array $filters = [], int $limit = 25): Collection
    {
        $range = $this->resolvePeriod($filters);

        return $this->baseQuery($patientId, $filters, $range['start'], $range['end'])
            ->with(['departement:id,name', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildDashboard(int $patientId, array $filters): array
    {
        $patient = DossierPatient::query()->findOrFail($patientId);
        $range = $this->resolvePeriod($filters);
        $query = $this->baseQuery($patientId, $filters, $range['start'], $range['end']);

        $consultations = (clone $query)
            ->with(['departement:id,name', 'user:id,name', 'laboratoire', 'imagerie', 'prescription'])
            ->orderBy('created_at')
            ->get();

        $first = $consultations->first();
        $last = $consultations->last();
        $comparePair = $this->comparePair($consultations, $filters['compare_mode'] ?? 'first_last');

        return [
            'patient' => [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
                'nin' => $patient->nin,
                'age' => $patient->age,
                'genre' => $patient->genre,
            ],
            'periodLabel' => $range['label'],
            'filters' => [
                'departements' => $this->filterOptions($patientId, 'departement'),
                'doctors' => $this->filterOptions($patientId, 'doctor'),
            ],
            'kpis' => $this->kpis($consultations, $patientId, $range['start'], $range['end']),
            'activity' => $this->activityAnalytics($consultations),
            'vitals' => $this->vitalsAnalytics($consultations),
            'clinical' => $this->clinicalAnalytics($consultations),
            'exams' => $this->examsAnalytics($consultations, $patientId, $range['start'], $range['end']),
            'comparison' => $this->comparisonAnalytics($comparePair),
            'timeline' => $this->timeline($consultations),
            'insights' => $this->insights($consultations, $first, $last, $comparePair),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: ?\Carbon\CarbonInterface, end: \Carbon\CarbonInterface, label: string}
     */
    private function resolvePeriod(array $filters): array
    {
        $period = $filters['period'] ?? 'all';
        $end = filled($filters['date_end'] ?? null)
            ? Carbon::parse($filters['date_end'])->endOfDay()
            : now()->endOfDay();

        return match ($period) {
            '3months' => [
                'start' => now()->subMonths(3)->startOfDay(),
                'end' => $end,
                'label' => '3 derniers mois',
            ],
            '6months' => [
                'start' => now()->subMonths(6)->startOfDay(),
                'end' => $end,
                'label' => '6 derniers mois',
            ],
            'year' => [
                'start' => now()->subYear()->startOfDay(),
                'end' => $end,
                'label' => '12 derniers mois',
            ],
            '2years' => [
                'start' => now()->subYears(2)->startOfDay(),
                'end' => $end,
                'label' => '24 derniers mois',
            ],
            'custom' => [
                'start' => Carbon::parse($filters['date_start'] ?? today())->startOfDay(),
                'end' => $end,
                'label' => 'Période personnalisée',
            ],
            default => [
                'start' => null,
                'end' => $end,
                'label' => 'Historique complet',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(int $patientId, array $filters, ?\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): Builder
    {
        return Consultation::query()
            ->where('dossier_patient_id', $patientId)
            ->when($start, fn (Builder $q) => $q->whereBetween('created_at', [$start, $end]))
            ->when(filled($filters['departement_id'] ?? null), fn (Builder $q) => $q->where('departement_id', $filters['departement_id']))
            ->when(filled($filters['consultation_type'] ?? null), fn (Builder $q) => $q->where('type', $filters['consultation_type']))
            ->when(filled($filters['user_id'] ?? null), fn (Builder $q) => $q->where('user_id', $filters['user_id']));
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @return array<string, int|float|string|null>
     */
    private function kpis(Collection $consultations, int $patientId, ?\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): array
    {
        $lastVisit = $consultations->last();
        $hospitalizations = Hospitalisation::query()
            ->where('dossier_patient_id', $patientId)
            ->when($start, fn ($q) => $q->whereBetween('created_at', [$start, $end]))
            ->count();

        $weights = $consultations->pluck('poids')->filter(fn ($v) => filled($v));
        $systolics = $consultations->pluck('systolite')->filter(fn ($v) => filled($v));

        return [
            'total_consultations' => $consultations->count(),
            'first_visit' => $consultations->first()?->created_at?->format('d/m/Y'),
            'last_visit' => $lastVisit?->created_at?->format('d/m/Y'),
            'days_since_last_visit' => $lastVisit ? (int) $lastVisit->created_at->diffInDays(now()) : null,
            'departments_count' => $consultations->pluck('departement_id')->filter()->unique()->count(),
            'doctors_count' => $consultations->pluck('user_id')->filter()->unique()->count(),
            'lab_exams' => $consultations->whereNotNull('laboratoire_id')->count(),
            'imaging_exams' => $consultations->whereNotNull('imagerie_id')->count(),
            'prescriptions' => $consultations->whereNotNull('prescription_id')->count(),
            'hospitalizations' => $hospitalizations,
            'programmed_visits' => $consultations->where('is_visite_program', true)->count(),
            'latest_weight' => $weights->last(),
            'avg_weight' => $weights->isEmpty() ? null : round((float) $weights->avg(), 1),
            'latest_systolic' => $systolics->last(),
            'avg_systolic' => $systolics->isEmpty() ? null : round((float) $systolics->avg(), 0),
            'latest_temperature' => $consultations->pluck('temperature')->filter(fn ($v) => filled($v))->last(),
            'latest_glycemia' => $consultations->pluck('glycemie')->filter(fn ($v) => filled($v))->last(),
        ];
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @return array<string, mixed>
     */
    private function activityAnalytics(Collection $consultations): array
    {
        $monthly = $consultations
            ->groupBy(fn (Consultation $c) => $c->created_at->format('Y-m'))
            ->map(fn (Collection $group) => $group->count())
            ->sortKeys();

        $byDepartment = $consultations
            ->groupBy(fn (Consultation $c) => $c->departement?->name ?? 'Non renseigné')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->take(8)
            ->map(fn ($total, $label) => ['label' => $label, 'value' => $total])
            ->values()
            ->all();

        $byType = $consultations
            ->groupBy(fn (Consultation $c) => $c->type === 'depistage' ? 'Dépistage / Urgence' : 'Consultation')
            ->map(fn (Collection $group) => $group->count());

        return [
            'monthly_trend' => [
                'labels' => $monthly->keys()->map(fn ($m) => Carbon::parse($m . '-01')->format('m/Y'))->values()->all(),
                'values' => $monthly->values()->all(),
            ],
            'by_department' => $byDepartment,
            'by_type' => [
                'labels' => $byType->keys()->all(),
                'values' => $byType->values()->all(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @return array<string, mixed>
     */
    private function vitalsAnalytics(Collection $consultations): array
    {
        $labels = $consultations->map(fn (Consultation $c) => $c->created_at->format('d/m/y'))->all();

        return [
            'labels' => $labels,
            'weight' => $this->vitalSeries($consultations, 'poids'),
            'height' => $this->vitalSeries($consultations, 'taille'),
            'temperature' => $this->vitalSeries($consultations, 'temperature'),
            'systolic' => $this->vitalSeries($consultations, 'systolite'),
            'diastolic' => $this->vitalSeries($consultations, 'diastolique'),
            'heart_rate' => $this->vitalSeries($consultations, 'frequence_cardiaque'),
            'respiratory_rate' => $this->vitalSeries($consultations, 'frequence_respiratoire'),
            'oxygen_saturation' => $this->vitalSeries($consultations, 'saturation_oxygene'),
            'glycemia' => $this->vitalSeries($consultations, 'glycemie'),
        ];
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @return array<int, float|null>
     */
    private function vitalSeries(Collection $consultations, string $field): array
    {
        return $consultations
            ->map(fn (Consultation $c) => filled($c->{$field}) ? (float) $c->{$field} : null)
            ->all();
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @return array<string, mixed>
     */
    private function clinicalAnalytics(Collection $consultations): array
    {
        $diagnostics = $consultations
            ->pluck('diagnostic_presomption')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(6)
            ->map(fn ($total, $label) => ['label' => Str::limit((string) $label, 40), 'value' => $total])
            ->values()
            ->all();

        $issues = $consultations
            ->pluck('issue')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn ($total, $label) => ['label' => (string) $label, 'value' => $total])
            ->values()
            ->all();

        return [
            'top_diagnostics' => $diagnostics,
            'outcomes' => $issues,
            'closed_consultations' => $consultations->where('is_clore', true)->count(),
            'open_consultations' => $consultations->where('is_clore', false)->count(),
        ];
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @return array<string, mixed>
     */
    private function examsAnalytics(Collection $consultations, int $patientId, ?\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): array
    {
        $labTrend = $consultations
            ->filter(fn (Consultation $c) => $c->laboratoire_id !== null)
            ->groupBy(fn (Consultation $c) => $c->created_at->format('Y-m'))
            ->map(fn (Collection $group) => $group->count())
            ->sortKeys();

        $actes = $consultations
            ->loadMissing('actes')
            ->flatMap(fn (Consultation $c) => $c->actes)
            ->countBy('name')
            ->sortDesc()
            ->take(6)
            ->map(fn ($total, $label) => ['label' => Str::limit((string) $label, 35), 'value' => $total])
            ->values()
            ->all();

        return [
            'lab_trend' => [
                'labels' => $labTrend->keys()->map(fn ($m) => Carbon::parse($m . '-01')->format('m/Y'))->values()->all(),
                'values' => $labTrend->values()->all(),
            ],
            'top_actes' => $actes,
            'imaging_count' => $consultations->whereNotNull('imagerie_id')->count(),
        ];
    }

    /**
     * @param  array{first: ?Consultation, last: ?Consultation, label: string}|null  $pair
     * @return array<string, mixed>
     */
    private function comparisonAnalytics(?array $pair): array
    {
        if (! $pair || ! $pair['first'] || ! $pair['last']) {
            return [
                'label' => $pair['label'] ?? 'Comparaison indisponible',
                'rows' => [],
            ];
        }

        $first = $pair['first'];
        $last = $pair['last'];
        $metrics = [
            'Poids (kg)' => 'poids',
            'Taille (cm)' => 'taille',
            'Température (°C)' => 'temperature',
            'Tension SYS (mmHg)' => 'systolite',
            'Tension DIA (mmHg)' => 'diastolique',
            'FC (bpm)' => 'frequence_cardiaque',
            'FR (/min)' => 'frequence_respiratoire',
            'SpO2 (%)' => 'saturation_oxygene',
            'Glycémie' => 'glycemie',
        ];

        $rows = [];

        foreach ($metrics as $label => $field) {
            $from = $first->{$field};
            $to = $last->{$field};

            if (! filled($from) && ! filled($to)) {
                continue;
            }

            $delta = (filled($from) && filled($to)) ? round((float) $to - (float) $from, 1) : null;

            $rows[] = [
                'metric' => $label,
                'first' => $from,
                'last' => $to,
                'delta' => $delta,
            ];
        }

        return [
            'label' => $pair['label'],
            'first_date' => $first->created_at->format('d/m/Y'),
            'last_date' => $last->created_at->format('d/m/Y'),
            'rows' => $rows,
        ];
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @return array<int, array<string, mixed>>
     */
    private function timeline(Collection $consultations): array
    {
        return $consultations
            ->sortByDesc('created_at')
            ->take(8)
            ->map(fn (Consultation $c) => [
                'id' => $c->id,
                'date' => $c->created_at->format('d/m/Y H:i'),
                'reference' => $c->reference,
                'department' => $c->departement?->name ?? '-',
                'doctor' => $c->user?->name ?? '-',
                'diagnostic' => Str::limit((string) ($c->diagnostic_presomption ?: '-'), 60),
                'type' => $c->type === 'depistage' ? 'Dépistage' : 'Consultation',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @param  array{first: ?Consultation, last: ?Consultation, label: string}|null  $pair
     * @return array<string, mixed>
     */
    private function insights(Collection $consultations, ?Consultation $first, ?Consultation $last, ?array $pair): array
    {
        $trends = [];
        $alerts = [];

        if ($consultations->isEmpty()) {
            return [
                'summary' => 'Aucune consultation ne correspond aux filtres sélectionnés.',
                'trends' => [],
                'alerts' => ['Affinez les filtres ou élargissez la période analysée.'],
            ];
        }

        $trends[] = $consultations->count() . ' consultation(s) analysée(s) sur la période.';

        if ($last) {
            $days = (int) $last->created_at->diffInDays(now());
            $trends[] = 'Dernière visite il y a ' . $days . ' jour(s).';

            if ($days > 180) {
                $alerts[] = 'Suivi inactif depuis plus de 6 mois — planifier une visite de contrôle.';
            }
        }

        if ($pair && isset($pair['first'], $pair['last']) && filled($pair['first']->poids) && filled($pair['last']->poids)) {
            $delta = round((float) $pair['last']->poids - (float) $pair['first']->poids, 1);
            $trends[] = 'Évolution du poids : ' . ($delta > 0 ? '+' : '') . $delta . ' kg (' . $pair['label'] . ').';
        }

        $open = $consultations->where('is_clore', false)->count();
        if ($open > 0) {
            $alerts[] = $open . ' consultation(s) encore ouverte(s).';
        }

        return [
            'summary' => 'Suivi longitudinal du patient basé sur les consultations enregistrées.',
            'trends' => $trends,
            'alerts' => $alerts,
        ];
    }

    /**
     * @param  Collection<int, Consultation>  $consultations
     * @return array{first: ?Consultation, last: ?Consultation, label: string}|null
     */
    private function comparePair(Collection $consultations, string $mode): ?array
    {
        if ($consultations->count() < 2) {
            return $consultations->count() === 1
                ? ['first' => $consultations->first(), 'last' => $consultations->first(), 'label' => 'Visite unique']
                : null;
        }

        return match ($mode) {
            'last_two' => [
                'first' => $consultations->slice(-2, 1)->first(),
                'last' => $consultations->last(),
                'label' => '2 dernières visites',
            ],
            default => [
                'first' => $consultations->first(),
                'last' => $consultations->last(),
                'label' => '1ère vs dernière visite',
            ],
        };
    }

    /**
     * @return array<int, array{id: int|string, label: string}>
     */
    private function filterOptions(int $patientId, string $type): array
    {
        $query = Consultation::query()->where('dossier_patient_id', $patientId);

        if ($type === 'departement') {
            $ids = $query->whereNotNull('departement_id')->distinct()->pluck('departement_id');

            return Departement::query()
                ->whereIn('id', $ids)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($d) => ['id' => $d->id, 'label' => $d->name])
                ->all();
        }

        $userIds = $query->whereNotNull('user_id')->distinct()->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'label' => $u->name])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function cacheKey(int $patientId, array $filters): string
    {
        return 'patient.evolution.v1.' . $patientId . '.' . md5(json_encode($filters));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializable(mixed $value): array
    {
        if ($value instanceof Collection) {
            $value = $value->map(fn ($item) => $this->serializableValue($item))->values()->all();
        } elseif (is_array($value)) {
            $value = array_map(fn ($item) => $this->serializableValue($item), $value);
        } else {
            $value = $this->serializableValue($value);
        }

        return is_array($value) ? $value : ['value' => $value];
    }

    private function serializableValue(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            return $value->map(fn ($item) => $this->serializableValue($item))->values()->all();
        }

        if ($value instanceof \Illuminate\Database\Eloquent\Model) {
            return $this->serializableValue($value->attributesToArray());
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toIso8601String();
        }

        if ($value instanceof \stdClass) {
            return $this->serializableValue((array) $value);
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->serializableValue($item), $value);
        }

        if (is_object($value)) {
            return $this->serializableValue(get_object_vars($value));
        }

        return $value;
    }
}
