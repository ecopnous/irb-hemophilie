<?php

namespace App\Livewire;

use App\Models\prescription\Prescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PharmacyPrescriptionTable extends PowerGridComponent
{
    public string $tableName = 'pharmacyPrescriptionTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showToggleColumns()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Prescription::query()
            ->with(['consultation', 'dossierPatient', 'servedBy', 'medicaments'])
            ->where('hopital_id', current_hopital_id())
            ->latest('created_at');
    }

    public function relationSearch(): array
    {
        return [
            'consultation' => ['reference'],
            'dossierPatient' => ['nom', 'postnom', 'prenom'],
            'servedBy' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('numero', fn(Prescription $p) => $p->reference ?: ('PRES-' . $p->id))
            ->add('patient', fn(Prescription $p) => $p->dossierPatient?->full_name ?: '-')
            ->add('prescripteur', fn(Prescription $p) => $p->consultation?->user?->name ?: '-')
            ->add('date', fn(Prescription $p) => optional($p->created_at)->format('d/m/Y H:i'))
            ->add('items', fn(Prescription $p) => $p->medicaments->count())
            ->add('status_badge', function (Prescription $p) {
                $meta = match ($p->status) {
                    'served' => ['label' => 'Servie', 'class' => 'bg-emerald-100 text-emerald-700'],
                    'partial' => ['label' => 'Partielle', 'class' => 'bg-sky-100 text-sky-700'],
                    'cancelled' => ['label' => 'Annulee', 'class' => 'bg-red-100 text-red-700'],
                    default => ['label' => 'Brouillon', 'class' => 'bg-slate-100 text-slate-700'],
                };

                return Blade::render('<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $class }}">{{ $label }}</span>', $meta);
            })
            ->add('consultation_ref', fn(Prescription $p) => $p->consultation?->reference ?: '-');
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')->bodyAttribute('text-xs'),
            Column::make('Numero', 'numero')->sortable()->searchable()->bodyAttribute('text-xs font-semibold'),
            Column::make('Patient', 'patient')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Prescripteur', 'prescripteur')->sortable()->searchable()->bodyAttribute('text-xs'),
            Column::make('Date', 'date', 'created_at')->sortable()->bodyAttribute('text-xs'),
            Column::make('Items', 'items')->sortable()->bodyAttribute('text-xs text-center'),
            Column::make('Etat', 'status_badge')->visibleInExport(false)->bodyAttribute('text-xs'),
            Column::make('Consultation', 'consultation_ref')->sortable()->searchable()->bodyAttribute('text-xs'),
        ];
    }
}
