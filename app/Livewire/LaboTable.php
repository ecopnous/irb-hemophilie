<?php

namespace App\Livewire;

use App\Models\Laboratoire;
use App\Support\PowerGridCell;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
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
            ->add('reference', fn ($labo) => $labo->consultation->reference)
            ->add('patient', fn ($labo) => PowerGridCell::render(
                'components.powergrid.cells.labo-patient',
                ['consultation' => $labo->consultation]
            ))
            ->add('age', fn ($labo) => $labo->consultation->dossierPatient->age)
            ->add('genre', fn ($labo) => $labo->consultation->dossierPatient->genre)
            ->add('provenance', fn ($labo) => ucfirst($labo->consultation->departement->name))
            ->add('medecin', fn ($labo) => $labo->consultation->user->name ?? '-')
            ->add('date', fn ($labo) => PowerGridCell::render(
                'components.powergrid.cells.datetime-stacked',
                ['datetime' => $labo->created_at]
            ))
            ->add('prelevement', fn ($labo) => PowerGridCell::render(
                'components.powergrid.cells.labo-prelevement',
                ['prelevement' => $labo->date_heure_prelevemnt]
            ))
            ->add('statut', fn ($labo) => PowerGridCell::render(
                'components.powergrid.cells.labo-statut',
                ['statut' => $labo->statut]
            ))
            ->add('validation_date', fn ($labo) => PowerGridCell::render(
                'components.powergrid.cells.labo-validation',
                ['validation' => $labo->date_heure_validation]
            ))
            ->add('validateur', fn ($labo) => $labo->userValideur?->name ?? '-');
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
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('date_heure_validation', $direction))
                ->hidden($this->context !== 'rapport'),

            Column::make('Valideur', 'validateur')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->searchable()
                ->sortable(fn (Builder $query, string $direction) => $query->leftJoin('users as validateurs', 'laboratoires.user_valideur_id', '=', 'validateurs.id')->orderBy('validateurs.name', $direction)->select('laboratoires.*'))
                ->hidden($this->context !== 'rapport'),
        ];
    }
}
