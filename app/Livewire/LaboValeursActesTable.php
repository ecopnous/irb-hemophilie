<?php

namespace App\Livewire;

use App\Models\Configs\Acte;
use App\Models\Configs\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class LaboValeursActesTable extends PowerGridComponent
{
    public string $tableName = 'laboValeursActesTable';

    public ?int $selectedActeId = null;

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
        return Acte::query()
            ->with(['service', 'departement'])
            ->whereHas('departement', fn($query) => $query->where('ref', 'labo'))
            ->orderBy('name');
    }

    public function relationSearch(): array
    {
        return [
            'service' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', function (Acte $acte) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-bold text-slate-900 dark:text-white">{{ $acte->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">ID #{{ $acte->id }}</p>
                    </div>',
                    ['acte' => $acte]
                );
            })
            ->add('service_name', fn(Acte $acte) => $acte->service?->name ?? 'Aucun service')
            ->add('unite', fn(Acte $acte) => $acte->unite ?: '-')
            ->add('plage_generale', fn(Acte $acte) => ($acte->min ?? '—') . ' / ' . ($acte->max ?? '—'))
            ->add('plage_homme', fn(Acte $acte) => ($acte->homme_min ?? '—') . ' / ' . ($acte->homme_max ?? '—'))
            ->add('plage_femme', fn(Acte $acte) => ($acte->femme_min ?? '—') . ' / ' . ($acte->femme_max ?? '—'))
            ->add('configured_label', function (Acte $acte) {
                $configured = filled($acte->unite)
                    || filled($acte->min)
                    || filled($acte->max)
                    || filled($acte->homme_min)
                    || filled($acte->homme_max)
                    || filled($acte->femme_min)
                    || filled($acte->femme_max);

                return Blade::render(
                    '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $configured ? \'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300\' : \'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300\' }}">
                        {{ $configured ? \'Configuré\' : \'À compléter\' }}
                    </span>',
                    ['configured' => $configured]
                );
            })
            ->add('selection_action', function (Acte $acte) {
                $isSelected = (int) $this->selectedActeId === (int) $acte->id;

                return Blade::render(
                    '<a href="{{ route(\'laboratoire.valeurs_exactes\', [\'acte\' => $acte->id]) }}"
                        wire:navigate
                        class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-xs font-bold transition {{ $isSelected ? \'bg-cyan-600 text-white shadow-sm\' : \'border border-slate-200 bg-white text-slate-700 hover:border-cyan-200 hover:text-cyan-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-cyan-500/40 dark:hover:text-cyan-300\' }}">
                        {{ $isSelected ? \'Sélectionné\' : \'Modifier\' }}
                    </a>',
                    ['acte' => $acte, 'isSelected' => $isSelected]
                );
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Acte', 'name', 'name')
                ->searchable()
                ->sortable(),

            Column::make('Service', 'service_name', 'service.name')
                ->searchable()
                ->sortable(),

            Column::make('Unité', 'unite', 'unite')
                ->searchable()
                ->sortable(),

            Column::make('Plage générale', 'plage_generale')
                ->visibleInExport(false),

            Column::make('Homme', 'plage_homme')
                ->visibleInExport(false),

            Column::make('Femme', 'plage_femme')
                ->visibleInExport(false),

            Column::make('Statut', 'configured_label')
                ->visibleInExport(false),

            Column::make('Action', 'selection_action')
                ->visibleInExport(false)
                ->bodyAttribute('text-right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('unite')->operators(['contains']),
            Filter::select('service_id', 'service_id')
                ->dataSource(Service::query()->whereHas('departement', fn($query) => $query->where('ref', 'labo'))->orderBy('name')->get(['id', 'name']))
                ->optionLabel('name')
                ->optionValue('id'),
        ];
    }
}
