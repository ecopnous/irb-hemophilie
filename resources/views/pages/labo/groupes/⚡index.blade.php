<?php

use App\Models\Configs\GroupeExamen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Groupes d\'examens'), Layout('layouts::app.other.laboratoire')] class extends Component {
    use WithPagination;

    public int $quantity = 10;
    public ?string $search = null;

    public array $headers = [
        ['index' => 'name', 'label' => 'Groupe'],
        ['index' => 'service_name', 'label' => 'Service'],
        ['index' => 'actes_count', 'label' => 'Examens'],
        ['index' => 'montant_total', 'label' => 'Montant total'],
        ['index' => 'status_badge', 'label' => 'Statut'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingQuantity(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function rows()
    {
        return GroupeExamen::query()
            ->with('service')
            ->withCount('actes')
            ->withSum('actes', 'montant')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $inner) {
                    $inner
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('service', function (Builder $service) {
                            $service->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate($this->quantity)
            ->through(function (GroupeExamen $groupe) {
                $groupe->name = Str::ucfirst(mb_strtolower((string) $groupe->name));
                $groupe->service_name = $groupe->service?->name ?: 'Tous services';
                $groupe->montant_total = number_format((float) ($groupe->actes_sum_montant ?? 0), 2, ',', ' ') . ' $';
                $groupe->status_badge = $groupe->is_active ? 'Actif' : 'Inactif';

                return $groupe;
            })
            ->withQueryString();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => GroupeExamen::query()->count(),
            'active' => GroupeExamen::query()->where('is_active', true)->count(),
            'with_actes' => GroupeExamen::query()->has('actes')->count(),
        ];
    }
};
?>

<div class="space-y-6 mx-auto max-w-7xl">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-2">
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Laboratoire', 'link' => 'laboratoire.index', 'icon' => 'beaker'],
                ['label' => 'Groupes d\'examens', 'icon' => 'rectangle-group'],
            ]" />
            <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                Groupes d'examens
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Composez des ensembles d'examens de laboratoire pour une prescription rapide.
            </p>
        </div>

        <flux:button href="{{ route('laboratoire.groupes.create') }}" wire:navigate variant="primary" color="sky"
            icon="plus">
            Nouveau groupe
        </flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Groupes</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $this->stats['total'] }}</p>
        </div>
        <div
            class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Actifs</p>
            <p class="mt-2 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['active'] }}</p>
        </div>
        <div
            class="rounded-2xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700 dark:text-sky-300">Avec examens</p>
            <p class="mt-2 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['with_actes'] }}</p>
        </div>
    </div>

    <div
        class="overflow-hidden p-4 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <x-table :$headers :rows="$this->rows" filter paginate loading link="show/{id}" />
    </div>
</div>
