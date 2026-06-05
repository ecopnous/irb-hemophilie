<?php

namespace App\Livewire;

use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class GroupeHopitalHopitalTable extends PowerGridComponent
{
    public string $tableName = 'groupeHopitalHopitalTable';

    public int $groupeId;

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Hopital::query()
            ->whereHas('groupes', fn(Builder $query) => $query->where('groupe_hopitals.id', $this->groupeId))
            ->withCount('patients');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', function (Hopital $hopital) {
                return sprintf(
                    '<div>
                        <p class="font-bold text-slate-900 dark:text-white">%s</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">%s</p>
                    </div>',
                    e($hopital->name),
                    e($hopital->reference),
                );
            })
            ->add('name_export', fn(Hopital $hopital) => $hopital->name)
            ->add('reference')
            ->add('type')
            ->add('devise', fn(Hopital $hopital) => strtoupper($hopital->devise))
            ->add('patients_count')
            ->add('status', function (Hopital $hopital) {
                $classes = $hopital->is_actif
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300'
                    : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300';

                return sprintf(
                    '<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold %s">%s</span>',
                    $classes,
                    $hopital->is_actif ? 'Actif' : 'Inactif',
                );
            })
            ->add('status_export', fn(Hopital $hopital) => $hopital->is_actif ? 'Actif' : 'Inactif')
            ->add('adresse', fn(Hopital $hopital) => collect([$hopital->quartier, $hopital->avenue, $hopital->numero])->filter()->implode(', '))
            ->add('created_at_formatted', fn(Hopital $hopital) => Carbon::parse($hopital->created_at)->format('d/m/Y H:i'));
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Hopital', 'name')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable()
                ->visibleInExport(false),

            Column::make('Hopital', 'name_export')
                ->hidden()
                ->visibleInExport(true),

            Column::make('Reference', 'reference')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Type', 'type')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Devise', 'devise')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Patients', 'patients_count')
                ->bodyAttribute('text-xs')
                ->sortable(),

            Column::make('Statut', 'status')
                ->bodyAttribute('text-xs')
                ->visibleInExport(false),

            Column::make('Statut', 'status_export')
                ->hidden()
                ->visibleInExport(true),

            Column::make('Adresse', 'adresse')
                ->bodyAttribute('text-xs')
                ->searchable(),

            Column::action('Action'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('type', 'type')
                ->dataSource(collect([
                    ['id' => 'public', 'name' => 'Public'],
                    ['id' => 'prive', 'name' => 'Prive'],
                    ['id' => 'privée', 'name' => 'Privee'],
                    ['id' => 'clinique', 'name' => 'Clinique'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    #[\Livewire\Attributes\On('access-hospital-from-group')]
    public function accessHospital(int $rowId): void
    {
        $this->redirectRoute('settings.hopital.show', ['id' => $rowId], navigate: true);
    }

    public function actions(Hopital $row): array
    {
        return [
            Button::add('show')
                ->slot('Voir fiche')
                ->id()
                ->class('inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/10')
                ->dispatch('access-hospital-from-group', ['rowId' => $row->id]),
        ];
    }
}
