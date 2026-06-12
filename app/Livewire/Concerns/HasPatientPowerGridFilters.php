<?php

namespace App\Livewire\Concerns;

use App\Support\AgeBrackets;
use App\Support\PowerGridFilterCache;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Facades\Filter;

trait HasPatientPowerGridFilters
{
    public function patientPowerGridFilters(): array
    {
        $hopitalId = current_hopital_id();

        return [
            Filter::inputText('nin')
                ->operators(['contains']),

            Filter::inputText('telephone')
                ->operators(['contains']),

            Filter::inputText('email')
                ->operators(['contains']),

            Filter::select('genre', 'genre')
                ->dataSource(collect([
                    ['id' => 'M', 'name' => 'Homme'],
                    ['id' => 'F', 'name' => 'Femme'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('patient_age_bracket', 'patient_age_bracket')
                ->dataSource(collect(AgeBrackets::options()))
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => AgeBrackets::apply($query, $value)),

            Filter::select('assurance_id', 'assurance_id')
                ->dataSource(PowerGridFilterCache::assurances())
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('tag_id', 'tag_id')
                ->dataSource(PowerGridFilterCache::tags())
                ->optionValue('id')
                ->optionLabel('name')
                ->builder(fn (Builder $query, string $value) => $query->whereHas(
                    'tags',
                    fn (Builder $tagQuery) => $tagQuery->where('tags.id', $value)
                )),

            Filter::select('etat_civil', 'etat_civil')
                ->dataSource(collect([
                    ['id' => 'Célibataire', 'name' => 'Célibataire'],
                    ['id' => 'Marié', 'name' => 'Marié'],
                    ['id' => 'Divorcé', 'name' => 'Divorcé'],
                    ['id' => 'Veu(f)ve', 'name' => 'Veu(f)ve'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('user_id', 'user_id')
                ->dataSource(PowerGridFilterCache::users($hopitalId))
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
        return $this->patientPowerGridFilters();
    }
}
