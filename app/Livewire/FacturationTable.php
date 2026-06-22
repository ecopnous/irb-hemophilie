<?php

namespace App\Livewire;

use App\Models\facturation\Facturation;
use App\Services\ConsultationBillingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class FacturationTable extends PowerGridComponent
{
    public string $tableName = 'facturationTable';

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
        return Facturation::query()
            ->with([
                'dossierPatient',
                'consultation.dossierPatient',
                'consultation.departement',
                'consultation.projet.assurance',
                'consultation.assurance',
                'consultation.user',
                'consultation.projet',
                'consultation.actes',
            ])
            ->whereHas('consultation', fn($query) => $query->whereHopitalId(current_hopital_id()))
            ->latest('created_at');
    }

    public function relationSearch(): array
    {
        return [
            'dossierPatient' => ['nom', 'postnom', 'prenom', 'nin', 'ins'],
            'consultation' => ['reference', 'type', 'mois'],
        ];
    }

    protected function summary(Facturation $facturation): array
    {
        $consultation = $facturation->consultation;
        $actes = $consultation?->actes ?? collect();
        $billing = app(ConsultationBillingService::class);

        $total = $consultation
            ? $billing->totals($consultation)['patient']
            : (float) $actes->sum(fn ($acte) => (float) ($acte->pivot->montant ?? 0));
        $paid = (float) $facturation->payments()->whereNull('voided_at')->sum('amount');
        $remaining = max(0, $total - $paid);

        $status = match (true) {
            $total <= 0 => 'a_facturer',
            $paid <= 0 => 'en_attente',
            $paid < $total => 'partiel',
            default => 'paye',
        };

        return [
            'actes_count' => $actes->count(),
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'status' => $status,
        ];
    }

    protected function patientLabel(Facturation $facturation): array
    {
        $patient = $facturation->dossierPatient ?: $facturation->consultation?->dossierPatient;

        if (!$patient) {
            return ['name' => 'Patient inconnu', 'identifier' => '-'];
        }

        return [
            'name' => trim(implode(' ', array_filter([
                strtoupper((string) $patient->nom),
                strtoupper((string) $patient->postnom),
                ucfirst((string) $patient->prenom),
            ]))),
            'identifier' => (string) ($patient->nin ?: $patient->ins ?: '-'),
        ];
    }

    protected function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ');
    }

    protected function statusMeta(string $status): array
    {
        return match ($status) {
            'a_facturer' => ['label' => 'A facturer', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'],
            'en_attente' => ['label' => 'En attente', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
            'partiel' => ['label' => 'Paiement partiel', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
            'paye' => ['label' => 'Paye', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
            default => ['label' => ucfirst($status), 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'],
        };
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('reference', fn(Facturation $facturation) => $facturation->consultation?->reference ?? '-')
            ->add('patient', function (Facturation $facturation) {
                $patient = $this->patientLabel($facturation);

                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
                            {{ $patient["name"] }}
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ $patient["identifier"] }}
                        </p>
                    </div>',
                    ['patient' => $patient]
                );
            })
            ->add('contexte', function (Facturation $facturation) {
                $consultation = $facturation->consultation;

                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $departement }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $type }} {{ $mois !== "-" ? "• " . $mois : "" }}
                        </p>
                    </div>',
                    [
                        'departement' => $consultation?->departement?->name ?? '-',
                        'type' => ucfirst((string) ($consultation?->type ?? '-')),
                        'mois' => $consultation?->mois ?? '-',
                    ]
                );
            })
            ->add('prise_en_charge', function (Facturation $facturation) {
                $consultation = $facturation->consultation;
                $billing = app(ConsultationBillingService::class);

                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $projet }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $assurance }}</p>
                    </div>',
                    [
                        'projet' => $consultation ? $billing->coverageLabel($consultation) : 'Paiement direct',
                        'assurance' => $consultation && $billing->hasCoverage($consultation)
                            ? $billing->assuranceName($consultation) . ' (' . number_format($billing->defaultCoverageRate($consultation), 0, ',', ' ') . '%)'
                            : 'Sans assurance',
                    ]
                );
            })
            ->add('medecin', fn(Facturation $facturation) => $facturation->consultation?->user?->name ?? 'Non assigne')
            ->add('actes_count', fn(Facturation $facturation) => $this->summary($facturation)['actes_count'])
            ->add('finances', function (Facturation $facturation) {
                $summary = $this->summary($facturation);

                return Blade::render(
                    '<div class="space-y-1 text-right">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $total }}</p>
                        <p class="text-xs text-emerald-700 dark:text-emerald-300">Paye: {{ $paid }}</p>
                        <p class="text-xs text-amber-700 dark:text-amber-300">Reste: {{ $remaining }}</p>
                    </div>',
                    [
                        'total' => $this->money($summary['total']),
                        'paid' => $this->money($summary['paid']),
                        'remaining' => $this->money($summary['remaining']),
                    ]
                );
            })
            ->add('status_badge', function (Facturation $facturation) {
                $meta = $this->statusMeta($this->summary($facturation)['status']);

                return Blade::render(
                    '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">
                        {{ $label }}
                    </span>',
                    ['class' => $meta['class'], 'label' => $meta['label']]
                );
            })
            ->add('created_label', function (Facturation $facturation) {
                return Blade::render(
                    '<div>
                        <p class="font-medium text-slate-900 dark:text-white">
                            {{ optional($createdAt)->format("d/m/Y") }}
                        </p>
                        <p class="text-slate-500 dark:text-slate-400">
                            {{ optional($createdAt)->format("H:i") }}
                        </p>
                    </div>',
                    ['createdAt' => $facturation->created_at]
                );
            })
            ->add('actions', function (Facturation $facturation) {
                return Blade::render(
                    '<a href="{{ route(\'facturation.show\', $facturation->id) }}"
                        wire:navigate
                        class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20">
                        Ouvrir
                    </a>',
                    ['facturation' => $facturation]
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
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Contexte', 'contexte')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Prise en charge', 'prise_en_charge')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Medecin', 'medecin')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),

            Column::make('Actes', 'actes_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),

            Column::make('Montants', 'finances')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Etat', 'status_badge')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Date', 'created_label', 'created_at')
                ->sortable()
                ->bodyAttribute('text-xs'),

            Column::make('Action', 'actions')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs text-right'),
        ];
    }
}
