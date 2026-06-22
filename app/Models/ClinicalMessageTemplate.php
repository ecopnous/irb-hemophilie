<?php

namespace App\Models;

use App\Enums\ClinicalMessageCategory;
use App\Models\Concerns\ScopesByHopital;
use App\Models\Configs\Departement;
use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalMessageTemplate extends Model
{
    use ScopesByHopital;

    protected $fillable = [
        'hopital_id',
        'departement_id',
        'category',
        'name',
        'subject',
        'body',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ClinicalMessageCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForContext(Builder $query, ?int $hopitalId, ?int $departementId = null): Builder
    {
        return $query
            ->where(function (Builder $hopitalQuery) use ($hopitalId): void {
                $hopitalQuery->whereNull('hopital_id');

                if ($hopitalId !== null) {
                    $hopitalQuery->orWhere('hopital_id', $hopitalId);
                }
            })
            ->when($departementId, function (Builder $deptQuery) use ($departementId): void {
                $deptQuery->where(function (Builder $inner) use ($departementId): void {
                    $inner->whereNull('departement_id')
                        ->orWhere('departement_id', $departementId);
                });
            }, function (Builder $deptQuery): void {
                $deptQuery->whereNull('departement_id');
            });
    }
}
