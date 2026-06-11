<?php

namespace App\Livewire;

use App\Models\Configs\Assurance;
use App\Services\AssuranceInvoiceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AssuranceFacturationTable extends PowerGridComponent
{
    public string $tableName = 'assuranceFacturationTable';

    public int $rowCounter = 0;

    public function setUp(): array
    {
        $this->rowCounter = 0;

        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Assurance::query()
            ->with(['categorisation'])
            ->withCount([
                'projets',
                'patients',
                'consultations' => fn ($query) => $query
                    ->whereHopitalId(current_hopital_id())
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year),
            ])
            ->where(function (Builder $query) {
                $query->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->latest('name');
    }

    public function relationSearch(): array
    {
        return [
            'categorisation' => ['name'],
        ];
    }

    protected function monthStats(Assurance $assurance): array
    {
        return app(AssuranceInvoiceService::class)->statsForAssurance(
            $assurance,
            current_hopital_id(),
        );
    }

    protected function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' $';
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('name_export', fn (Assurance $assurance) => $assurance->name)
            ->add('assurance', function (Assurance $assurance) {
                return Blade::render(
                    '<div class="flex items-center gap-3">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="" class="h-11 w-11 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-700" />
                        @else
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sm font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                {{ strtoupper(mb_substr($assurance->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="space-y-1">
                            <a href="{{ route(\'facturation.assurance.show\', $assurance->id) }}" wire:navigate
                                class="font-bold tracking-tight text-slate-900 hover:text-sky-600 dark:text-white dark:hover:text-sky-300">
                                {{ $assurance->name }}
                            </a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $assurance->reference }}</p>
                        </div>
                    </div>',
                    [
                        'assurance' => $assurance,
                        'logoUrl' => $assurance->logoUrl(),
                    ]
                );
            })
            ->add('type_label', fn (Assurance $assurance) => ucfirst($assurance->type))
            ->add('categorisation_label', function (Assurance $assurance) {
                if (! $assurance->categorisation) {
                    return Blade::render('<span class="text-xs font-semibold text-amber-600 dark:text-amber-300">Non classee</span>');
                }

                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $percent }}% prise en charge</p>
                    </div>',
                    [
                        'name' => $assurance->categorisation->name,
                        'percent' => number_format((float) $assurance->categorisation->pourcentage, 0),
                    ]
                );
            })
            ->add('forfait_label', function (Assurance $assurance) {
                if (! $assurance->forfait_actif) {
                    return Blade::render('<span class="text-xs font-semibold text-slate-500">Non</span>');
                }

                return Blade::render(
                    '<div class="space-y-1">
                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">OUI</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $price }} / patient</p>
                    </div>',
                    ['price' => $this->money((float) ($assurance->prix_patient ?? 0))]
                );
            })
            ->add('activity_label', function (Assurance $assurance) {
                $stats = $this->monthStats($assurance);

                return Blade::render(
                    '<div class="space-y-1 text-right">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $consultations }} consult.</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $patients }} patients</p>
                    </div>',
                    [
                        'consultations' => $stats['consultations'],
                        'patients' => $stats['patients'],
                    ]
                );
            })
            ->add('montant_label', function (Assurance $assurance) {
                $stats = $this->monthStats($assurance);

                return Blade::render(
                    '<p class="text-right font-bold text-slate-900 dark:text-white">{{ $montant }}</p>',
                    ['montant' => $this->money($stats['montant'])]
                );
            })
            ->add('action', function (Assurance $assurance) {
                return Blade::render(
                    '<div class="flex flex-col items-end gap-2">
                        <a href="{{ route(\'facturation.assurance.show\', $assurance->id) }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300">
                            Fiche
                        </a>
                        <a href="{{ route(\'facturation.assurance.invoice\', $assurance->id) }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                            Facture
                        </a>
                    </div>',
                    ['assurance' => $assurance]
                );
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Assurance', 'assurance', 'name_export')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Type', 'type_label', 'type')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Categorisation', 'categorisation_label')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Forfait', 'forfait_label')
                ->bodyAttribute('text-xs'),
            Column::make('Activite mois', 'activity_label')
                ->bodyAttribute('text-xs'),
            Column::make('Montant mois', 'montant_label')
                ->bodyAttribute('text-xs'),
            Column::make('Actions', 'action')
                ->bodyAttribute('text-xs text-right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('type', 'type')
                ->dataSource(collect([
                    ['id' => 'assurance', 'name' => 'Assurance'],
                    ['id' => 'entreprise', 'name' => 'Entreprise'],
                    ['id' => 'organisation', 'name' => 'Organisation'],
                    ['id' => 'partenaire', 'name' => 'Partenaire'],
                    ['id' => 'particulier', 'name' => 'Particulier'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),
            Filter::select('forfait_actif', 'forfait_actif')
                ->dataSource(collect([
                    ['id' => '1', 'name' => 'Avec forfait'],
                    ['id' => '0', 'name' => 'Sans forfait'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }
}
