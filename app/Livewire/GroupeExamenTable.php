<?php

namespace App\Livewire;

use App\Models\Configs\GroupeExamen;
use App\Support\PowerGridCell;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class GroupeExamenTable extends PowerGridComponent
{
    public string $tableName = 'groupeExamenTable';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.table.loading';

    public int $rowCounter = 0;

    public function setUp(): array
    {
        $this->rowCounter = 0;

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return GroupeExamen::query()
            ->with('service:id,name')
            ->withCount('actes')
            ->withSum('actes', 'montant')
            ->latest('created_at');
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
            ->add('name_sort', fn (GroupeExamen $groupe) => $groupe->name)
            ->add('groupe', fn (GroupeExamen $groupe) => PowerGridCell::render(
                'components.powergrid.cells.groupe-examen-name',
                compact('groupe')
            ))
            ->add('service_label', fn (GroupeExamen $groupe) => $groupe->service?->name ?: 'Tous services')
            ->add('actes_count_label', fn (GroupeExamen $groupe) => (int) $groupe->actes_count)
            ->add('montant_label', fn (GroupeExamen $groupe) => number_format(
                (float) ($groupe->actes_sum_montant ?? 0),
                2,
                ',',
                ' '
            ) . ' $')
            ->add('status', fn (GroupeExamen $groupe) => PowerGridCell::render(
                'components.powergrid.cells.groupe-examen-status',
                compact('groupe')
            ))
            ->add('created_at_label', fn (GroupeExamen $groupe) => optional($groupe->created_at)->format('d/m/Y H:i'))
            ->add('action', fn (GroupeExamen $groupe) => PowerGridCell::render(
                'components.powergrid.cells.groupe-examen-action',
                compact('groupe')
            ));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')
                ->bodyAttribute('text-xs font-semibold text-center w-10'),

            Column::make('Groupe', 'groupe', 'name_sort')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),

            Column::make('Service', 'service_label', 'service_id')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),

            Column::make('Examens', 'actes_count_label', 'actes_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),

            Column::make('Montant total', 'montant_label')
                ->bodyAttribute('text-xs text-right font-semibold'),

            Column::make('Statut', 'status', 'is_active')
                ->sortable()
                ->bodyAttribute('text-xs'),

            Column::make('Créé le', 'created_at_label', 'created_at')
                ->sortable()
                ->bodyAttribute('text-xs'),

            Column::make('Action', 'action')
                ->bodyAttribute('text-xs text-right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('is_active', 'is_active')
                ->dataSource(collect([
                    ['id' => '1', 'name' => 'Actif'],
                    ['id' => '0', 'name' => 'Inactif'],
                ]))
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => $query->where(
                    'is_active',
                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                )),
        ];
    }
}
