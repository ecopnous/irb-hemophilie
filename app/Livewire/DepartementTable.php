<?php

namespace App\Livewire;

use App\Models\Configs\Departement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class DepartementTable extends PowerGridComponent
{
    public string $tableName = 'departementTable';

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
        return Departement::query()
            ->with('chef:id,name,prenom,post_nom,matricule')
            ->withCount(['services', 'actes', 'users'])
            ->where('is_delete', false)
            ->orderBy('name');
    }

    public function relationSearch(): array
    {
        return [
            'chef' => ['name', 'prenom', 'post_nom', 'matricule'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('name_sort', fn (Departement $departement) => $departement->name)
            ->add('departement', fn (Departement $departement) => Blade::render(
                '<div class="space-y-1">
                    <a href="{{ route(\'settings.departement.show\', $departement->id) }}" wire:navigate
                        class="font-bold tracking-tight text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-300">
                        {{ ucfirst($departement->name) }}
                    </a>
                    <p class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ strtoupper($departement->ref) }}</p>
                </div>',
                ['departement' => $departement]
            ))
            ->add('description_label', fn (Departement $departement) => str($departement->description ?: '—')->limit(60))
            ->add('chef_label', fn (Departement $departement) => $departement->chef
                ? trim(collect([$departement->chef->name, $departement->chef->prenom])->filter()->implode(' '))
                : '—')
            ->add('services_count_label', fn (Departement $departement) => (int) $departement->services_count)
            ->add('actes_count_label', fn (Departement $departement) => (int) $departement->actes_count)
            ->add('users_count_label', fn (Departement $departement) => (int) $departement->users_count)
            ->add('action', fn (Departement $departement) => Blade::render(
                '<a href="{{ route(\'settings.departement.show\', $departement->id) }}" wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                    Gérer
                </a>',
                ['departement' => $departement]
            ));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Département', 'departement', 'name_sort')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Description', 'description_label', 'description')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Chef', 'chef_label')
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Services', 'services_count_label', 'services_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Actes', 'actes_count_label', 'actes_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Personnel', 'users_count_label', 'users_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Action', 'action')
                ->bodyAttribute('text-xs text-right'),
        ];
    }
}
