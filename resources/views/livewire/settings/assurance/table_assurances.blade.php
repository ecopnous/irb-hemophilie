<?php

use App\Models\Configs\Assurance;
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
                ['index' => 'name', 'label' => 'Nom'],
                ['index' => 'type', 'label' => 'Type'],
                ['index' => 'email', 'label' => 'Email'],
                ['index' => 'phone', 'label' => 'Telephone'],
                ['index' => 'categorisation.name', 'label' => 'Categorisation'],
            ],
            'rows' => Assurance::query()
                ->with('categorisation')
                ->when($this->search, function (Builder $query) {
                    return $query->where(function (Builder $query) {
                        $query->where('name', 'like', "%{$this->search}%")
                            ->orWhere('type', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('name')
                ->paginate($this->quantity)
                ->through(function (Assurance $assurance) {
                    $assurance->name = Str::title((string) $assurance->name);

                    return $assurance;
                })
                ->withQueryString(),
        ];
    }
}; ?>

<div>
    <x-table :$headers :$rows filter paginate loading link="{{ url('/settings/assurance/show/{id}') }}" />
</div>
