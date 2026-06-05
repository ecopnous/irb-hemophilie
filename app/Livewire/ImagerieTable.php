<?php

namespace App\Livewire;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ImagerieTable extends PowerGridComponent
{
    public string $tableName = 'imagerieTable';

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
        return Consultation::query()
            ->with([
                'dossierPatient',
                'departement',
                'user',
                'imagerie',
                'actes' => fn($query) => $query->with('departement', 'service'),
            ])
            ->whereHas('imagerie')
            ->whereHopitalId(current_hopital_id())
            ->latest('created_at');
    }

    public function relationSearch(): array
    {
        return [
            'dossierPatient' => ['nom', 'postnom', 'prenom', 'nin', 'ins'],
            'departement' => ['name'],
            'user' => ['name'],
            'imagerie' => ['renseignement', 'statut', 'note'],
        ];
    }

    protected function acteBelongsToImagerie($acte): bool
    {
        $departement = $acte->departement;

        if (!$departement) {
            return false;
        }

        $name = strtolower((string) $departement->name);
        $ref = strtolower((string) ($departement->ref ?? ''));

        return str_contains($name, 'imagerie') || $ref === 'img';
    }

    protected function examensFor(Consultation $consultation): array
    {
        return $consultation->actes
            ->filter(fn($acte) => $this->acteBelongsToImagerie($acte))
            ->map(fn($acte) => [
                'id' => (int) $acte->id,
                'name' => (string) $acte->name,
                'documented' => filled(data_get($acte, 'pivot.clinique'))
                    || filled(data_get($acte, 'pivot.protocole'))
                    || filled(data_get($acte, 'pivot.cloture')),
            ])
            ->values()
            ->all();
    }

    protected function patientIdentity(Consultation $consultation): array
    {
        $patient = $consultation->dossierPatient;

        if (!$patient) {
            return ['name' => 'Patient inconnu', 'identifier' => '-', 'demography' => '-'];
        }

        $genre = match ($patient->genre) {
            'M' => 'Masculin',
            'F' => 'Feminin',
            default => '-',
        };

        return [
            'name' => (string) $patient->full_name,
            'identifier' => trim((string) ($patient->nin ?? '-') . ($patient->ins ? ' - INS ' . $patient->ins : '')),
            'demography' => trim($genre . ($patient->age ? ' - ' . $patient->age : '')),
        ];
    }

    protected function statusMeta(?string $status): array
    {
        return match ($status) {
            'en attente' => ['label' => 'En attente', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
            'en cours' => ['label' => 'En cours', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
            'terminé' => ['label' => 'Termine', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
            'bloqué' => ['label' => 'Bloque', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'],
            default => ['label' => ucfirst((string) ($status ?: 'indefini')), 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'],
        };
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('reference', fn(Consultation $consultation) => $consultation->reference ?? '-')
            ->add('patient', function (Consultation $consultation) {
                $patient = $this->patientIdentity($consultation);

                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
                            <a href="{{ route(\'imagerie.show\', $consultation->id) }}" class="hover:text-fuchsia-600" wire:navigate>
                                {{ $patient["name"] }}
                            </a>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">{{ $patient["identifier"] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $patient["demography"] }}</p>
                    </div>',
                    ['consultation' => $consultation, 'patient' => $patient]
                );
            })
            ->add('provenance', function (Consultation $consultation) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $departement }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $medecin }}</p>
                    </div>',
                    [
                        'departement' => $consultation->departement?->name ?? '-',
                        'medecin' => $consultation->user?->name ?? 'Medecin non assigne',
                    ]
                );
            })
            ->add('medecin', fn(Consultation $consultation) => $consultation->user?->name ?? 'Non assigne')
            ->add('examens', function (Consultation $consultation) {
                $examens = $this->examensFor($consultation);
                $renseignement = (string) ($consultation->imagerie?->renseignement ?? '');

                return Blade::render(
                    '<div class="space-y-2">
                        @if ($renseignement !== "")
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $renseignement }}</p>
                        @endif

                        @if (!empty($examens))
                            <div class="flex flex-wrap gap-2">
                                @foreach ($examens as $examen)
                                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $examen["documented"] ? "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300" : "border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700 dark:border-fuchsia-500/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-300" }}">
                                        {{ $examen["name"] }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-400">Aucun examen d imagerie demande.</p>
                        @endif
                    </div>',
                    ['examens' => $examens, 'renseignement' => $renseignement]
                );
            })
            ->add('statut_badge', function (Consultation $consultation) {
                $meta = $this->statusMeta($consultation->imagerie?->statut);

                return Blade::render(
                    '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">
                        {{ $label }}
                    </span>',
                    ['class' => $meta['class'], 'label' => $meta['label']]
                );
            })
            ->add('created_label', function (Consultation $consultation) {
                return Blade::render(
                    '<div>
                        <p class="font-medium text-slate-900 dark:text-white">
                            {{ optional($createdAt)->format("d/m/Y") }}
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ optional($createdAt)->format("H:i") }}
                        </p>
                    </div>',
                    ['createdAt' => $consultation->created_at]
                );
            })
            ->add('action_link', function (Consultation $consultation) {
                return Blade::render(
                    '<a href="{{ route(\'imagerie.show\', $consultation->id) }}"
                        wire:navigate
                        class="inline-flex items-center justify-center rounded-xl border border-fuchsia-200 bg-fuchsia-50 px-3 py-2 text-xs font-bold text-fuchsia-700 transition hover:bg-fuchsia-100 dark:border-fuchsia-500/30 dark:bg-fuchsia-500/10 dark:text-fuchsia-300 dark:hover:bg-fuchsia-500/20">
                        Ouvrir
                    </a>',
                    ['consultation' => $consultation]
                );
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Reference', 'reference')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),

            Column::make('Patient', 'patient')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs'),

            Column::make('Provenance', 'provenance')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs'),

            Column::make('Medecin', 'medecin')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs')
                ->hidden(),

            Column::make('Examens demandes', 'examens')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs'),

            Column::make('Statut', 'statut_badge')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs'),

            Column::make('Date', 'created_label', 'created_at')
                ->sortable()
                ->bodyAttribute('text-xs'),

            Column::make('Action', 'action_link')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs text-right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('created_at'),
        ];
    }
}
