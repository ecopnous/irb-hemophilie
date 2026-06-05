<?php

use App\Models\User;
use Carbon\Carbon;
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
            'headers' => [['index' => 'name', 'label' => 'Nom'], ['index' => 'prenom', 'label' => 'Prenom'], ['index' => 'email', 'label' => 'Email'], ['index' => 'role', 'label' => 'Role'], ['index' => 'departement.name', 'label' => 'Departement'], ['index' => 'last_seen_at', 'label' => 'Dernière connexion']],
            'rows' => User::query()
                ->with(['departement'])
                ->where('hopital_id', current_hopital_id())
                ->when($this->search, function (Builder $query) {
                    return $query->where(function (Builder $query) {
                        $query
                            ->where('name', 'like', "%{$this->search}%")
                            ->orWhere('prenom', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                            ->orWhere('role', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('name')
                ->paginate($this->quantity)
                ->through(function (User $user) {
                    $user->name = Str::title(trim($user->name));
                    $user->prenom = Str::title((string) $user->prenom);
                    // $user->statut_presence = $user->last_seen_at instanceof Carbon && $user->last_seen_at->greaterThanOrEqualTo(now()->subMinutes(6)) ? 'En ligne' : 'Hors ligne';

                    return $user;
                })
                ->withQueryString(),
        ];
    }
}; ?>

<div wire:poll.60s>
    <x-table :$headers :$rows filter paginate loading link="{{ url('/settings/user/show/{id}') }}" />
</div>
