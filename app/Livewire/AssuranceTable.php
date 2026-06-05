<?php

namespace App\Livewire;

use App\Models\configs\Assurance;
use Blade;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class AssuranceTable extends PowerGridComponent
{
    public string $tableName = 'assuranceTable';

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
        return Assurance::query()->with(['categorisation'])
            ->latest();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('reference')
            ->add('assurance', function ($assurance) {
                return Blade::render('<p class="font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ ucfirst($assurance->name) }}
                        </p>',
                    ['assurance' => $assurance]
                );
            })
            ->add('type')
            ->add('logo')
            ->add('categorisation', function ($assurance) {
                return Blade::render(
                    '<x-progress percent="{{ $assurance->categorisation->pourcentage }}" title="{{ $assurance->categorisation->name }}" />',
                    ['assurance' => $assurance]
                );
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
            Column::make('Reference', 'reference'),
            Column::make('Avatar', 'logo'),
            Column::make('Assurance', 'assurance', 'name')
                ->sortable()
                ->searchable(),
            Column::make('Type', 'type')
                ->sortable()
                ->searchable(),
            Column::make('Categorisation', 'categorisation'),
            Column::make('', 'action')
                ->bodyAttribute('flex justify-end items-center'),
        ];
    }
}
