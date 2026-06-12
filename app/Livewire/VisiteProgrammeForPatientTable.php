<?php

namespace App\Livewire;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class VisiteProgrammeForPatientTable extends PowerGridComponent
{
    public string $tableName = 'visiteProgrammeForPatientTable';
    public bool $deferLoading = true;
    public string $loadingComponent = 'components.table.loading';

    public $dossierPatientId;

    public function setUp(): array
    {

        return [
            PowerGrid::header()
                ->showToggleColumns()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),

            // PowerGrid::responsive()
            //     ->fixedColumns('dossierPatient', 'date'),
        ];
    }

    public function datasource(): Builder
    {
        return Consultation::with(['dossierPatient', 'departement', 'facturation', 'user'])
            ->where('dossier_patient_id', $this->dossierPatientId)
            ->programmed()
            ->whereDate('created_at', '>=', today())
            ->whereHopitalId(current_hopital_id());
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('type_visite', fn($consultation) => ucfirst($consultation->type_visite ?? '-'))
            ->add('reference', fn($consultation) => $consultation->reference ?? '-')
            ->add('departement', fn($consultation) => ucwords($consultation->departement?->name) ?? '-')
            ->add('mois', fn($consultation) => $consultation->mois ?? '-')
            ->add('user', fn($consultation) => ucfirst($consultation->user?->name ?? '-'))
            ->add('date', fn($consultation) => Blade::render('
            <p class="text-xs font-medium text-blue-600 dark:text-blue-300">
                {{ $dateLabel }}
            </p>
            ', [
                'dateLabel' => $consultation->created_at->isToday()
                    ? "Date du visite aujourd'hui"
                    : 'Passage prevu le ' . $consultation->created_at->format('d/m/Y')
            ]) ?? '-');
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),


            Column::make('Type Fiche', 'type_visite')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Département', 'departement')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Période', 'mois')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Info du visite', 'date')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),
        ];
    }
}
