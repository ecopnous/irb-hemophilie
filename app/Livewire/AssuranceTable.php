<?php

namespace App\Livewire;

use App\Models\Configs\Assurance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AssuranceTable extends PowerGridComponent
{
    public string $tableName = 'assuranceTable';
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
            ->withCount(['projets', 'patients'])
            ->where(function (Builder $query) {
                $query->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->latest('created_at');
    }

    public function relationSearch(): array
    {
        return [
            'categorisation' => ['name'],
        ];
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
                            <img src="{{ $logoUrl }}" alt="" class="h-10 w-10 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-700" />
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-xs font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                {{ strtoupper(mb_substr($assurance->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="space-y-1">
                            <a href="{{ route(\'settings.assurance.show\', $assurance->id) }}" wire:navigate
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
                    '<x-progress percent="{{ $categorisation->pourcentage }}" title="{{ $categorisation->name }}" />',
                    ['categorisation' => $assurance->categorisation]
                );
            })
            ->add('projets_count_label', fn (Assurance $assurance) => (int) $assurance->projets_count)
            ->add('patients_count_label', fn (Assurance $assurance) => (int) $assurance->patients_count)
            ->add('email_label', fn (Assurance $assurance) => $assurance->email ?: '—')
            ->add('action', function (Assurance $assurance) {
                return Blade::render(
                    '<a href="{{ route(\'settings.assurance.show\', $assurance->id) }}" wire:navigate
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                        Voir detail
                    </a>',
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
            Column::make('Projets', 'projets_count_label', 'projets_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Patients', 'patients_count_label', 'patients_count')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('Email', 'email_label', 'email')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Action', 'action')
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
        ];
    }
}
