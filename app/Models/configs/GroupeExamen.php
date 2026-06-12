<?php

namespace App\Models\Configs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\Rule;

class GroupeExamen extends Model
{
    protected $table = 'groupe_examens';

    protected $fillable = [
        'name',
        'description',
        'service_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function actes(): BelongsToMany
    {
        return $this->belongsToMany(Acte::class, 'acte_groupe_examen')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function labActesQuery(): Builder
    {
        return Acte::query()
            ->whereHas('departement', fn (Builder $query) => $query->where('ref', 'labo'));
    }

    /**
     * @return array<int, int>
     */
    public static function labActeIds(): array
    {
        return static::labActesQuery()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function acteSelectionRules(): array
    {
        return [
            'selectedActes' => ['required', 'array', 'min:1'],
            'selectedActes.*' => ['integer', Rule::in(static::labActeIds())],
        ];
    }

    /**
     * @param  array<int, mixed>  $acteIds
     * @return array<int, int>
     */
    public static function normalizeActeIds(array $acteIds): array
    {
        return collect($acteIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
