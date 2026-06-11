<?php

namespace App\Livewire;

use App\Models\Configs\Projet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ProjetTable extends PowerGridComponent
{
    public string $tableName = 'projetTable';
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
        return Projet::query()
            ->with(['assurance'])
            ->withCount('consultations')
            ->latest('created_at');
    }

    public function relationSearch(): array
    {
        return [
            'assurance' => ['name', 'reference'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('name_export', fn (Projet $projet) => $projet->name)
            ->add('projet', function (Projet $projet) {
                return Blade::render(
                    '<div class="space-y-1">
                        <a href="{{ route(\'settings.projet.show\', $projet->id) }}" wire:navigate
                            class="font-bold tracking-tight text-slate-900 hover:text-sky-600 dark:text-white dark:hover:text-sky-300">
                            {{ $projet->name }}
                        </a>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $projet->reference ?: \'—\' }}</p>
                    </div>',
                    ['projet' => $projet]
                );
            })
            ->add('assurance_label', function (Projet $projet) {
                if (! $projet->assurance) {
                    return Blade::render('<span class="text-xs text-amber-600 dark:text-amber-300">Non renseignee</span>');
                }

                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $assurance->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $assurance->reference ?: \'—\' }}</p>
                    </div>',
                    ['assurance' => $projet->assurance]
                );
            })
            ->add('description_label', fn (Projet $projet) => str($projet->description ?: '—')->limit(80))
            ->add('consultations_count_label', fn (Projet $projet) => (int) $projet->consultations_count)
            ->add('created_at_label', fn (Projet $projet) => optional($projet->created_at)->format('d/m/Y H:i'))
            ->add('action', function (Projet $projet) {
                return Blade::render(
                    '<a href="{{ route(\'settings.projet.show\', $projet->id) }}" wire:navigate
                        class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300">
                        Voir detail
                    </a>',
                    ['projet' => $projet]
                );
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Projet', 'projet', 'name_export')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Assurance', 'assurance_label')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Description', 'description_label', 'description')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Consultations', 'consultations_count_label', 'consultations_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Cree le', 'created_at_label', 'created_at')
                ->sortable()
                ->bodyAttribute('text-xs'),
            Column::make('Action', 'action')
                ->bodyAttribute('text-xs text-right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('created_at'),
        ];
    }
}
