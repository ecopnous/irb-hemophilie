<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class AgeBrackets
{
    /**
     * @return list<array{id: string, name: string, min: ?int, max: ?int}>
     */
    public static function definitions(): array
    {
        return collect(config('reception.age_brackets', []))
            ->map(function (array $bracket) {
                return [
                    'id' => (string) $bracket['id'],
                    'name' => (string) $bracket['name'],
                    'min' => array_key_exists('min', $bracket) && $bracket['min'] !== null
                        ? (int) $bracket['min']
                        : null,
                    'max' => array_key_exists('max', $bracket) && $bracket['max'] !== null
                        ? (int) $bracket['max']
                        : null,
                ];
            })
            ->filter(fn (array $bracket) => filled($bracket['id']) && filled($bracket['name']))
            ->values()
            ->all();
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

        if ($bracket['min'] !== null && $bracket['min'] > 0) {
            $query->where('date_naissance', '<=', $now->copy()->subYears($bracket['min']));
        }

        if ($bracket['max'] !== null) {
            $query->where('date_naissance', '>', $now->copy()->subYears($bracket['max']));
        }

        return $query;
    }
}
