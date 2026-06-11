<?php

namespace App\Livewire;

use App\Models\ReceptionBaseService;
use App\Services\ReceptionCatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ReceptionBaseServiceTable extends PowerGridComponent
{
    public string $tableName = 'receptionBaseServiceTable';

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
        return ReceptionBaseService::query()
            ->with('updatedBy')
            ->whereHopitalId(current_hopital_id())
            ->orderBy('name');
    }

    public function relationSearch(): array
    {
        return [
            'updatedBy' => ['name'],
        ];
    }

    protected function categoryLabel(string $category): string
    {
        return app(ReceptionCatalogService::class)->serviceCategoryLabels()[$category] ?? ucfirst($category);
    }

    protected function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' $';
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('name_export', fn (ReceptionBaseService $service) => $service->name)
            ->add('service', function (ReceptionBaseService $service) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-bold text-slate-900 dark:text-white">{{ $name }}</p>
                        <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $code }}</p>
                    </div>',
                    [
                        'name' => $service->name,
                        'code' => $service->code ?: '—',
                    ]
                );
            })
            ->add('category_label', fn (ReceptionBaseService $service) => $this->categoryLabel($service->category))
            ->add('price_value', fn (ReceptionBaseService $service) => (float) $service->price)
            ->add('price_label', fn (ReceptionBaseService $service) => $this->money((float) $service->price))
            ->add('status_badge', function (ReceptionBaseService $service) {
                $active = (bool) $service->is_active;
                $class = $active
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';

                return Blade::render(
                    '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">{{ $label }}</span>',
                    ['class' => $class, 'label' => $active ? 'Actif' : 'Inactif']
                );
            })
            ->add('updated_label', fn (ReceptionBaseService $service) => optional($service->updated_at)->format('d/m/Y H:i') ?: '—');
    }

    public function actions(ReceptionBaseService $row): array
    {
        return [
            Button::add('view')
                ->slot('Voir')
                ->class('inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200')
                ->dispatch('view-service-row', ['rowId' => $row->id]),
            Button::add('edit')
                ->slot('Modifier')
                ->class('inline-flex items-center rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-300')
                ->dispatch('edit-service-row', ['rowId' => $row->id]),
        ];
    }

    #[\Livewire\Attributes\On('view-service-row')]
    public function forwardView(int $rowId): void
    {
        $this->dispatch('service-view', id: $rowId);
    }

    #[\Livewire\Attributes\On('edit-service-row')]
    public function forwardEdit(int $rowId): void
    {
        $this->dispatch('service-edit', id: $rowId);
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Service', 'service', 'name_export')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Categorie', 'category_label', 'category')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Prix', 'price_label', 'price_value')
                ->sortable()
                ->bodyAttribute('text-xs text-right font-bold'),
            Column::make('Statut', 'status_badge')
                ->bodyAttribute('text-xs'),
            Column::make('MAJ', 'updated_label', 'updated_at')
                ->sortable()
                ->bodyAttribute('text-xs'),
            Column::action('Actions'),
        ];
    }

    public function filters(): array
    {
        $categories = collect(app(ReceptionCatalogService::class)->serviceCategoryLabels())
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
