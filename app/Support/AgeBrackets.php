<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class AgeBrackets
{
    /**
     * @return list<int>
     */
    public static function thresholds(): array
    {
        $thresholds = config('reception.age_bracket_thresholds', []);

        return collect($thresholds)
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public static function underLimits(): array
    {
        $limits = config('reception.age_under_limits', []);

        return collect($limits)
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, min: ?int, max: ?int}>
     */
    public static function definitions(): array
    {
        $thresholds = static::thresholds();

        if ($thresholds === []) {
            return [];
        }

        $definitions = [];
        $previous = 0;

        foreach ($thresholds as $threshold) {
            if ($threshold <= $previous) {
                continue;
            }

            $definitions[] = [
                'id' => $previous === 0 ? "under_{$threshold}" : "{$previous}_{$threshold}",
                'name' => static::label($previous, $threshold, false),
                'min' => $previous === 0 ? null : $previous,
                'max' => $threshold,
            ];

            $previous = $threshold;
        }

        foreach (static::underLimits() as $limit) {
            $alreadyDefined = collect($definitions)->contains(
                fn (array $definition) => $definition['min'] === null && $definition['max'] === $limit
            );

            if ($alreadyDefined) {
                continue;
            }

            $definitions[] = [
                'id' => "under_limit_{$limit}",
                'name' => "- de {$limit}",
                'min' => null,
                'max' => $limit,
            ];
        }

        $definitions[] = [
            'id' => "over_{$previous}",
            'name' => static::label($previous, null, true),
            'min' => $previous,
            'max' => null,
        ];

        return $definitions;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (array $bracket) => [
                'id' => $bracket['id'],
                'name' => $bracket['name'],
            ],
            static::definitions()
        );
    }

    public static function apply(Builder $query, string $bracketId): Builder
    {
        $bracket = collect(static::definitions())->firstWhere('id', $bracketId);

        if ($bracket === null) {
            return $query;
        }

        $now = now();

        $query->whereNotNull('date_naissance');

        if ($bracket['min'] !== null) {
            $query->where('date_naissance', '<=', $now->copy()->subYears($bracket['min']));
        }

        if ($bracket['max'] !== null) {
            $query->where('date_naissance', '>', $now->copy()->subYears($bracket['max']));
        }

        return $query;
    }

    private static function label(int $min, ?int $max, bool $isOver): string
    {
        if ($isOver) {
            return "+{$min} ans";
        }

        if ($min === 0) {
            return "-{$max} ans";
        }

        return "{$min} - {$max} ans";
    }
}
