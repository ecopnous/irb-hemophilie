<?php

namespace App\Livewire;

use App\Models\Configs\Hopital;
use App\Services\HospitalSessionService;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;

final class HopitalTable extends PowerGridComponent
{
    use WithExport;
    public string $tableName = 'hopitalTable';

    public function setUp(): array
    {
        return [
            PowerGrid::exportable(fileName: 'liste-des-hopitaux')
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Hopital::query()->withCount('patients');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', function ($hopital) {
                return sprintf(
                    '<div>
                        <a href="#"><b>%s</b></a><br>
                        <span class="text-xs text-slate-500">%s</span> (%s)%s
                    </div>',
                    e(ucfirst($hopital->name)),
                    e($hopital->reference),
                    e(ucfirst($hopital->type)),
                    current_hopital_id() === $hopital->id
                        ? '<br><span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700">Hopital actif</span>'
                        : ''
                );
            })
            ->add('name_export', fn($hopital) => $hopital->name)
            ->add('reference_export', fn($hopital) => $hopital->reference)
            ->add('type_export', fn($hopital) => $hopital->type)
            ->add('devise')
            ->add('code_postal')
            ->add('is_actif')
            ->add('site_web')
            ->add('numero_licence')
            ->add('autorite_regulation')
            ->add('description')
            ->add('quartier')
            ->add('avenue')
            ->add('numero')
            ->add('country_id')
            ->add('province_id')
            ->add('ville_id')
            ->add('commune_id')
            ->add('created_at_formatted', fn(Hopital $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')->bodyAttribute('text-xs'),

            Column::make('Nom', 'name')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->visibleInExport(false)
                ->searchable(),

            Column::make('Nbre de patients', 'patients_count')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Devise', 'devise')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            Column::make('Code postal', 'code_postal')
                ->bodyAttribute('text-xs')
                ->sortable()
                ->searchable(),

            // Column::make('Is actif', 'is_actif')
            //     ->sortable()
            //     ->searchable(),


            // Column::make('Description', 'description')
            //     ->sortable()
            //     ->searchable(),

            // Column::make('Quartier', 'quartier')
            //     ->sortable()
            //     ->searchable(),

            // Column::make('Avenue', 'avenue')
            //     ->sortable()
            //     ->searchable(),

            // Column::make('Numero', 'numero')
            //     ->sortable()
            //     ->searchable(),

            // Column::make('Country id', 'country_id'),
            // Column::make('Province id', 'province_id'),
            // Column::make('Ville id', 'ville_id'),
            // Column::make('Commune id', 'commune_id'),
            // Column::make('Created at', 'created_at_formatted', 'created_at')
            //     ->sortable(),

            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datetimepicker('created_at'),
        ];
    }

    #[\Livewire\Attributes\On('access-hospital')]
    public function accessHospital(int $rowId): void
    {
        $hopital = Hopital::query()->findOrFail($rowId);

        app(HospitalSessionService::class)->setCurrent(request(), $hopital);

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function actions(Hopital $row): array
    {
        return [
            Button::add('edit')
                ->slot('Acceder à l\'hopital')
                ->id()
                ->class('inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-500/40')
                ->dispatch('access-hospital', ['rowId' => $row->id])
        ];
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
