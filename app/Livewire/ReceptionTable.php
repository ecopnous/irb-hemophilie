<?php

namespace App\Livewire;

use App\Models\Configs\Departement;
use App\Models\Configs\Projet;
use App\Models\Consultation;
use App\Models\User;
use App\Support\AgeBrackets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Concerns\Filter as PowerGridFilter;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class ReceptionTable extends PowerGridComponent
{
    use PowerGridFilter {
        clearAllFilters as powerGridClearAllFilters;
        clearFilter as powerGridClearFilter;
    }

    public string $tableName = 'receptionTable';

    public int $rowCounter = 0;

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        $this->rowCounter = 0;
        $this->showFilters = true;

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('components.powergrid.reception-total'),
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
            ->when(filled($this->dateStart), fn (Builder $query) => $query->whereDate('created_at', '>=', $this->dateStart))
            ->when(filled($this->dateEnd), fn (Builder $query) => $query->whereDate('created_at', '<=', $this->dateEnd))
            ->latest('created_at');
    }

    public function updatedDateStart(?string $value): void
    {
        $this->resetPage();
        $this->syncDateFilterBadge('date_start', 'Date début', $value);
    }

    public function updatedDateEnd(?string $value): void
    {
        $this->resetPage();
        $this->syncDateFilterBadge('date_end', 'Date fin', $value);
    }

    public function clearFilter(string $field = ''): void
    {
        if ($field === 'date_start' || $field === '') {
            $this->dateStart = null;
        }

        if ($field === 'date_end' || $field === '') {
            $this->dateEnd = null;
        }

        if (in_array($field, ['date_start', 'date_end'], true)) {
            $this->enabledFilters = array_values(array_filter(
                $this->enabledFilters,
                fn (array $filter) => $filter['field'] !== $field
            ));
            $this->persistState('filters');

            return;
        }

        $this->powerGridClearFilter($field);
    }

    public function clearAllFilters(): void
    {
        $this->dateStart = null;
        $this->dateEnd = null;

        $this->powerGridClearAllFilters();
    }

    private function syncDateFilterBadge(string $field, string $label, ?string $value): void
    {
        if (blank($value)) {
            $this->enabledFilters = array_values(array_filter(
                $this->enabledFilters,
                fn (array $filter) => $filter['field'] !== $field
            ));

            return;
        }

        if (! collect($this->enabledFilters)->contains('field', $field)) {
            $this->addEnabledFilters($field, $label);
        }
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
            // ->add('numero', fn () => ++$this->rowCounter)
            ->add('reference', function (Consultation $consultation) {
                return Blade::render('<div class="space-y-1">
                        @if($consultation->is_visite_program)
                            <p class="font-bold tracking-tight text-blue-600 dark:text-blue-300">
                                Rendez-Vous
                            </p>
                        @elseif($consultation->type === "depistage")
                            <p class="font-bold tracking-tight text-green-600 dark:text-green-300">
                                Examen
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
                            <a href="{{ route(\'patient.show\', $consultation->dossierPatient->id) }}" class="hover:text-blue-600" wire:navigate>{{ $consultation->dossierPatient?->full_name }}</a>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ $consultation->dossierPatient?->genre }} ({{ $consultation->dossierPatient?->age }})
                        </p>
                    </div>',
                    ['consultation' => $consultation]
                );
            })
            ->add('type', fn (Consultation $consultation) => $consultation->type)
            ->add('type_fichier', fn (Consultation $consultation) => ucfirst($consultation->type_fichier ?? '-'))
            ->add('patient_genre', fn (Consultation $consultation) => $consultation->dossierPatient?->genre)
            ->add('patient_age_bracket', fn () => null)
            ->add('temperature', fn (Consultation $consultation) => $consultation->temperature === null ? '-' : $consultation->temperature . '°C')
            ->add('pression_arterielle', fn (Consultation $consultation) => (! $consultation->systolite ? '-' : $consultation->systolite) . ' / ' . (! $consultation->diastolique ? '-' : $consultation->diastolique) . ' mmHg')
            ->add('poids', fn (Consultation $consultation) => $consultation->poids === null ? '-' : $consultation->poids . ' kg')
            ->add('departement_id', fn (Consultation $consultation) => $consultation->departement_id)
            ->add('is_clore', fn (Consultation $consultation) => $consultation->is_clore)
            ->add('is_clore_label', fn (Consultation $consultation) => $consultation->is_clore ? 'Classé' : 'Ouvert')
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
            ->add('user_id', fn (Consultation $consultation) => $consultation->user_id)
            ->add('user', fn (Consultation $consultation) => ucfirst($consultation->user?->name ?? '-'))
            ->add('created_at')
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
                switch ($consultation->type) {
                    case 'depistage':
                        return Blade::render('
                                <a href="{{ route(\'consultation.show\', $consultation->id) }}" wire:navigate
                                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-500/40">
                                    Résultat
                                </a>
                            ', ['consultation' => $consultation]);

                    case 'consultation':
                        if ($consultation->user_id !== null && $consultation->issue === null) {
                            return Blade::render('
                                <a href="{{ route(\'consultation.show\', $consultation->id) }}" wire:navigate
                                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-500/40">
                                    Consulter
                                </a>
                            ', ['consultation' => $consultation]);
                        } elseif ($consultation->user_id === null) {
                            return Blade::render('
                                <a href="{{ route(\'consultation.prelevement\', $consultation->id) }}" wire:navigate
                                    class="inline-flex items-center gap-2 px-3 py-2 text-xs font-bold  text-amber-900 dark:text-amber-100 rounded-xl border border-amber-200 bg-amber-50/80 dark:border-amber-500/20 dark:bg-amber-500/10">
                                    Orienter
                                </a>
                            ', ['consultation' => $consultation]);
                        }

                        return Blade::render('
                                <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                                    Déjà Cloturée
                                </span>
                            ');

                    default:
                        return Blade::render('
                            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                                <span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                Aucunne Action
                            </span>
                        ');
                }
            });
    }

    public function columns(): array
    {
        return [
            // Column::make('#', 'numero')
            //     ->bodyAttribute('text-xs font-semibold text-center w-10'),

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

            Column::make('Type Fiche', 'type_fichier', 'type_fichier')
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

    public function filters(): array
    {
        $hopitalId = current_hopital_id();

        return [
            Filter::inputText('reference')
                ->operators(['contains']),

            Filter::select('is_clore', 'is_clore')
                ->dataSource(collect([
                    ['id' => '1', 'name' => 'Classé'],
                    ['id' => '0', 'name' => 'Ouvert'],
                ]))
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => $query->where(
                    'is_clore',
                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                )),

            Filter::select('type', 'type')
                ->dataSource(collect([
                    ['id' => 'consultation', 'name' => 'Consultation'],
                    ['id' => 'depistage', 'name' => 'Depistage'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('type_fichier', 'type_fichier')
                ->dataSource(collect([
                    ['id' => 'standard', 'name' => 'Standard'],
                    ['id' => 'hemophile', 'name' => 'Hemophile'],
                    ['id' => 'redac', 'name' => 'Redac'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),

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

            Filter::select('patient_age_bracket', 'patient_age_bracket')
                ->dataSource(collect(AgeBrackets::options()))
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => $query->whereHas(
                    'dossierPatient',
                    fn (Builder $patientQuery) => AgeBrackets::apply($patientQuery, $value)
                )),

            Filter::select('departement_id', 'departement_id')
                ->dataSource(
                    Departement::query()->orderBy('name')->get(['id', 'name'])
                )
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('projet_id', 'projet_id')
                ->dataSource(
                    Projet::query()->orderBy('name')->get(['id', 'name'])
                )
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::inputText('mois', 'mois')
                ->operators(['contains']),

            Filter::select('user_id', 'user_id')
                ->dataSource(
                    User::query()
                        ->when($hopitalId, fn ($query) => $query->where('hopital_id', $hopitalId))
                        ->orderBy('name')
                        ->get(['id', 'name'])
                )
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::dynamic('date_start', 'date_start')
                ->component('powergrid.filters.date-start'),

            Filter::dynamic('date_end', 'date_end')
                ->component('powergrid.filters.date-end'),
        ];
    }
}
