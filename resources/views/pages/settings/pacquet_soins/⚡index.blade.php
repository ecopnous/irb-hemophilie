<?php

use App\Models\Configs\PacquetSoin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Title('Pacquets de soins'), Layout('layouts::app.other.support_tech')] class extends Component {
    use WithPagination;

    public int $quantity = 10;
    public ?string $search = null;

    public array $headers = [['index' => 'name', 'label' => 'Paquet'], ['index' => 'categorisation_name', 'label' => 'Categorisation'], ['index' => 'actes_count', 'label' => 'Actes'], ['index' => 'montant_total', 'label' => 'Montant total'], ['index' => 'paiement_badge', 'label' => 'Paiement']];

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
        return PacquetSoin::query()
            ->with('categorisation')
            ->withCount('actes')
            ->withSum('actes', 'montant')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $inner) {
                    $inner
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('categorisation', function (Builder $categorisation) {
                            $categorisation->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate($this->quantity)
            ->through(function (PacquetSoin $paquet) {
                $paquet->name = Str::ucfirst(mb_strtolower((string) $paquet->name));
                $paquet->categorisation_name = $paquet->categorisation?->name ?: 'Non classe';
                $paquet->montant_total = number_format((float) ($paquet->actes_sum_montant ?? 0), 2, ',', ' ') . ' $';
                $paquet->paiement_badge = $paquet->paiement_directe ? 'Direct' : 'Differe';

                return $paquet;
            })
            ->withQueryString();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => PacquetSoin::query()->count(),
            'direct' => PacquetSoin::query()->where('paiement_directe', true)->count(),
            'with_actes' => PacquetSoin::query()->has('actes')->count(),
        ];
    }
}; ?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Gestion des paquets de soins') }}</flux:heading>
    <x-header_default :title="__('Paquets de soins')" :navigations="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Support technique', 'link' => 'settings/hopital', 'icon' => 'cog-6-tooth'],
        ['label' => 'Paquets de soins', 'icon' => 'squares-plus'],
    ]">
        <x-slot:actions>
            <x-button icon="squares-plus" position="left" href="{{ route('settings.paquet.create') }}" wire:navigate>
                Nouveau paquet de soins
            </x-button>
        </x-slot>
    </x-header_default>

    {{-- <x-pages::settings.layout :heading="__('Paquets de soins')" :subheading="__('Catalogue des paquets de soins et de leurs actes medicaux')">
        <x-slot:actions>
            
        </x-slot>

        <div class="grid gap-4 md:grid-cols-3">
            <div
                class="rounded-2xl border border-zinc-200 bg-white/90 p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Paquets enregistres</p>
                <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $this->stats['total'] }}</p>
            </div>
            <div
                class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-900/70 dark:bg-emerald-950/30">
                <p class="text-sm text-emerald-700 dark:text-emerald-300">Paiement direct</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-900 dark:text-emerald-100">
                    {{ $this->stats['direct'] }}</p>
            </div>
            <div
                class="rounded-2xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-900/70 dark:bg-sky-950/30">
                <p class="text-sm text-sky-700 dark:text-sky-300">Paquets avec actes</p>
                <p class="mt-2 text-3xl font-semibold text-sky-900 dark:text-sky-100">{{ $this->stats['with_actes'] }}
                </p>
            </div>
        </div>

        <div
            class="mt-6 rounded-2xl border border-zinc-200 bg-white/95 p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
        </div>
    </x-pages::settings.layout> --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div
            class="rounded-2xl border border-zinc-200 bg-white/90 p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/90">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Paquets enregistres</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $this->stats['total'] }}</p>
        </div>
        <div
            class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-900/70 dark:bg-emerald-950/30">
            <p class="text-sm text-emerald-700 dark:text-emerald-300">Paiement direct</p>
            <p class="mt-2 text-3xl font-semibold text-emerald-900 dark:text-emerald-100">
                {{ $this->stats['direct'] }}</p>
        </div>
        <div
            class="rounded-2xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-900/70 dark:bg-sky-950/30">
            <p class="text-sm text-sky-700 dark:text-sky-300">Paquets avec actes</p>
            <p class="mt-2 text-3xl font-semibold text-sky-900 dark:text-sky-100">{{ $this->stats['with_actes'] }}
            </p>
        </div>
    </div>
    <x-table :$headers :rows="$this->rows" filter paginate loading link="paquet/show/{id}" />
</section>
