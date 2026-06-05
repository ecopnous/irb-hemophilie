<?php

namespace App\Livewire;

use App\Models\prescription\Medicament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PharmacyStockTable extends PowerGridComponent
{
    public string $tableName = 'pharmacyStockTable';

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
            ->add('stock_total', fn(Medicament $m) => (int) $m->pharmacies->sum(fn($p) => (int) $p->pivot->quantiter))
            ->add('status_badge', function (Medicament $m) {
                $stock = (int) $m->pharmacies->sum(fn($p) => (int) $p->pivot->quantiter);
                $isLow = $stock <= (int) $m->stock_min;
                $class = $isLow
                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
                    : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
                $label = $isLow ? 'Stock bas' : 'Actif';

                return Blade::render('<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">{{ $label }}</span>', [
                    'class' => $class, 'label' => $label,
                ]);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')->bodyAttribute('text-xs'),
            Column::make('Nom du produit', 'name')->sortable()->searchable()->bodyAttribute('text-xs font-semibold'),
            Column::make('Reference', 'reference')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Classe therapeutique', 'classe')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Forme', 'forme')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Qte', 'stock_total')->sortable()->bodyAttribute('text-xs text-right'),
            Column::make('Statut', 'status_badge')->visibleInExport(false)->bodyAttribute('text-xs'),
        ];
    }
}
