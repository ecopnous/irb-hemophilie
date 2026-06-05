<?php

namespace App\Livewire;

use App\Models\prescription\Medicament;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PharmacyMedicineTable extends PowerGridComponent
{
    public string $tableName = 'pharmacyMedicineTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showToggleColumns()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Medicament::query()->latest('created_at');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('reference')
            ->add('name')
            ->add('classe')
            ->add('fournisseur', fn(Medicament $m) => $m->fournisseur ?: '-')
            ->add('fabricant', fn(Medicament $m) => $m->fabricant ?: '-')
            ->add('forme')
            ->add('dosage')
            ->add('created_label', fn(Medicament $m) => optional($m->created_at)->format('d/m/Y H:i'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')->bodyAttribute('text-xs'),
            Column::make('Reference', 'reference')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Nom du produit', 'name')->sortable()->searchable()->bodyAttribute('text-xs font-semibold'),
            Column::make('Classe', 'classe')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Fournisseur', 'fournisseur')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Fabricant', 'fabricant')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Forme', 'forme')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Dosage', 'dosage')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Date', 'created_label', 'created_at')->sortable()->bodyAttribute('text-xs'),
        ];
    }
}
