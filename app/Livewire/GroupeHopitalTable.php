<?php

namespace App\Livewire;

use App\Models\Configs\GroupeHopital;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class GroupeHopitalTable extends PowerGridComponent
{
    public string $tableName = 'groupeHopitalTable';

    public function setUp(): array
    {
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
        return GroupeHopital::query()
            ->with('user')
            ->withCount('hopitaux');
    }

    public function relationSearch(): array
    {
        return [
            'user' => ['name', 'email'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nom', function (GroupeHopital $groupe) {
                return sprintf(
                    '<div>
                        <p class="font-bold text-slate-900 dark:text-white">%s</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">%s</p>
                    </div>',
                    e($groupe->nom),
                    e($groupe->objetif ?: 'Objectif non renseigne'),
                );
            })
            ->add('nom_export', fn(GroupeHopital $groupe) => $groupe->nom)
            ->add('objetif')
            ->add('hopitaux_count')
            ->add('created_by', fn(GroupeHopital $groupe) => $groupe->user?->name ?: $groupe->user?->email ?: 'Non renseigne')
            ->add('created_at_formatted', fn(GroupeHopital $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i'));
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')
                ->bodyAttribute('text-xs'),

            Column::make('Nom', 'nom')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable()
                ->visibleInExport(false),

            Column::make('Nom', 'nom_export')
                ->hidden()
                ->visibleInExport(true),

            Column::make('Objectif', 'objetif')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Hopitaux', 'hopitaux_count')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Cree par', 'created_by')
                ->bodyAttribute('text-xs')
                ->searchable(),

            Column::make('Creation', 'created_at_formatted', 'created_at')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('created_at'),
        ];
    }

    #[\Livewire\Attributes\On('show-group')]
    public function showGroup(int $rowId): void
    {
        $this->redirectRoute('groupe_hopitaux.show', ['id' => $rowId], navigate: true);
    }

    public function actions(GroupeHopital $row): array
    {
        return [
            Button::add('show')
                ->slot('Voir detail')
                ->id()
                ->class('inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:border-blue-500/40')
                ->dispatch('show-group', ['rowId' => $row->id])
        ];
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
