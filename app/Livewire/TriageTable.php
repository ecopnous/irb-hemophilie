<?php

namespace App\Livewire;

use App\Models\Consultation;
use App\Support\PowerGridFilterCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class TriageTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'triageTable';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.table.loading';

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        $this->showFilters = true;

        return [
            PowerGrid::exportable(fileName: 'triage-consultations')
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
            ])
            // ->whereNull('user_id')
            ->whereNot('type', 'depistage')
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
            ->add('type_visite', fn (Consultation $consultation) => ucfirst($consultation->type_visite ?? '-'))
            ->add('patient_genre', fn (Consultation $consultation) => $consultation->dossierPatient?->genre)
            ->add('projet_id', fn (Consultation $consultation) => $consultation->projet_id)
            ->add('departement_id', fn (Consultation $consultation) => $consultation->departement_id)
            ->add('reference', function (Consultation $consultation) {
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
            ->add('dossierPatient', function (Consultation $consultation) {
                return Blade::render('<div class="space-y-1">
                        <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
                            <a href="{{ route(\'consultation.prelevement\', $consultation->id) }}" class="hover:text-blue-600" wire:navigate>{{ $consultation->dossierPatient?->full_name }}</a>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ $consultation->dossierPatient?->genre }} ({{ $consultation->dossierPatient?->age }})
                        </p>
                    </div>',
                    ['consultation' => $consultation]
                );
            })
            ->add('departement', function (Consultation $consultation) {
                return Blade::render('<div class="space-y-1">
                        <p class="uppercase tracking-tight">
                           {{ ucwords($consultation->departement?->name ?? \' - \') }}
                        </p>
                        @if($consultation->is_clore)
                            <p class="text-xs font-medium text-green-600 dark:text-green-300">
                                dossier classé
                            </p>
                        @else
                            <p class="text-xs font-medium text-red-600 dark:text-red-300">
                                dossier ouvert
                            </p>
                        @endif
                    </div>',
                    ['consultation' => $consultation]
                );
            })
            ->add('mois', fn (Consultation $consultation) => $consultation->mois ?? '-')
            ->add('date', function (Consultation $consultation) {
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
            ->add('action', function (Consultation $consultation) {
                return Blade::render('
                    <a href="{{ route(\'consultation.prelevement\', $consultation->id) }}" wire:navigate
                        class="inline-flex items-center gap-2 px-3 py-2 text-xs font-bold text-amber-900 dark:text-amber-100 rounded-xl border border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-500/10">
                        Prélever
                    </a>
                ', ['consultation' => $consultation]);
            })
            ->add('reference_export', fn (Consultation $consultation) => Str::lower($consultation->reference))
            ->add('nom_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->nom))
            ->add('post_nom_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->postnom))
            ->add('prenom_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->prenom))
            ->add('genre_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->genre))
            ->add('age_export', fn (Consultation $consultation) => Str::lower($consultation->dossierPatient?->age))
            ->add('type_visite_export', fn (Consultation $consultation) => Str::lower($consultation->type_visite))
            ->add('departement_export', fn (Consultation $consultation) => Str::lower($consultation->departement?->name))
            ->add('mois_export', fn (Consultation $consultation) => Str::lower($consultation->mois))
            ->add('date_export', fn (Consultation $consultation) => optional($consultation->created_at)->format('d/m/Y'))
            ->add('heure_export', fn (Consultation $consultation) => optional($consultation->created_at)->format('H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference', 'reference')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Patient', 'dossierPatient')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Sexe', 'patient_genre', 'patient_genre')
                ->hidden(),

            Column::make('Type Fiche', 'type_visite', 'type_visite')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Projet', 'projet_id', 'projet_id')
                ->hidden(),

            Column::make('Département', 'departement', 'departement_id')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Période', 'mois', 'mois')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Date', 'date', 'created_at')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Action', 'action')
                ->visibleInExport(false),

            Column::make('Reference', 'reference_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Nom', 'nom_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Post-nom', 'post_nom_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Prénom', 'prenom_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Genre', 'genre_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Age', 'age_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Visite', 'type_visite_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Département', 'departement_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Période', 'mois_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Date', 'date_export')
                ->visibleInExport(true)
                ->hidden(),
            Column::make('Heure', 'heure_export')
                ->visibleInExport(true)
                ->hidden(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('reference')
                ->operators(['contains']),

            Filter::select('patient_genre', 'patient_genre')
                ->dataSource(collect([
                    ['id' => 'M', 'name' => 'Homme'],
                    ['id' => 'F', 'name' => 'Femme'],
                ]))
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => $query->whereHas(
                    'dossierPatient',
                    fn (Builder $patientQuery) => $patientQuery->where('genre', $value)
                )),

            Filter::select('type_visite', 'type_visite')
                ->dataSource(collect([
                    ['id' => 'standard', 'name' => 'Standard'],
                    ['id' => 'hémophilie', 'name' => 'Hémophilie'],
                    ['id' => 'drépanocytose', 'name' => 'Drépanocytose'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('departement_id', 'departement_id')
                ->dataSource(PowerGridFilterCache::departements())
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('projet_id', 'projet_id')
                ->dataSource(PowerGridFilterCache::projets())
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::inputText('mois', 'mois')
                ->operators(['contains']),
        ];
    }
}
