<?php

namespace App\Livewire;

use App\Models\Laboratoire;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class LaboTable extends PowerGridComponent
{
    public string $tableName = 'laboTable';

    public string $context = 'reception';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.table.loading';

    public function setUp(): array
    {
        // $this->showCheckBox();

        return [
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
        return Laboratoire::query()
            ->with([
                'consultation.dossierPatient:id,nom,postnom,prenom,genre,date_naissance,nin',
                'consultation.departement:id,name',
                'consultation.user:id,name',
                'userValideur:id,name',
            ])
            ->whereHopitalId(current_hopital_id())
            ->when($this->context === 'rapport', function (Builder $query) {
                $query->where(function (Builder $reportQuery) {
                    $reportQuery
                        ->where('statut', 'terminé')
                        ->orWhereNotNull('date_heure_validation')
                        ->orWhereNotNull('user_valideur_id');
                });
            });
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('reference', fn($labo) => $labo->consultation->reference)
            ->add('patient', function ($labo) {
                return Blade::render('<div class="space-y-1">
                        <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
                            <a href="{{ route(\'laboratoire.show\', $consultation->laboratoire_id) }}" class="hover:text-blue-600" wire:navigate>{{ $consultation->dossierPatient?->full_name }}</a>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ $consultation->dossierPatient?->nin }}
                        </p>
                    </div>',
                    ['consultation' => $labo->consultation]
                );
            })
            ->add('age', fn($labo) => $labo->consultation->dossierPatient->age)
            ->add('genre', fn($labo) => $labo->consultation->dossierPatient->genre)
            ->add('provenance', fn($labo) => ucfirst($labo->consultation->departement->name))
            ->add('medecin', fn($labo) => $labo->consultation->user->name ?? '-')
            ->add('date', function ($labo) {
                return Blade::render('
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">
                        {{ optional($labo->created_at)->format(\'d/m/Y\') }}
                    </p>
                    <p class="text-slate-500 dark:text-slate-400">
                        {{ optional($labo->created_at)->format(\'H:i:s\') }}
                    </p>
                </div>
            ', ['labo' => $labo]);
            })
            ->add('prelevement', function ($labo) {
                $prelevement = $labo->date_heure_prelevemnt;
                if ($prelevement) {
                    return Blade::render('
                    <div class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <p class="font-medium text-slate-900 dark:text-white">
                            {{ \Illuminate\Support\Carbon::parse($prelevement)->format(\'d/m/Y\') }}
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ \Illuminate\Support\Carbon::parse($prelevement)->format(\'H:i:s\') }}
                        </p>
                    </div>', ['prelevement' => $prelevement]);
                } else {
                    return Blade::render('
                     <span
                        class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                        En attente
                    </span>', ['prelevement' => $prelevement]);
                }
            })
            ->add('statut', function ($labo) {
                $statut = $labo->statut;
                $colors = [
                    'en attente' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                    'en cours' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
                    'terminé' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300',
                    'bloqué' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
                ];
                $colorClass = $colors[$statut] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300';

                return Blade::render('<span
                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $colorClass }}">
                    {{ ucfirst($statut) }}
                </span>', ['statut' => $statut, 'colorClass' => $colorClass]);
            })
            ->add('validation_date', function ($labo) {
                $validation = $labo->date_heure_validation;

                if ($validation) {
                    return Blade::render('
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">
                                {{ \Illuminate\Support\Carbon::parse($validation)->format(\'d/m/Y\') }}
                            </p>
                            <p class="text-slate-500 dark:text-slate-400">
                                {{ \Illuminate\Support\Carbon::parse($validation)->format(\'H:i\') }}
                            </p>
                        </div>
                    ', ['validation' => $validation]);
                }

                return Blade::render('
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        Non datée
                    </span>
                ');
            })
            ->add('validateur', fn($labo) => $labo->userValideur?->name ?? '-');
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs'),

            Column::make('Patient', 'patient')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Age', 'age')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Genre', 'genre')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Provenance', 'provenance')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Medecin', 'medecin')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Date', 'date')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Prelevement', 'prelevement')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs'),

            Column::make('Statut', 'statut')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs'),

            Column::make('Validation', 'validation_date')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(fn(Builder $query, string $direction) => $query->orderBy('date_heure_validation', $direction))
                ->hidden($this->context !== 'rapport'),

            Column::make('Valideur', 'validateur')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->searchable()
                ->sortable(fn(Builder $query, string $direction) => $query->leftJoin('users as validateurs', 'laboratoires.user_valideur_id', '=', 'validateurs.id')->orderBy('validateurs.name', $direction)->select('laboratoires.*'))
                ->hidden($this->context !== 'rapport'),
        ];
    }

    // public function filters(): array
    // {
    //     return [
    //         Filter::datetimepicker('date_heure_prelevemnt'),
    //         Filter::datetimepicker('created_at'),
    //     ];
    // }

    // #[\Livewire\Attributes\On('edit')]
    // public function edit($rowId): void
    // {
    //     $this->js('alert('.$rowId.')');
    // }

    // public function actions(Laboratoire $row): array
    // {
    //     return [
    //         Button::add('edit')
    //             ->slot('Edit: '.$row->id)
    //             ->id()
    //             ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
    //             ->dispatch('edit', ['rowId' => $row->id])
    //     ];
    // }

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
