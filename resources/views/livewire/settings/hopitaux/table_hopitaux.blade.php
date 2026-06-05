<?php

use App\Models\Configs\Hopital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;

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
                ['index' => 'reference', 'label' => 'Reference'],
                ['index' => 'nom', 'label' => 'Nom'],
                ['index' => 'type', 'label' => 'Type'],
                ['index' => 'devise', 'label' => 'Devise'],
                ['index' => 'code_postal', 'label' => 'Code Postal'],
            ],
            'rows' => Hopital::query()
                ->when($this->search, function (Builder $query) {
                    return $query->where('nom', 'like', "%{$this->search}%")
                        ->orWhere('reference', 'like', "%{$this->search}%");
                })
                ->paginate($this->quantity)
                ->through(function (Hopital $hopital) {
                    $hopital->nom = Str::ucfirst(mb_strtolower((string) $hopital->nom));

                    return $hopital;
                })
                ->withQueryString(),
        ];
    }
}; ?>

<div>
    <x-table :$headers :$rows filter paginate loading link="https://google.com/?user={id}" />
</div>
