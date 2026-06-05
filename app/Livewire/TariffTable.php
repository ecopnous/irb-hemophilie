<?php

namespace App\Livewire;

use App\Models\Configs\Acte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class TariffTable extends PowerGridComponent
{
    public string $tableName = 'tariffTable';

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
            ->with(['departement', 'service', 'updatedBy'])
            ->where('is_delete', false)
            ->latest('updated_at');
    }

    public function relationSearch(): array
    {
        return [
            'departement' => ['name'],
            'service' => ['name'],
            'updatedBy' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code', fn(Acte $acte) => $acte->code ?: '-')
            ->add('name')
            ->add('departement_name', fn(Acte $acte) => $acte->departement?->name ?: '-')
            ->add('service_name', fn(Acte $acte) => $acte->service?->name ?: '-')
            ->add('price', fn(Acte $acte) => number_format((float) ($acte->base_price ?? $acte->montant ?? 0), 2, ',', ' '))
            ->add('status_badge', function (Acte $acte) {
                $active = (bool) $acte->is_active;
                $class = $active
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                    : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
                $label = $active ? 'Actif' : 'Inactif';

                return Blade::render(
                    '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">{{ $label }}</span>',
                    ['class' => $class, 'label' => $label]
                );
            })
            ->add('updated_by_name', fn(Acte $acte) => $acte->updatedBy?->name ?: '-')
            ->add('updated_label', function (Acte $acte) {
                return optional($acte->updated_at)->format('d/m/Y H:i') ?: '-';
            })
            ->add('actions', function (Acte $acte) {
                $url = route('facturation.tariffs', ['acte' => $acte->id]);

                return Blade::render(
                    '<a href="{{ $url }}" wire:navigate class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20">Modifier</a>',
                    ['url' => $url]
                );
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Code', 'code')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),

            Column::make('Acte', 'name')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs font-semibold'),

            Column::make('Departement', 'departement_name')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),

            Column::make('Service', 'service_name')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),

            Column::make('Prix', 'price')
                ->sortable()
                ->bodyAttribute('text-xs text-right'),

            Column::make('Statut', 'status_badge')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs'),

            Column::make('Mis a jour par', 'updated_by_name')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),

            Column::make('Date MAJ', 'updated_label', 'updated_at')
                ->sortable()
                ->bodyAttribute('text-xs'),

            Column::make('Action', 'actions')
                ->visibleInExport(false)
                ->bodyAttribute('text-xs text-right'),
        ];
    }
}
