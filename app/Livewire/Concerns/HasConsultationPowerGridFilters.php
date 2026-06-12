<?php

namespace App\Livewire\Concerns;

use App\Models\Configs\Departement;
use App\Models\Configs\Projet;
use App\Models\User;
use App\Support\AgeBrackets;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Facades\Filter;

trait HasConsultationPowerGridFilters
{
    public function consultationPowerGridFilters(): array
    {
        $hopitalId = current_hopital_id();

        return [
            Filter::inputText('reference')
                ->operators(['contains']),

            Filter::select('is_clore', 'is_clore')
                ->dataSource(collect([
                    ['id' => '1', 'name' => 'Classé'],
                    ['id' => '0', 'name' => 'Ouvert'],
                ]))
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => $query->where(
                    'is_clore',
                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                )),

            Filter::select('type', 'type')
                ->dataSource(collect([
                    ['id' => 'consultation', 'name' => 'Consultation'],
                    ['id' => 'depistage', 'name' => 'Depistage'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('type_fichier', 'type_fichier')
                ->dataSource(collect([
                    ['id' => 'standard', 'name' => 'Standard'],
                    ['id' => 'hemophile', 'name' => 'Hemophile'],
                    ['id' => 'redac', 'name' => 'Redac'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('patient_genre', 'patient_genre')
                ->dataSource(collect([
                    ['id' => 'M', 'name' => 'Homme'],
                    ['id' => 'F', 'name' => 'Femme'],
                ]))
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => $query->whereHas(
                    'dossierPatient',
                    fn (Builder $patientQuery) => $patientQuery->where('genre', $value)
                )),

            Filter::select('patient_age_bracket', 'patient_age_bracket')
                ->dataSource(collect(AgeBrackets::options()))
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => $query->whereHas(
                    'dossierPatient',
                    fn (Builder $patientQuery) => AgeBrackets::apply($patientQuery, $value)
                )),

            Filter::select('departement_id', 'departement_id')
                ->dataSource(
                    Departement::query()->orderBy('name')->get(['id', 'name'])
                )
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('projet_id', 'projet_id')
                ->dataSource(
                    Projet::query()->orderBy('name')->get(['id', 'name'])
                )
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::inputText('mois', 'mois')
                ->operators(['contains']),

            Filter::select('user_id', 'user_id')
                ->dataSource(
                    User::query()
                        ->when($hopitalId, fn ($query) => $query->where('hopital_id', $hopitalId))
                        ->orderBy('name')
                        ->get(['id', 'name'])
                )
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::dynamic('date_start', 'date_start')
                ->component('powergrid.filters.date-start'),

            Filter::dynamic('date_end', 'date_end')
                ->component('powergrid.filters.date-end'),
        ];
    }

    public function filters(): array
    {
        return $this->consultationPowerGridFilters();
    }
}
