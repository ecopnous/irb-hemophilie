<?php

namespace App\Livewire;

use App\Models\prescription\Pharmacie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PharmacyPharmacyTable extends PowerGridComponent
{
    public string $tableName = 'pharmacyPharmacyTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showToggleColumns()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Pharmacie::query()->where('hopital_id', current_hopital_id())->latest('created_at');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nom')
            ->add('status_badge', function (Pharmacie $p) {
                $class = $p->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700';
                $label = $p->is_active ? 'Active' : 'Inactive';
                return Blade::render('<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">{{ $label }}</span>', compact('class', 'label'));
            })
            ->add('created_label', fn(Pharmacie $p) => optional($p->created_at)->format('d/m/Y H:i'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')->bodyAttribute('text-xs'),
            Column::make('Nom pharmacie', 'nom')->sortable()->searchable()->bodyAttribute('text-xs font-semibold'),
            Column::make('Statut', 'status_badge')->visibleInExport(false)->bodyAttribute('text-xs'),
            Column::make('Date', 'created_label', 'created_at')->sortable()->bodyAttribute('text-xs'),
        ];
    }
}
