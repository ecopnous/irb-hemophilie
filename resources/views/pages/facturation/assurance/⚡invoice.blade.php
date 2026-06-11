<?php

use App\Models\Configs\Assurance;
use App\Services\AssuranceInvoiceService;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Facture mensuelle assurance'), Layout('layouts::app.other.facturation')] class extends Component {
    public Assurance $assurance;

    public string $periodMonth;

    public ?int $projetId = null;

    public bool $showForfaitModal = false;

    public bool $forfait_actif = false;

    public ?string $prix_patient = null;

    public function mount(int $id): void
    {
        $this->assurance = Assurance::query()
            ->with('categorisation')
            ->findOrFail($id);

        $this->periodMonth = request('month', now()->format('Y-m'));
        $this->forfait_actif = (bool) $this->assurance->forfait_actif;
        $this->prix_patient = $this->assurance->prix_patient !== null
            ? (string) $this->assurance->prix_patient
            : null;
    }

    #[Computed]
    public function period(): array
    {
        $start = Carbon::createFromFormat('Y-m', $this->periodMonth)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->translatedFormat('F Y'),
        ];
    }

    #[Computed]
    public function invoice(): array
    {
        return app(AssuranceInvoiceService::class)->build(
            $this->assurance,
            current_hopital_id(),
            $this->period['start'],
            $this->period['end'],
            $this->projetId,
        );
    }

    #[Computed]
    public function hopital(): array
    {
        return app(AssuranceInvoiceService::class)->hopitalHeader(current_hopital_id());
    }

    #[Computed]
    public function projets()
    {
        return app(AssuranceInvoiceService::class)->availableProjets(
            $this->assurance,
            current_hopital_id(),
        );
    }

    public function openForfaitModal(): void
    {
        $this->forfait_actif = (bool) $this->assurance->forfait_actif;
        $this->prix_patient = $this->assurance->prix_patient !== null
            ? (string) $this->assurance->prix_patient
            : null;
        $this->showForfaitModal = true;
    }

    public function saveForfait(): void
    {
        $validated = $this->validate([
            'forfait_actif' => ['boolean'],
            'prix_patient' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assurance->update([
            'forfait_actif' => $validated['forfait_actif'],
            'prix_patient' => $validated['forfait_actif'] ? ($validated['prix_patient'] ?? 0) : null,
        ]);

        $this->assurance->refresh();
        $this->showForfaitModal = false;
        unset($this->invoice);

        Flux::toast('Forfait assurance mis a jour.', variant: 'success');
    }
};
?>

<section class="w-full space-y-6 print:p-0">
    <flux:heading class="sr-only">Facture mensuelle assurance</flux:heading>

    <div class="print:hidden">
        <x-header_default
            :title="'FACTURE MENSUELLE | ' . strtoupper($assurance->name)"
            :subtitle="$this->period['label']"
            :navigations="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Facturation', 'link' => 'facturation', 'icon' => 'document-text'],
                ['label' => 'Assurances', 'link' => 'facturation/assurance', 'icon' => 'shield-check'],
                ['label' => $assurance->reference, 'link' => 'facturation/assurance/' . $assurance->id, 'icon' => 'document-text'],
                ['label' => 'Facture', 'icon' => 'shield-check'],
            ]"
        >
            <x-slot:actions>
                <x-button href="{{ route('facturation.assurance.show', $assurance->id) }}" wire:navigate>Fiche</x-button>
                <x-button icon="cog-6-tooth" position="left" wire:click="openForfaitModal">Modifier forfait</x-button>
            </x-slot>
        </x-header_default>

        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <label class="text-sm font-semibold text-slate-600 dark:text-slate-300">Periode</label>
            <input type="month" wire:model.live="periodMonth"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />

            <label class="text-sm font-semibold text-slate-600 dark:text-slate-300">Projet</label>
            <select wire:model.live="projetId"
                class="min-w-[220px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="">Tous les projets</option>
                @foreach ($this->projets as $projet)
                    <option value="{{ $projet->id }}">{{ $projet->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-facturation.assurance-invoice-document
        :invoice="$this->invoice"
        :hopital="$this->hopital"
    />

    <flux:modal wire:model.self="showForfaitModal" class="max-w-lg">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Modifier forfait</flux:heading>
                <flux:subheading>Configurer le forfait mensuel par patient pour {{ $assurance->name }}.</flux:subheading>
            </div>

            <label class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <input type="checkbox" wire:model.live="forfait_actif" class="rounded border-slate-300" />
                Activer le forfait mensuel
            </label>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Prix par patient ($)</label>
                <input type="number" min="0" step="0.01" wire:model="prix_patient"
                    @disabled(! $forfait_actif)
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900" />
                @error('prix_patient')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showForfaitModal', false)">Annuler</flux:button>
                <flux:button variant="primary" wire:click="saveForfait">Enregistrer</flux:button>
            </div>
        </div>
    </flux:modal>
</section>

<style>
    @media print {
        body {
            background: #fff !important;
        }

        .print\:hidden {
            display: none !important;
        }
    }
</style>
