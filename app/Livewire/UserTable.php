<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;

final class UserTable extends PowerGridComponent
{
    use WithExport;
    public string $tableName = 'userTable';

    public function setUp(): array
    {
        // $this->showCheckBox();

        return [
            PowerGrid::exportable(fileName: 'triage-consultations')
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()
                ->showToggleColumns()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return User::query()->with(['departement'])
            ->whereHopitalId(current_hopital_id());
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', function ($projet) {
                return sprintf(
                    "<div class='space-y-1'>
                        <p class='font-bold uppercase tracking-tight text-slate-900 dark:text-white'>
                            %s
                        </p>
                        <p class='text-slate-500 dark:text-slate-400'>
                            %s
                        </p>
                    </div>",
                    Str::upper(e($projet->full_name)),
                    e($projet->phone),
                );
            })
            ->add('nationalite')
            ->add('genre')
            ->add('role')
            ->add('grade')
            ->add('email')
            ->add('matricule', fn($user) => $user->matricule ?? "-")
            ->add('department', fn($user) => ucwords($user->departement->name ?? "-"))
            ->add('dernier_connexion', function ($user) {
                return Blade::render('
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">
                        {{ optional($model->last_seen_at)->format(\'d/m/Y\') }}
                    </p>
                    <p class="text-slate-500 dark:text-slate-400">
                        {{ optional($model->last_seen_at)->format(\'H:i:s\') }}
                    </p>
                </div>
                ', ['model' => $user]);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Nom complet', 'name')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Email', 'email')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Nationalité', 'nationalite')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Genre', 'genre')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Département', 'department')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Grade', 'grade')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Role', 'role')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Matricule', 'matricule')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Dérnière connexion', 'dernier_connexion')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),
        ];
    }
}
