<?php

namespace App\Support;

use App\Models\Configs\Assurance;
use App\Models\Configs\Departement;
use App\Models\Configs\Projet;
use App\Models\other\Tag;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PowerGridFilterCache
{
    private const TTL_SECONDS = 3600;

    private const CACHE_VERSION = 'v2';

    public static function departements(): Collection
    {
        return self::remember('departements', fn () => Departement::query()->orderBy('name')->get(['id', 'name']));
    }

    public static function projets(): Collection
    {
        return self::remember('projets', fn () => Projet::query()->orderBy('name')->get(['id', 'name']));
    }

    public static function assurances(): Collection
    {
        return self::remember('assurances', fn () => Assurance::query()->orderBy('name')->get(['id', 'name']));
    }

    public static function tags(): Collection
    {
        return self::remember('tags', fn () => Tag::query()->orderBy('name')->get(['id', 'name']));
    }

    public static function users(?int $hopitalId): Collection
    {
        $suffix = (string) ($hopitalId ?? 'all');

        return self::remember("users.{$suffix}", fn () => User::query()
            ->when($hopitalId, fn ($query) => $query->where('hopital_id', $hopitalId))
            ->orderBy('name')
            ->get(['id', 'name']));
    }

    /**
     * Cache plain arrays — never store Eloquent models (serialization breaks across requests).
     */
    private static function remember(string $key, callable $resolver): Collection
    {
        $cacheKey = 'pg.filters.' . self::CACHE_VERSION . '.' . $key;

        $items = Cache::remember($cacheKey, self::TTL_SECONDS, fn () => $resolver()
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])
            ->values()
            ->all());

        return collect($items);
    }
}
