<?php

namespace App\Livewire;

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class TariffTable extends PowerGridComponent
{
    public string $tableName = 'tariffTable';

    public int $rowCounter = 0;

    public bool $canEdit = true;

    public function setUp(): array
    {
        $this->rowCounter = 0;

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
            ->where(function (Builder $query) {
                $query->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->orderBy('name');
    }

    public function relationSearch(): array
    {
        return [
            'departement' => ['name'],
            'service' => ['name'],
            'updatedBy' => ['name'],
        ];
    }

    protected function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' $';
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('row_num', fn () => ++$this->rowCounter)
            ->add('id')
            ->add('code_label', fn (Acte $acte) => $acte->code ?: '—')
            ->add('name_export', fn (Acte $acte) => $acte->name)
            ->add('acte', function (Acte $acte) {
                return Blade::render(
                    '<div class="space-y-1">
                        <p class="font-bold text-slate-900 dark:text-white">{{ $name }}</p>
                        <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $code }}</p>
                    </div>',
                    [
                        'name' => $acte->name,
                        'code' => $acte->code ?: 'Sans code',
                    ]
                );
            })
            ->add('departement_name', fn (Acte $acte) => $acte->departement?->name ?: '—')
            ->add('service_name', fn (Acte $acte) => $acte->service?->name ?: '—')
            ->add('price_value', fn (Acte $acte) => (float) ($acte->base_price ?? $acte->montant ?? 0))
            ->add('price', function (Acte $acte) {
                $amount = (float) ($acte->base_price ?? $acte->montant ?? 0);

                return Blade::render(
                    '<p class="text-right font-black text-slate-900 dark:text-white">{{ $price }}</p>',
                    ['price' => $this->money($amount)]
                );
            })
            ->add('status_badge', function (Acte $acte) {
                $active = (bool) $acte->is_active;
                $class = $active
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
                $label = $active ? 'Actif' : 'Inactif';

                return Blade::render(
                    '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">{{ $label }}</span>',
                    ['class' => $class, 'label' => $label]
                );
            })
            ->add('updated_by_name', fn (Acte $acte) => $acte->updatedBy?->name ?: '—')
            ->add('updated_label', fn (Acte $acte) => optional($acte->updated_at)->format('d/m/Y H:i') ?: '—');
    }

    public function actions(Acte $row): array
    {
        if (! $this->canEdit) {
            return [];
        }

        return [
            Button::add('edit')
                ->slot('Modifier')
                ->id()
                ->class('inline-flex items-center justify-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300')
                ->dispatch('edit-tariff-row', ['rowId' => $row->id]),
        ];
    }

    #[\Livewire\Attributes\On('edit-tariff-row')]
    public function forwardEdit(int $rowId): void
    {
        $this->dispatch('tariff-edit', id: $rowId);
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_num')->bodyAttribute('text-xs font-semibold text-center w-10'),
            Column::make('Acte', 'acte', 'name_export')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Departement', 'departement_name')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Service', 'service_name')
                ->sortable()
                ->searchable()
                ->bodyAttribute('text-xs'),
            Column::make('Prix', 'price', 'price_value')
                ->sortable()
                ->bodyAttribute('text-xs'),
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

            Column::action('Action'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('departement_id', 'departement_id')
                ->dataSource(
                    Departement::query()
                        ->orderBy('name')
                        ->get(['id', 'name'])
                )
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
