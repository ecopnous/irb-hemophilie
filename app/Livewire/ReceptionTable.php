<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasConsultationPowerGridFilters;
use App\Livewire\Concerns\HasPowerGridDateFilters;
use App\Models\Consultation;
use App\Support\PowerGridCell;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class ReceptionTable extends PowerGridComponent
{
    use HasConsultationPowerGridFilters;
    use HasPowerGridDateFilters;

    public string $tableName = 'receptionTable';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.table.loading';

    public int $rowCounter = 0;

    public function setUp(): array
    {
        $this->rowCounter = 0;
        $this->showFilters = true;

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('components.powergrid.powergrid-total'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount('full'),
        ];
    }

    public function datasource(): Builder
    {
        return Consultation::query()
            ->with([
                'dossierPatient:id,nom,postnom,prenom,genre,date_naissance',
                'departement:id,name',
                'user:id,name',
            ])
            ->old()
            ->whereHopitalId(current_hopital_id())
            ->tap(fn (Builder $query) => $this->applyCreatedAtDateFilters($query))
            ->latest('created_at');
    }

    public function relationSearch(): array
    {
        return [
            'dossierPatient' => ['nom', 'postnom', 'prenom'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('reference', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-reference',
                compact('consultation')
            ))
            ->add('dossierPatient', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-patient',
                ['consultation' => $consultation, 'routeName' => 'patient.show']
            ))
            ->add('type', fn (Consultation $consultation) => $consultation->type)
            ->add('type_visite', fn (Consultation $consultation) => ucfirst($consultation->type_visite ?? '-'))
            ->add('patient_genre', fn (Consultation $consultation) => $consultation->dossierPatient?->genre)
            ->add('patient_age_bracket', fn () => null)
            ->add('temperature', fn (Consultation $consultation) => $consultation->temperature === null ? '-' : $consultation->temperature . '°C')
            ->add('pression_arterielle', fn (Consultation $consultation) => (! $consultation->systolite ? '-' : $consultation->systolite) . ' / ' . (! $consultation->diastolique ? '-' : $consultation->diastolique) . ' mmHg')
            ->add('poids', fn (Consultation $consultation) => $consultation->poids === null ? '-' : $consultation->poids . ' kg')
            ->add('departement_id', fn (Consultation $consultation) => $consultation->departement_id)
            ->add('projet_id', fn (Consultation $consultation) => $consultation->projet_id)
            ->add('is_clore', fn (Consultation $consultation) => $consultation->is_clore)
            ->add('is_clore_label', fn (Consultation $consultation) => $consultation->is_clore ? 'Classé' : 'Ouvert')
            ->add('departement', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-departement',
                compact('consultation')
            ))
            ->add('mois', fn (Consultation $consultation) => $consultation->mois ?? '-')
            ->add('user_id', fn (Consultation $consultation) => $consultation->user_id)
            ->add('user', fn (Consultation $consultation) => ucfirst($consultation->user?->name ?? '-'))
            ->add('created_at')
            ->add('date', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.datetime-stacked',
                ['datetime' => $consultation->created_at]
            ))
            ->add('action', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.reception-action',
                compact('consultation')
            ));
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference', 'reference')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Type', 'type', 'type')
                ->hidden(),

            Column::make('Sexe', 'patient_genre', 'patient_genre')
                ->hidden(),

            Column::make('Tranche d\'âge', 'patient_age_bracket', 'patient_age_bracket')
                ->hidden(),

            Column::make('Patient', 'dossierPatient')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Visite', 'type_visite', 'type_visite')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Projet', 'projet_id', 'projet_id')
                ->hidden(),

            Column::make('T°C', 'temperature')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('PA', 'pression_arterielle')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Poids', 'poids')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Département', 'departement', 'departement_id')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Dossier', 'is_clore_label', 'is_clore')
                ->hidden(),

            Column::make('Période', 'mois', 'mois')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Medecin Traitant', 'user', 'user_id')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Date début', 'date_start', 'created_at')
                ->hidden(),

            Column::make('Date fin', 'date_end', 'created_at')
                ->hidden(),

            Column::make('Date', 'date', 'created_at')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Action', 'action'),
        ];
    }
}
