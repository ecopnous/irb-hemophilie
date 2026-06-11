<?php

namespace App\Livewire;

use App\Models\Configs\Assurance;
use App\Models\DossierPatient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class PatientTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'patientTable';
    public bool $deferLoading = true;
    public string $loadingComponent = 'components.table.loading';
    public int $rowCounter = 0;

    public function setUp(): array
    {
        $this->rowCounter = 0;

        return [
            PowerGrid::exportable(fileName: 'dossiers-patients')
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
        return DossierPatient::query()
            ->with(['user', 'assurance'])
            ->whereHopitalId(current_hopital_id())
            ->latest('created_at');
    }

    public function relationSearch(): array
    {
        return [
            'user' => ['name'],
            'assurance' => ['name'],
        ];
    }

    protected function genderLabel(?string $gender): string
    {
        return match ($gender) {
            'M' => 'Masculin',
            'F' => 'Feminin',
            default => '-',
        };
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('numero', fn () => ++$this->rowCounter)
            ->add('nin')
            ->add('dossier', function (DossierPatient $patient) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
                            <a href="{{ route(\'patient.show\', $patient->id) }}" class="hover:text-blue-600" wire:navigate>
                                {{ $patient->full_name }}
                            </a>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            NIN: {{ $patient->nin ?? "-" }} {{ $patient->ins ? " - INS " . $patient->ins : "" }}
                        </p>
                    </div>',
                    ['patient' => $patient]
                );
            })
            ->add('identifiants_export', fn(DossierPatient $patient) => trim(($patient->nin ?? '-') . ' ' . ($patient->ins ?? '')))
            ->add('demographie', function (DossierPatient $patient) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $genre }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $age }}{{ $birthdate !== "-" ? " - Ne le " . $birthdate : "" }}
                        </p>
                    </div>',
                    [
                        'genre' => $this->genderLabel($patient->genre),
                        'age' => $patient->age ?? '-',
                        'birthdate' => $patient->formatted_birthdate ?? '-',
                    ]
                );
            })
            ->add('contact', function (DossierPatient $patient) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $telephone }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $email }}</p>
                    </div>',
                    [
                        'telephone' => $patient->telephone ?? 'Telephone non renseigne',
                        'email' => $patient->email ?? 'Email non renseigne',
                    ]
                );
            })
            ->add('couverture', function (DossierPatient $patient) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $assurance }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $etatCivil }}</p>
                    </div>',
                    [
                        'assurance' => $patient->assurance?->name ?? 'Paiement direct',
                        'etatCivil' => $patient->etat_civil ?? 'Etat civil non renseigne',
                    ]
                );
            })
            ->add('suivi', function (DossierPatient $patient) {
                return Blade::render(
                    '<div class="space-y-1 text-center">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $count }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">consultation(s)</p>
                    </div>',
                    ['count' => (int) $patient->consultations_count]
                );
            })
            ->add('created_by', fn(DossierPatient $patient) => $patient->user?->name ?? 'Non assigne')
            ->add('created_at_label', function (DossierPatient $patient) {
                return Blade::render(
                    '<div>
                        <p class="font-medium text-slate-900 dark:text-white">
                            {{ optional($createdAt)->format("d/m/Y") }}
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ optional($createdAt)->format("H:i") }}
                        </p>
                    </div>',
                    ['createdAt' => $patient->created_at]
                );
            })
            ->add('actions', function (DossierPatient $patient) {
                return Blade::render(
                    '<a href="{{ route(\'patient.show\', $patient->id) }}"
                        wire:navigate
                        class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/20">
                        Ouvrir
                    </a>',
                    ['patient' => $patient]
                );
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'numero')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs font-semibold text-center w-10'),

            Column::make('Dossier', 'dossier')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Identifiants', 'identifiants_export', 'nin')
                ->searchable()
                ->sortable()
                ->hidden(),

            Column::make('Demographie', 'demographie')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Contact', 'contact')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Couverture', 'couverture')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Cree par', 'created_by')
                ->searchable()
                ->sortable()
                ->bodyAttribute('text-xs'),

            Column::make('Date creation', 'created_at_label', 'created_at')
                ->sortable()
                ->bodyAttribute('text-xs'),

            Column::make('Action', 'actions')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs text-right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('genre', 'genre')
                ->dataSource(collect([
                    ['id' => 'M', 'name' => 'Masculin'],
                    ['id' => 'F', 'name' => 'Feminin'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),
            Filter::select('assurance_id', 'assurance_id')
                ->dataSource(Assurance::query()->orderBy('name')->get(['id', 'name']))
                ->optionValue('id')
                ->optionLabel('name'),
            Filter::inputText('nin')->operators(['contains']),
            Filter::inputText('telephone')->operators(['contains']),
        ];
    }
}
