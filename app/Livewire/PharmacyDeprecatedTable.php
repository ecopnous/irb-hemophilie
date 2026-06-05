<?php

namespace App\Livewire;

use App\Models\prescription\Medicament;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PharmacyDeprecatedTable extends PowerGridComponent
{
    public string $tableName = 'pharmacyDeprecatedTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showToggleColumns()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Medicament::query()
            ->with('pharmacies')
            ->where(function (Builder $query) {
                $query->where('is_active', false)
                    ->orWhereDate('expiration_date', '<', today());
            })
            ->latest('updated_at');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('reference')
            ->add('classe')
            ->add('forme')
            ->add('stock', fn(Medicament $m) => (int) $m->pharmacies->sum(fn($p) => (int) $p->pivot->quantiter))
            ->add('expiration', fn(Medicament $m) => optional($m->expiration_date)->format('d/m/Y') ?: '-');
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')->bodyAttribute('text-xs'),
            Column::make('Nom du produit', 'name')->sortable()->searchable()->bodyAttribute('text-xs font-semibold'),
            Column::make('Reference', 'reference')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Classe therapeutique', 'classe')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Forme', 'forme')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Stock', 'stock')->sortable()->bodyAttribute('text-xs text-right'),
            Column::make('Expiration', 'expiration', 'expiration_date')->sortable()->bodyAttribute('text-xs'),
        ];
    }
}
