<?php

use App\Models\Configs\Departement;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Title('Departement'), Layout('layouts::app.other.support_tech')] class extends Component {
    use WithPagination;

    public ?int $quantity = 10;
    public ?string $search = null;
    public array $headers = [['index' => 'id', 'label' => '#'], ['index' => 'name', 'label' => 'Département'], ['index' => 'services_count', 'label' => 'Services associés'], ['index' => 'actes_count', 'label' => 'Actes associés'], ['index' => 'users_count', 'label' => 'Corps médical']];

    #[Computed]
    public function rows()
    {
        return Departement::query()
            ->withCount('services')
            ->withCount('actes')
            ->withCount('users')
            ->when($this->search, function (Builder $query) {
                return $query->where('name', 'like', "%{$this->search}%");
            })
            ->paginate($this->quantity)
            ->through(function (Departement $departement) {
                $departement->name = Str::ucfirst(mb_strtolower((string) $departement->name));

                return $departement;
            })
            ->withQueryString();
    }
}; ?>

<section class="w-full">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Support technique', 'link' => 'settings.departement.index', 'icon' => 'cog-6-tooth'],
                ['label' => 'Département', 'icon' => 'swatch'],
            ]" />
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
                Départements Médicaux
            </h1>
        </div>
    </div>
    <x-table :$headers :rows="$this->rows" filter paginate loading link="departement/show/{id}" />
</section>
