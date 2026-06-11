<?php

use App\Models\Configs\Acte;
use App\Models\Configs\MedicalActPriceHistory;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Grille tarifaire'), Layout('layouts::app.other.facturation')] class extends Component {
    public bool $showEditModal = false;

    public ?int $edit_id = null;

    public ?string $edit_name = null;

    public ?string $edit_price = null;

    public ?string $edit_code = null;

    public bool $edit_active = true;

    public function mount(): void
    {
        $acteId = request()->integer('acte');
        if ($acteId) {
            $this->startEdit($acteId);
        }
    }

    public function canManageTariffs(): bool
    {
        return in_array(auth()->user()?->role, [
            'admin',
            'super_admin',
            'developper',
            'support_tech',
            'caissier',
        ], true);
    }

    #[Computed]
    public function stats(): array
    {
        $base = Acte::query()
            ->where(function ($query) {
                $query->where('is_delete', false)->orWhereNull('is_delete');
            });

        $active = (clone $base)->where('is_active', true)->count();
        $inactive = (clone $base)->where('is_active', false)->count();
        $avgPrice = (float) (clone $base)->avg('base_price') ?: (float) (clone $base)->avg('montant');
        $updatesThisMonth = MedicalActPriceHistory::query()
            ->whereMonth('changed_at', now()->month)
            ->whereYear('changed_at', now()->year)
            ->count();

        return [
            'total' => (clone $base)->count(),
            'active' => $active,
            'inactive' => $inactive,
            'avg_price' => round($avgPrice, 2),
            'updates_month' => $updatesThisMonth,
        ];
    }

    #[Computed]
    public function priceHistory(): Collection
    {
        if (! $this->edit_id) {
            return collect();
        }

        return MedicalActPriceHistory::query()
            ->with('changedBy')
            ->where('acte_id', $this->edit_id)
            ->latest('changed_at')
            ->limit(6)
            ->get();
    }

    #[On('tariff-edit')]
    public function startEdit(int $id): void
    {
        $act = Acte::query()
            ->where(function ($query) {
                $query->where('is_delete', false)->orWhereNull('is_delete');
            })
            ->findOrFail($id);

        $this->edit_id = $act->id;
        $this->edit_name = $act->name;
        $this->edit_price = number_format((float) ($act->base_price ?? $act->montant ?? 0), 2, '.', '');
        $this->edit_code = $act->code;
        $this->edit_active = (bool) $act->is_active;
        $this->showEditModal = true;
        unset($this->priceHistory);
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->reset(['edit_id', 'edit_name', 'edit_price', 'edit_code', 'edit_active']);
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        if (! $this->canManageTariffs()) {
            Flux::toast('Vous n\'avez pas les droits pour modifier la grille tarifaire.', variant: 'danger');

            return;
        }

        $validated = $this->validate([
            'edit_id' => ['required', 'integer', 'exists:actes,id'],
            'edit_name' => ['required', 'string', 'max:255'],
            'edit_price' => ['required', 'numeric', 'min:0'],
            'edit_code' => ['nullable', 'string', 'max:50'],
            'edit_active' => ['boolean'],
        ]);

        $act = Acte::query()->findOrFail($validated['edit_id']);
        $oldPrice = (float) ($act->base_price ?? $act->montant ?? 0);
        $newPrice = round((float) $validated['edit_price'], 2);

        $act->forceFill([
            'name' => $validated['edit_name'],
            'code' => $validated['edit_code'],
            'base_price' => $newPrice,
            'montant' => $newPrice,
            'is_active' => $validated['edit_active'],
            'updated_by' => Auth::id(),
        ])->save();

        if (abs($oldPrice - $newPrice) > 0.009) {
            MedicalActPriceHistory::query()->create([
                'acte_id' => $act->id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'changed_by' => Auth::id(),
                'changed_at' => now(),
            ]);
        }

        $this->dispatch('pg:eventRefresh-tariffTable');
        $this->cancelEdit();

        Flux::toast(
            heading: 'Tarif mis a jour',
            text: 'Le prix de l\'acte a ete enregistre avec succes.',
            variant: 'success',
        );
    }
};
?>

<section class="w-full space-y-6">
    <flux:heading class="sr-only">Grille tarifaire</flux:heading>

    <x-header_default
        title="Grille tarifaire"
        subtitle="Gestion centralisee des prix des actes medicaux"
        :navigations="[
            ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Facturation', 'link' => 'facturation', 'icon' => 'document-text'],
            ['label' => 'Tarifs', 'icon' => 'document-currency-dollar'],
        ]"
    >
        <x-slot:actions>
            <x-button href="{{ route('facturation.index') }}" wire:navigate>Factures clinique</x-button>
        </x-slot>
    </x-header_default>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Actes</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">{{ $this->stats['total'] }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">References tarifaires</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">Actifs</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->stats['active'] }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Disponibles en consultation</p>
        </div>

        <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">Inactifs</p>
            <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">{{ $this->stats['inactive'] }}</p>
            <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">Hors grille active</p>
        </div>

        <div class="rounded-3xl border border-violet-200 bg-violet-50/80 p-5 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700 dark:text-violet-300">Prix moyen</p>
            <p class="mt-3 text-3xl font-black text-violet-900 dark:text-violet-100">{{ number_format($this->stats['avg_price'], 2, ',', ' ') }} $</p>
            <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">Moyenne catalogue</p>
        </div>

        <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-5 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-300">Mises a jour</p>
            <p class="mt-3 text-3xl font-black text-blue-900 dark:text-blue-100">{{ $this->stats['updates_month'] }}</p>
            <p class="mt-1 text-xs text-blue-700/80 dark:text-blue-300/80">Revisions ce mois</p>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white">Catalogue des actes</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Cliquez sur <span class="font-semibold">Modifier</span> pour ajuster le prix, le code ou le statut d'un acte.
            </p>
        </div>
        @unless ($this->canManageTariffs())
            <flux:badge color="amber" inset>Lecture seule</flux:badge>
        @endunless
    </div>

    <livewire:tariff-table :can-edit="$this->canManageTariffs()" />

    <flux:modal wire:model.self="showEditModal" class="max-w-2xl">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Modifier l'acte tarifaire</flux:heading>
                <flux:subheading>
                    Ajustez le prix catalogue. Les nouvelles consultations utiliseront cette valeur.
                </flux:subheading>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Nom de l'acte</label>
                    <input type="text" wire:model="edit_name"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @error('edit_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Code</label>
                    <input type="text" wire:model="edit_code"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @error('edit_code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Prix (USD)</label>
                    <input type="number" min="0" step="0.01" wire:model="edit_price"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                    @error('edit_price')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <input type="checkbox" wire:model="edit_active" class="rounded border-slate-300" />
                        Acte actif dans la grille tarifaire
                    </label>
                </div>
            </div>

            @if ($this->priceHistory->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Historique des prix</p>
                    <div class="mt-3 space-y-2">
                        @foreach ($this->priceHistory as $history)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-slate-600 dark:text-slate-300">
                                    {{ optional($history->changed_at)->format('d/m/Y H:i') }}
                                    · {{ $history->changedBy?->name ?? 'Systeme' }}
                                </span>
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    {{ number_format((float) $history->old_price, 2, ',', ' ') }} $
                                    →
                                    {{ number_format((float) $history->new_price, 2, ',', ' ') }} $
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelEdit">Annuler</flux:button>
                <flux:button variant="primary" wire:click="saveEdit">Enregistrer</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
