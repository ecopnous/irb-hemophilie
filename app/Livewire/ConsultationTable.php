<?php

namespace App\Livewire;

use App\Models\Consultation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;

final class ConsultationTable extends PowerGridComponent
{
    use WithExport;
    public string $tableName = 'consultationTable';
    public bool $deferLoading = true;
    public string $loadingComponent = 'components.table.loading';
    public int $rowCounter = 0;

    public function setUp(): array
    {
        $this->rowCounter = 0;

        return [
            PowerGrid::exportable(fileName: 'consultations')
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
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
        return Consultation::query()
            ->with(['dossierPatient', 'departement', 'facturation', 'user'])
            ->where(function ($query) {
                $query->orWhereNotNull('user_id')
                    ->orWhere('type', 'depistage');
            })
            ->toDay()
            ->thisHopital();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('numero', fn() => ++$this->rowCounter)
            ->add('type_fichier', fn($consultation) => ucfirst($consultation->type_fichier ?? '-'))
            ->add('temperature', fn($consultation) => $consultation->temperature === null ? '-' : $consultation->temperature . '°C')
            ->add('poids', fn($consultation) => $consultation->poids === null ? '-' : $consultation->poids . ' kg')
            ->add('reference', function ($consultation) {
                return Blade::render('<div class="space-y-1">
                        @if($consultation->is_visite_program)
                            <p class="font-bold tracking-tight text-blue-600 dark:text-blue-300">
                                Rendez-Vous
                            </p>
                        @else
                            <p class="font-bold tracking-tight text-slate-900 dark:text-white">
                                Visite Médicale
                            </p>
                        @endif
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ $consultation->reference }}
                        </p>
                    </div>',
                    ['consultation' => $consultation]
                );
            })
            ->add('dossierPatient', function ($consultation) {
                return Blade::render('<div class="space-y-1">
                        <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
                            <a href="{{ route(\'consultation.show\', $consultation->id) }}" class="hover:text-blue-600" wire:navigate>{{ $consultation->dossierPatient?->full_name }}</a>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ $consultation->dossierPatient?->genre }} ({{ $consultation->dossierPatient?->age }})
                        </p>
                    </div>',
                    ['consultation' => $consultation]
                );
            })
            ->add('departement', function ($consultation) {
                return ucwords($consultation->departement?->name) ?? '-';
            })
            ->add('mois', fn($consultation) => $consultation->mois ?? '-')
            ->add('user', fn($consultation) => ucfirst($consultation->user?->name ?? '-'))
            ->add('date', function ($consultation) {
                return Blade::render('
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">
                        {{ optional($consultation->created_at)->format(\'d/m/Y\') }}
                    </p>
                    <p class="text-slate-500 dark:text-slate-400">
                        {{ optional($consultation->created_at)->format(\'H:i:s\') }}
                    </p>
                </div>
            ', ['consultation' => $consultation]);
            })
            ->add('facture_action', function (Consultation $consultation) {
                if ($consultation->facturation_id) {
                    return Blade::render('
                        <a href="{{ route(\'facturation.show\', $consultation->facturation_id) }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-500/40">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Voir facture
                        </a>
                    ', ['consultation' => $consultation]);
                }

                return Blade::render('
                    <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                        <span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                        Sans facture
                    </span>
                ');
            })

            // For export
            ->add('reference_export', fn($consultation) => Str::lower($consultation->reference))
            ->add('nom_export', fn($consultation) => Str::lower($consultation->dossierPatient?->nom))
            ->add('post_nom_export', fn($consultation) => Str::lower($consultation->dossierPatient?->postnom))
            ->add('prenom_export', fn($consultation) => Str::lower($consultation->dossierPatient?->prenom))
            ->add('genre_export', fn($consultation) => Str::lower($consultation->dossierPatient?->genre))
            ->add('age_export', fn($consultation) => Str::lower($consultation->dossierPatient?->age))
            ->add('type_fichier_export', fn($consultation) => Str::lower($consultation->type_fichier))
            ->add('temperature_export', fn($consultation) => Str::lower($consultation->temperature))
            ->add('poids_export', fn($consultation) => Str::lower($consultation->poids))
            ->add('departement_export', fn($consultation) => Str::lower($consultation->departement?->name))
            ->add('mois_export', fn($consultation) => Str::lower($consultation->mois))
            ->add('date_export', fn(Consultation $model) => optional($model->created_at)->format('d/m/Y'))
            ->add('heure_export', fn(Consultation $model) => optional($model->created_at)->format('H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'numero')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs font-semibold text-center w-8')
                ->sortable(),

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

            Column::make('Type Fiche', 'type_fichier')
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

            // For export
            Column::make('Reference', 'reference_export')->visibleInExport(true)->hidden(),
            Column::make('Nom', 'nom_export')->visibleInExport(true)->hidden(),
            Column::make('Post-nom', 'post_nom_export')->visibleInExport(true)->hidden(),
            Column::make('Prénom', 'prenom_export')->visibleInExport(true)->hidden(),
            Column::make('Genre', 'genre_export')->visibleInExport(true)->hidden(),
            Column::make('Age', 'age_export')->visibleInExport(true)->hidden(),
            Column::make('Type Fiche', 'type_fichier_export')->visibleInExport(true)->hidden(),
            Column::make('Temperature', 'temperature_export')->visibleInExport(true)->hidden(),
            Column::make('Poids', 'poids_export')->visibleInExport(true)->hidden(),
            Column::make('Département', 'departement_export')->visibleInExport(true)->hidden(),
            Column::make('Période', 'mois_export')->visibleInExport(true)->hidden(),
            Column::make('Date', 'date_export')->visibleInExport(true)->hidden(),
            Column::make('Heure', 'heure_export')->visibleInExport(true)->hidden(),
        ];
    }

    // public function filters(): array
    // {
    //     return [
    //         Filter::datetimepicker('created_at'),
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
