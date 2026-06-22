<?php

namespace App\Livewire;

use App\Models\ReceptionSupply;
use App\Services\ReceptionCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class ReceptionSupplyTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'receptionSupplyTable';

    public int $rowCounter = 0;

    public function setUp(): array
    {
        $this->rowCounter = 0;

        return [
            PowerGrid::exportable(fileName: 'papeterie')
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return ReceptionSupply::query()
            ->with('updatedBy')
            ->whereHopitalId(current_hopital_id())
            ->orderBy('designation');
    }

    public function relationSearch(): array
    {
        return [
            'updatedBy' => ['name'],
        ];
    }

    protected function categoryLabel(string $category): string
    {
        return app(ReceptionCatalogService::class)->papeterieCategoryLabels()[$category] ?? ucfirst($category);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('designation_export', fn (ReceptionSupply $item) => $item->designation)
            ->add('reference_export', fn (ReceptionSupply $item) => $item->reference ?: '—')
            ->add('stock_gap', fn (ReceptionSupply $item) => $item->stockGap())
            ->add('status_export', fn (ReceptionSupply $item) => $item->is_active ? 'Actif' : 'Inactif')
            ->add('article', function (ReceptionSupply $item) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-bold text-slate-900 dark:text-white">{{ $designation }}</p>
                        <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $reference }}</p>
                    </div>',
                    [
                        'designation' => $item->designation,
                        'reference' => $item->reference ?: '—',
                    ]
                );
            })
            ->add('category_label', fn (ReceptionSupply $item) => $this->categoryLabel($item->category))
            ->add('unit')
            ->add('planned_stock')
            ->add('current_stock')
            ->add('stock_view', function (ReceptionSupply $item) {
                $low = $item->isLowStock();
                $gap = $item->stockGap();

                return Blade::render(
                    '<div class="space-y-1 text-right">
                        <p class="font-black {{ $stockClass }}">{{ $current }} / {{ $planned }}</p>
                        @if($low)
                            <p class="text-[11px] font-bold text-amber-600 dark:text-amber-300">Stock bas</p>
                        @elseif($gap > 0)
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Manque {{ $gap }}</p>
                        @else
                            <p class="text-[11px] text-emerald-600 dark:text-emerald-300">OK</p>
                        @endif
                    </div>',
                    [
                        'current' => $item->current_stock,
                        'planned' => $item->planned_stock,
                        'stockClass' => $low ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-white',
                        'low' => $low,
                        'gap' => $gap,
                    ]
                );
            })
            ->add('status_badge', function (ReceptionSupply $item) {
                $active = (bool) $item->is_active;
                $class = $active
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';

                return Blade::render(
                    '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">{{ $label }}</span>',
                    ['class' => $class, 'label' => $active ? 'Actif' : 'Inactif']
                );
            });
    }

    public function actions(ReceptionSupply $row): array
    {
        return [
            Button::add('view')
                ->slot('Voir')
                ->class('inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200')
                ->dispatch('view-supply-row', ['rowId' => $row->id]),
            Button::add('edit')
                ->slot('Modifier')
                ->class('inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300')
                ->dispatch('edit-supply-row', ['rowId' => $row->id]),
        ];
    }

    #[\Livewire\Attributes\On('view-supply-row')]
    public function forwardView(int $rowId): void
    {
        $this->dispatch('supply-view', id: $rowId);
    }

    #[\Livewire\Attributes\On('edit-supply-row')]
    public function forwardEdit(int $rowId): void
    {
        $this->dispatch('supply-edit', id: $rowId);
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Article', 'article')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),
            Column::make('Designation', 'designation_export', 'designation')
                ->sortable()
                ->searchable()
                ->hidden(),
            Column::make('Reference', 'reference_export', 'reference')
                ->sortable()
                ->searchable()
                ->hidden(),
            Column::make('Categorie', 'category_label', 'category')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Unite', 'unit')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Stock prevu', 'planned_stock')
                ->sortable()
                ->bodyAttribute('text-xs text-center'),
            Column::make('En stock', 'stock_view', 'current_stock')
                ->sortable()
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),
            Column::make('En stock', 'current_stock', 'current_stock')
                ->hidden(),
            Column::make('Manquant', 'stock_gap', 'stock_gap')
                ->hidden(),
            Column::make('Statut', 'status_badge')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),
            Column::make('Statut', 'status_export', 'is_active')
                ->hidden(),
            Column::action('Actions'),
        ];
    }

    public function filters(): array
    {
        $categories = collect(app(ReceptionCatalogService::class)->papeterieCategoryLabels())
            ->map(fn ($label, $id) => ['id' => $id, 'name' => $label])
            ->values();

        return [
            Filter::select('category', 'category')
                ->dataSource($categories)
                ->optionValue('id')
                ->optionLabel('name'),
            Filter::select('is_active', 'is_active')
                ->dataSource(collect([
                    ['id' => '1', 'name' => 'Actif'],
                    ['id' => '0', 'name' => 'Inactif'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }
}
