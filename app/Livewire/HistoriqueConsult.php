<?php

namespace App\Livewire;

use App\Models\Consultation;
use App\Support\PowerGridCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class HistoriqueConsult extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'historiqueConsultTable';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.table.loading';

    public $dossierPatientId;

    public function setUp(): array
    {
        return [
            PowerGrid::exportable(fileName: 'consultations')
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
        return Consultation::with(['dossierPatient', 'departement', 'facturation', 'user'])
            ->where('dossier_patient_id', $this->dossierPatientId)
            ->old()
            ->thisHopital()
            ->latest();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('type_visite', fn ($consultation) => ucfirst($consultation->type_visite ?? '-'))
            ->add('temperature', fn ($consultation) => $consultation->temperature === null ? '-' : $consultation->temperature . '°C')
            ->add('poids', fn ($consultation) => $consultation->poids === null ? '-' : $consultation->poids . ' kg')
            ->add('reference', fn ($consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-reference',
                ['consultation' => $consultation, 'showDepistage' => false]
            ))
            ->add('dossierPatient', fn ($consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-patient',
                ['consultation' => $consultation, 'routeName' => 'consultation.show']
            ))
            ->add('departement', fn ($consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-departement',
                compact('consultation')
            ))
            ->add('mois', fn ($consultation) => $consultation->mois ?? '-')
            ->add('user', fn ($consultation) => ucfirst($consultation->user?->name ?? '-'))
            ->add('date', fn ($consultation) => PowerGridCell::render(
                'components.powergrid.cells.datetime-stacked',
                ['datetime' => $consultation->created_at]
            ))
            ->add('reference_export', fn ($consultation) => Str::lower($consultation->reference))
            ->add('nom_export', fn ($consultation) => Str::lower($consultation->dossierPatient?->nom))
            ->add('post_nom_export', fn ($consultation) => Str::lower($consultation->dossierPatient?->postnom))
            ->add('prenom_export', fn ($consultation) => Str::lower($consultation->dossierPatient?->prenom))
            ->add('genre_export', fn ($consultation) => Str::lower($consultation->dossierPatient?->genre))
            ->add('age_export', fn ($consultation) => Str::lower($consultation->dossierPatient?->age))
            ->add('type_visite_export', fn ($consultation) => Str::lower($consultation->type_visite))
            ->add('temperature_export', fn ($consultation) => Str::lower($consultation->temperature))
            ->add('poids_export', fn ($consultation) => Str::lower($consultation->poids))
            ->add('departement_export', fn ($consultation) => Str::lower($consultation->departement?->name))
            ->add('mois_export', fn ($consultation) => Str::lower($consultation->mois))
            ->add('date_export', fn (Consultation $model) => optional($model->created_at)->format('d/m/Y'))
            ->add('heure_export', fn (Consultation $model) => optional($model->created_at)->format('H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Patient', 'dossierPatient')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Type Fiche', 'type_visite')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Température', 'temperature')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Poids', 'poids')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Département', 'departement')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Période', 'mois')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Medecin Traitant', 'user')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Date', 'date')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Facture', 'facture_action')
                ->visibleInExport(false),

            Column::make('Reference', 'reference_export')->visibleInExport(true)->hidden(),
            Column::make('Nom', 'nom_export')->visibleInExport(true)->hidden(),
            Column::make('Post-nom', 'post_nom_export')->visibleInExport(true)->hidden(),
            Column::make('Prénom', 'prenom_export')->visibleInExport(true)->hidden(),
            Column::make('Genre', 'genre_export')->visibleInExport(true)->hidden(),
            Column::make('Age', 'age_export')->visibleInExport(true)->hidden(),
            Column::make('Type Fiche', 'type_visite_export')->visibleInExport(true)->hidden(),
            Column::make('Temperature', 'temperature_export')->visibleInExport(true)->hidden(),
            Column::make('Poids', 'poids_export')->visibleInExport(true)->hidden(),
            Column::make('Département', 'departement_export')->visibleInExport(true)->hidden(),
            Column::make('Période', 'mois_export')->visibleInExport(true)->hidden(),
            Column::make('Date', 'date_export')->visibleInExport(true)->hidden(),
            Column::make('Heure', 'heure_export')->visibleInExport(true)->hidden(),
        ];
    }
}
