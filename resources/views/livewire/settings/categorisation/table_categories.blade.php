<?php

use App\Models\Configs\Categorisation;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public ?int $quantity = 10;
    public ?string $search = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['index' => 'id', 'label' => '#'],
                ['index' => 'name', 'label' => 'Nom'],
                ['index' => 'description', 'label' => 'Description'],
                ['index' => 'pourcentage', 'label' => 'Prise en Charge'],
                ['index' => 'categorisation.name', 'label' => 'Catégorie Parent'],
                // ['index' => 'patients_count', 'label' => 'Patients'],
            ],
            'rows' => Categorisation::query()
                // ->withCount('patients')
                ->with('categorisation')
                ->when($this->search, function (Builder $query) {
                    return $query->where('name', 'like', "%{$this->search}%");
                })
                ->paginate($this->quantity)
                ->through(function (Categorisation $departement) {
                    $departement->name = Str::ucfirst(mb_strtolower((string) $departement->name));

                    return $departement;
                })
                ->withQueryString(),
        ];
    }
}; ?>


<div>
    <x-table :$headers :$rows filter paginate loading link="https://google.com/?user={id}">
        {{-- On cible la colonne 'pourcentage' --}}
        @interact('column_pourcentage', $categorisation)
            <x-progress percent="{{ $categorisation->pourcentage }}" title="Pourcentage" />
        @endinteract
    </x-table>
</div>
