<?php

namespace App\Livewire;

use App\Models\configs\Categorisation;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CategorisationTable extends PowerGridComponent
{
    public string $tableName = 'categorisationTable';

    public function setUp(): array
    {
        // $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Categorisation::query()->with(['assurances']);
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('prise_en_charge', function ($categorisation) {
                return Blade::render('
                <x-progress percent="{{ $cat->pourcentage }}" title="Pourcentage" />
                ', ['cat' => $categorisation]);
            })
            ->add('description')
            ->add('created_at_formatted', function ($consultation) {
                return Blade::render('
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">
                        le {{ optional($consultation->created_at)->format(\'d/m/Y\') }}
                    </p>
                    <p class="text-slate-500 dark:text-slate-400">
                        {{ optional($consultation->created_at)->format(\'à H:i:s\') }}
                    </p>
                </div>
            ', ['consultation' => $consultation]);
            })
            ->add('action', function ($categorisation) {
                return Blade::render('
                        <a href="#" wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-500/40">
                            Voir détail
                        </a>
                ', ['cat' => $categorisation]);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id'),
            Column::make('Categorie', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Prise en charge', 'prise_en_charge', 'pourcentage')
                ->sortable()
                ->searchable(),

            Column::make('Assurance associé', 'assurances_count'),

            Column::make('Créer dépuis', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::make('', 'action')
                ->bodyAttribute('flex justify-end items-center'),
        ];
    }
}
