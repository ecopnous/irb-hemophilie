<?php

namespace App\Livewire;

use App\Models\Consultation;
use App\Services\DashboardMetricsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\Reactive;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class ReceptionTable extends PowerGridComponent
{
    public string $tableName = 'receptionTable';
    public int $rowCounter = 0;

    #[Reactive]
    public string $search = '';

    #[Reactive]
    public string $type = '';

    #[Reactive]
    public string $genre = '';

    #[Reactive]
    public string $user_id = '';

    #[Reactive]
    public string $departement_id = '';

    #[Reactive]
    public string $assignment = '';

    #[Reactive]
    public $province_id = null;

    #[Reactive]
    public $ville_id = null;

    #[Reactive]
    public $commune_id = null;

    #[Reactive]
    public $age_min = null;

    #[Reactive]
    public $age_max = null;

    #[Reactive]
    public $date_start = null;

    #[Reactive]
    public $date_end = null;

    public function setUp(): array
    {
        $this->rowCounter = 0;

        return [
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    // public $typeId = null;

    // #[On('typeChanged')]
    // public function updateCategory($typeId)
    // {
    //     $this->typeId = $typeId;

    //     $this->resetPage();
    //     $this->fillData();
    // }

    public function datasource(): Builder
    {
        return app(DashboardMetricsService::class)
            ->receptionQuery($this->filterPayload())
            ->with([
                'dossierPatient:id,nom,postnom,prenom,genre,date_naissance',
                'departement:id,name',
                'user:id,name',
            ])
            ->latest('consultations.created_at');
    }

    /**
     * @return array<string, mixed>
     */
    private function filterPayload(): array
    {
        return [
            'search' => $this->search,
            'type' => $this->type,
            'genre' => $this->genre,
            'user_id' => $this->user_id,
            'departement_id' => $this->departement_id,
            'assignment' => $this->assignment,
            'province_id' => $this->province_id,
            'ville_id' => $this->ville_id,
            'commune_id' => $this->commune_id,
            'age_min' => $this->age_min,
            'age_max' => $this->age_max,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
        ];
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('numero', fn () => ++$this->rowCounter)
            ->add('reference', function ($consultation) {
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
            ->add('dossierPatient', function ($consultation) {
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
            ->add('type_fichier', fn($consultation) => ucfirst($consultation->type_fichier ?? '-'))
            ->add('temperature', fn($consultation) => $consultation->temperature === null ? '-' : $consultation->temperature . '°C')
            ->add('pression_arterielle', fn($consultation) => (!$consultation->systolite ? '-' : $consultation->systolite) . ' / ' . (!$consultation->diastolique ? '-' : $consultation->diastolique) . ' mmHg')
            ->add('poids', fn($consultation) => $consultation->poids === null ? '-' : $consultation->poids . ' kg')
            ->add('departement', function ($consultation) {
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
                        } else {
                            return Blade::render('
                                <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                                    Déjà Cloturée
                                </span>
                            ');
                        }

                    default:
                        return Blade::render('
                            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                                <span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                Aucunne Action
                            </span>
                        ');
                }
            });

        // ->add('id')
        // ->add('type')
        // ->add('type_fichier')
        // ->add('is_project_period')
        // ->add('reference')
        // ->add('dossier_patient_id')
        // ->add('departement_id')
        // ->add('assurance_id')
        // ->add('projet_id')
        // ->add('service_id')
        // ->add('hopital_id')
        // ->add('user_id')
        // ->add('laboratoire_id')
        // ->add('imagerie_id')
        // ->add('facturation_id')
        // ->add('symptomes')
        // ->add('antecedents')
        // ->add('allergies')
        // ->add('histoire_maladie')
        // ->add('examen_physique')
        // ->add('diagnostic_presomption')
        // ->add('diagnostic_certitude')
        // ->add('complement_anamnese')
        // ->add('plan_traitement_conduite')
        // ->add('prescription_medicale')
        // ->add('rendez_vous_medical')
        // ->add('issue')
        // ->add('poids')
        // ->add('temperature')
        // ->add('taille')
        // ->add('systolite')
        // ->add('perimetre_cranien')
        // ->add('perimetre_brachial')
        // ->add('frequence_cardiaque')
        // ->add('frequence_respiratoire')
        // ->add('diastolique')
        // ->add('saturation_oxygene')
        // ->add('glycemie')
        // ->add('mois')
        // ->add('created_at_formatted', fn(Consultation $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'numero')
                ->bodyAttribute('text-xs font-semibold text-center w-10'),

            Column::make('Reference', 'reference')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Patient', 'dossierPatient')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Type Fiche', 'type_fichier')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Température', 'temperature')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('PA', 'pression_arterielle')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Poids', 'poids')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Département', 'departement')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Période', 'mois')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Medecin Traitant', 'user')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Date', 'date')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),
            Column::make('Action', 'action')
        ];
    }
}
