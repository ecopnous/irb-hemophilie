<?php

namespace App\Livewire;

use App\Models\Configs\Acte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class DepartementActeTable extends PowerGridComponent
{
    public string $tableName = 'departementActeTable';

    public int $departementId;

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.table.loading';

    public int $rowCounter = 0;

    public function setUp(): array
    {
        $this->rowCounter = 0;

        return [
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Acte::query()
            ->where('departement_id', $this->departementId)
            ->where('is_delete', false)
            ->with('service:id,name')
            ->orderBy('name');
    }

    public function relationSearch(): array
    {
        return [
            'service' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('name_sort', fn (Acte $acte) => $acte->name)
            ->add('acte', fn (Acte $acte) => Blade::render(
                '<div>
                    <p class="font-bold text-slate-900 dark:text-white">{{ ucfirst($acte->name) }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">ID #{{ $acte->id }}</p>
                </div>',
                ['acte' => $acte]
            ))
            ->add('service_label', fn (Acte $acte) => $acte->service?->name ?: '—')
            ->add('montant_label', fn (Acte $acte) => number_format((float) $acte->montant, 2, ',', ' ') . ' $')
            ->add('unite_label', fn (Acte $acte) => $acte->unite ?: '—')
            ->add('created_at_label', fn (Acte $acte) => optional($acte->created_at)->format('d/m/Y'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Acte médical', 'acte', 'name_sort')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Service', 'service_label')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Tarif', 'montant_label', 'montant')
                ->sortable()
                ->bodyAttribute('text-xs text-right font-semibold'),
            Column::make('Unité', 'unite_label', 'unite')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Créé le', 'created_at_label', 'created_at')
                ->sortable()
                ->bodyAttribute('text-xs'),
        ];
    }
}
