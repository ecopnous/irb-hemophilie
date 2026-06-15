<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasConsultationPowerGridFilters;
use App\Models\Consultation;
use App\Support\PowerGridCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class ConsultationTable extends PowerGridComponent
{
    use HasConsultationPowerGridFilters;
    use WithExport;

    public string $tableName = 'consultationTable';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.table.loading';

    public int $rowCounter = 0;

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        $this->rowCounter = 0;
        $this->showFilters = true;

        return [
            PowerGrid::exportable(fileName: 'consultations')
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
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
            ->where(function (Builder $query) {
                $query->whereNotNull('user_id')
                    ->orWhere('type', 'depistage');
            })
            ->toDay()
            ->whereHopitalId(current_hopital_id())
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
            ->add('numero', fn () => ++$this->rowCounter)
            ->add('type', fn (Consultation $consultation) => $consultation->type)
            ->add('type_visite', fn (Consultation $consultation) => ucfirst($consultation->type_visite ?? '-'))
            ->add('patient_genre', fn (Consultation $consultation) => $consultation->dossierPatient?->genre)
            ->add('patient_age_bracket', fn () => null)
            ->add('projet_id', fn (Consultation $consultation) => $consultation->projet_id)
            ->add('is_clore', fn (Consultation $consultation) => $consultation->is_clore)
            ->add('is_clore_label', fn (Consultation $consultation) => $consultation->is_clore ? 'Classé' : 'Ouvert')
            ->add('departement_id', fn (Consultation $consultation) => $consultation->departement_id)
            ->add('temperature', fn (Consultation $consultation) => $consultation->temperature === null ? '-' : $consultation->temperature . '°C')
            ->add('poids', fn (Consultation $consultation) => $consultation->poids === null ? '-' : $consultation->poids . ' kg')
            ->add('pression_arterielle', fn (Consultation $consultation) => (! $consultation->systolite ? '-' : $consultation->systolite) . ' / ' . (! $consultation->diastolique ? '-' : $consultation->diastolique) . ' mmHg')
            ->add('reference', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-reference',
                compact('consultation')
            ))
            ->add('dossierPatient', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-patient',
                ['consultation' => $consultation, 'routeName' => 'consultation.show']
            ))
            ->add('departement', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-departement',
                compact('consultation')
            ))
            ->add('mois', fn (Consultation $consultation) => $consultation->mois ?? '-')
            ->add('user_id', fn (Consultation $consultation) => $consultation->user_id)
            ->add('user', fn (Consultation $consultation) => ucfirst($consultation->user?->name ?? '-'))
            ->add('date', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.datetime-stacked',
                ['datetime' => $consultation->created_at]
            ))
            ->add('action', fn (Consultation $consultation) => PowerGridCell::render(
                'components.powergrid.cells.consultation-action',
                compact('consultation')
            ))
            ->add('reference_export', fn (Consultation $consultation) => Str::lower($consultation->reference))
            ->add('nom_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->nom))
            ->add('post_nom_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->postnom))
            ->add('prenom_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->prenom))
            ->add('genre_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->genre))
            ->add('age_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->age))
            ->add('type_visite_export', fn (Consultation $consultation) => Str::lower($consultation->type_visite))
            ->add('temperature_export', fn (Consultation $consultation) => Str::lower($consultation->temperature))
            ->add('poids_export', fn (Consultation $consultation) => Str::lower($consultation->poids))
            ->add('departement_export', fn (Consultation $consultation) => Str::lower($consultation->departement?->name))
            ->add('mois_export', fn (Consultation $consultation) => Str::lower($consultation->mois))
            ->add('date_export', fn (Consultation $consultation) => optional($consultation->created_at)->format('d/m/Y'))
            ->add('heure_export', fn (Consultation $consultation) => optional($consultation->created_at)->format('H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'numero')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs font-semibold text-center w-8'),

            Column::make('Reference', 'reference', 'reference')
                ->visibleInExport(false)
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
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Visite', 'type_visite', 'type_visite')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Projet', 'projet_id', 'projet_id')
                ->hidden(),

            Column::make('T°C', 'temperature')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Poids', 'poids')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('PA', 'pression_arterielle')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Département', 'departement', 'departement_id')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Dossier', 'is_clore_label', 'is_clore')
                ->hidden(),

            Column::make('Période', 'mois', 'mois')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Medecin Traitant', 'user', 'user_id')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Date', 'date', 'created_at')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Action', 'action')
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

    public function filters(): array
    {
        return array_slice($this->consultationPowerGridFilters(), 0, -2);
    }
}
