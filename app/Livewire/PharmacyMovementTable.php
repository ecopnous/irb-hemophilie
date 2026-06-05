<?php

namespace App\Livewire;

use App\Models\prescription\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PharmacyMovementTable extends PowerGridComponent
{
    public string $tableName = 'pharmacyMovementTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showToggleColumns()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return StockMovement::query()
            ->with(['pharmacie', 'medicament', 'consultation'])
            ->latest('created_at');
    }

    public function relationSearch(): array
    {
        return [
            'pharmacie' => ['nom'],
            'medicament' => ['name', 'reference'],
            'consultation' => ['reference'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('date', fn(StockMovement $m) => optional($m->created_at)->format('d/m/Y H:i'))
            ->add('reference', fn(StockMovement $m) => $m->reference ?: '-')
            ->add('type', fn(StockMovement $m) => strtoupper($m->movement_type))
            ->add('pharmacie', fn(StockMovement $m) => $m->pharmacie?->nom ?: '-')
            ->add('medicament', fn(StockMovement $m) => $m->medicament?->name ?: '-')
            ->add('quantity')
            ->add('avant', fn(StockMovement $m) => $m->quantity_before)
            ->add('apres', fn(StockMovement $m) => $m->quantity_after)
            ->add('consultation_ref', fn(StockMovement $m) => $m->consultation?->reference ?: '-');
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')->bodyAttribute('text-xs'),
            Column::make('Date', 'date', 'created_at')->sortable()->bodyAttribute('text-xs'),
            Column::make('Reference', 'reference')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Type', 'type')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Pharmacie', 'pharmacie')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Medicament', 'medicament')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Qte', 'quantity')->sortable()->bodyAttribute('text-xs text-right'),
            Column::make('Avant', 'avant')->sortable()->bodyAttribute('text-xs text-right'),
            Column::make('Apres', 'apres')->sortable()->bodyAttribute('text-xs text-right'),
            Column::make('Consultation', 'consultation_ref')->sortable()->searchable()->bodyAttribute('text-xs'),
        ];
    }
}
