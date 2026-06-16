<?php

namespace App\Livewire;

use App\Models\Configs\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class DepartementServiceTable extends PowerGridComponent
{
    public string $tableName = 'departementServiceTable';

    public int $departementId;

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.table.loading';

    public int $rowCounter = 0;

    public function setUp(): array
    {
        $this->rowCounter = 0;

        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Service::query()
            ->where('departement_id', $this->departementId)
            ->where('is_delete', false)
            ->withCount('actes')
            ->orderBy('name');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('name_sort', fn (Service $service) => $service->name)
            ->add('service', fn (Service $service) => Blade::render(
                '<div>
                    <p class="font-bold text-slate-900 dark:text-white">{{ ucfirst($service->name) }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">ID #{{ $service->id }}</p>
                </div>',
                ['service' => $service]
            ))
            ->add('description_label', fn (Service $service) => str($service->description ?: '—')->limit(80))
            ->add('actes_count_label', fn (Service $service) => (int) $service->actes_count)
            ->add('created_at_label', fn (Service $service) => optional($service->created_at)->format('d/m/Y'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Service', 'service', 'name_sort')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Description', 'description_label', 'description')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Actes liés', 'actes_count_label', 'actes_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Créé le', 'created_at_label', 'created_at')
                ->sortable()
                ->bodyAttribute('text-xs'),
        ];
    }
}
