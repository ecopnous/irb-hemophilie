<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Title('Actes medicaux'), Layout('layouts::app.other.support_tech')] class extends Component {
    use WithPagination;

    public $departement;
    public ?int $quantity = 10;
    public ?string $search = null;

    public function mount($id)
    {
        $this->departement = Departement::findOrFail($id);
    }

    public array $headers = [['index' => 'name', 'label' => 'Acte'], ['index' => 'montant', 'label' => 'Prix'], ['index' => 'departement.name', 'label' => 'Département'], ['index' => 'service.name', 'label' => 'Sérvice']];

    #[Computed]
    public function rows()
    {
        return Acte::query()
            ->when($this->search, function (Builder $query) {
                return $query->where('name', 'like', "%{$this->search}%");
            })
            ->paginate($this->quantity)
            ->through(function (Acte $acte) {
                $acte->name = Str::ucfirst(mb_strtolower((string) $acte->name));

                return $acte;
            })
            ->withQueryString();
    }
};
?>

<div>
    <div class="max-w-7xl mx-auto mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Support technique', 'link' => 'settings.departement.index', 'icon' => 'cog-6-tooth'],
                ['label' => 'Département', 'icon' => 'swatch'],
            ]" />
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
                {{ ucfirst($departement->name) }}
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <flux:button icon="printer" />
            <flux:button href="{{ route('settings.departement.newActe', $departement->id) }}" variant="primary"
                icon="plus" color="indigo" wire:navigate>
                Nouvel acte médical</flux:button>
        </div>
    </div>
    <div class="max-w-7xl mx-auto mb-8">
        <x-card header="Chef de département">
            Aucun chef de departement assigner
        </x-card>
    </div>
    <div class="max-w-7xl mx-auto mb-8">
        <x-table :$headers :rows="$this->rows" filter paginate loading link="departement/show/{id}" />
    </div>
</div>
