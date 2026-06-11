<?php

namespace App\Livewire;

use App\Models\Configs\Categorisation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class CategorisationTable extends PowerGridComponent
{
    public string $tableName = 'categorisationTable';
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
        return Categorisation::query()
            ->withCount(['assurances', 'paquets'])
            ->latest('created_at');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('name_export', fn (Categorisation $categorisation) => $categorisation->name)
            ->add('categorie', function (Categorisation $categorisation) {
                return Blade::render(
                    '<div class="space-y-1">
                        <a href="{{ route(\'settings.categorisation.show\', $categorisation->id) }}" wire:navigate
                            class="font-bold tracking-tight text-slate-900 hover:text-violet-600 dark:text-white dark:hover:text-violet-300">
                            {{ $categorisation->name }}
                        </a>
                        <p class="text-xs text-slate-500 dark:text-slate-400">ID #{{ $categorisation->id }}</p>
                    </div>',
                    ['categorisation' => $categorisation]
                );
            })
            ->add('prise_en_charge', function (Categorisation $categorisation) {
                return Blade::render(
                    '<x-progress percent="{{ $cat->pourcentage }}" title="Prise en charge" />',
                    ['cat' => $categorisation]
                );
            })
            ->add('description_label', fn (Categorisation $categorisation) => str($categorisation->description ?: '—')->limit(80))
            ->add('assurances_count_label', fn (Categorisation $categorisation) => (int) $categorisation->assurances_count)
            ->add('paquets_count_label', fn (Categorisation $categorisation) => (int) $categorisation->paquets_count)
            ->add('created_at_label', fn (Categorisation $categorisation) => optional($categorisation->created_at)->format('d/m/Y H:i'))
            ->add('action', function (Categorisation $categorisation) {
                return Blade::render(
                    '<a href="{{ route(\'settings.categorisation.show\', $categorisation->id) }}" wire:navigate
                        class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 transition hover:border-violet-300 hover:bg-violet-100 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-300">
                        Voir detail
                    </a>',
                    ['categorisation' => $categorisation]
                );
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Categorie', 'categorie', 'name_export')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Prise en charge', 'prise_en_charge', 'pourcentage')
                ->sortable()
                ->bodyAttribute('text-xs'),
            Column::make('Description', 'description_label', 'description')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Assurances', 'assurances_count_label', 'assurances_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Paquets', 'paquets_count_label', 'paquets_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Cree le', 'created_at_label', 'created_at')
                ->sortable()
                ->bodyAttribute('text-xs'),
            Column::make('Action', 'action')
                ->bodyAttribute('text-xs text-right'),
        ];
    }
}
