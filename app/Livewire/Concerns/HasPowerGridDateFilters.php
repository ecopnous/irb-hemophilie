<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Concerns\Filter as PowerGridFilter;

trait HasPowerGridDateFilters
{
    use PowerGridFilter {
        clearAllFilters as powerGridClearAllFilters;
        clearFilter as powerGridClearFilter;
    }

    public ?string $dateStart = null;

    public ?string $dateEnd = null;

    public function bootHasPowerGridDateFilters(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function updatedDateStart(?string $value): void
    {
        $this->resetPage();
        $this->syncDateFilterBadge('date_start', 'Date début', $value);
    }

    public function updatedDateEnd(?string $value): void
    {
        $this->resetPage();
        $this->syncDateFilterBadge('date_end', 'Date fin', $value);
    }

    public function clearFilter(string $field = ''): void
    {
        if ($field === 'date_start' || $field === '') {
            $this->dateStart = null;
        }

        if ($field === 'date_end' || $field === '') {
            $this->dateEnd = null;
        }

        if (in_array($field, ['date_start', 'date_end'], true)) {
            $this->enabledFilters = array_values(array_filter(
                $this->enabledFilters,
                fn (array $filter) => $filter['field'] !== $field
            ));
            $this->persistState('filters');

            return;
        }

        $this->powerGridClearFilter($field);
    }

    public function clearAllFilters(): void
    {
        $this->dateStart = null;
        $this->dateEnd = null;

        $this->powerGridClearAllFilters();
    }

    protected function applyCreatedAtDateFilters(Builder $query): Builder
    {
        return $query
            ->when(filled($this->dateStart), fn (Builder $q) => $q->whereDate('created_at', '>=', $this->dateStart))
            ->when(filled($this->dateEnd), fn (Builder $q) => $q->whereDate('created_at', '<=', $this->dateEnd));
    }

    private function syncDateFilterBadge(string $field, string $label, ?string $value): void
    {
        if (blank($value)) {
            $this->enabledFilters = array_values(array_filter(
                $this->enabledFilters,
                fn (array $filter) => $filter['field'] !== $field
            ));

            return;
        }

        if (! collect($this->enabledFilters)->contains('field', $field)) {
            $this->addEnabledFilters($field, $label);
        }
    }
}
