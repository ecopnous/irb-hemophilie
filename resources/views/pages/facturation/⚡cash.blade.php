<?php

use App\Models\facturation\CashRegisterEvent;
use App\Services\FinanceEventService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Operations de caisse'), Layout('layouts::app.other.facturation')] class extends Component {
    public string $operation_type = 'entree_manuelle';
    public string $event_type = 'cash_in';
    public ?float $amount = null;
    public string $currency = 'USD';
    public ?string $performed_at = null;
    public ?string $reference = null;
    public ?string $note = null;
    public string $search = '';
    public string $eventFilter = '';
    public string $currencyFilter = '';
    public ?string $dateStart = null;
    public ?string $dateEnd = null;

    public function mount(): void
    {
        $this->performed_at = now()->format('Y-m-d\TH:i');
    }

    public function saveOperation(FinanceEventService $service): void
    {
        abort_unless(auth()->user()?->role === 'admin' || auth()->user()?->role === 'caissier', 403);

        $validated = $this->validate([
            'operation_type' => ['required', 'in:ouverture,cloture,entree_manuelle,sortie_manuelle,ajustement'],
            'event_type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'performed_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->createCashOperation($validated + ['user_id' => Auth::id()]);

        $this->reset(['amount', 'reference', 'note']);
        $this->performed_at = now()->format('Y-m-d\TH:i');
    }

    public function operationTypeOptions(): array
    {
        return [['label' => 'Ouverture caisse', 'value' => 'ouverture'], ['label' => 'Cloture caisse', 'value' => 'cloture'], ['label' => 'Entree manuelle', 'value' => 'entree_manuelle'], ['label' => 'Sortie manuelle', 'value' => 'sortie_manuelle'], ['label' => 'Ajustement', 'value' => 'ajustement']];
    }

    public function eventTypeOptions(): array
    {
        return [['label' => 'Tous', 'value' => ''], ['label' => 'Entrees', 'value' => 'cash_in'], ['label' => 'Sorties', 'value' => 'cash_out']];
    }

    public function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'cash_in' => 'Entree',
            'cash_out' => 'Sortie',
            default => ucfirst($eventType),
        };
    }

    public function sourceLabel(string $sourceType): string
    {
        return match ($sourceType) {
            'manual_operation' => 'Operation manuelle',
            'payment' => 'Paiement facture',
            'payment_void' => 'Annulation paiement',
            default => str_replace('_', ' ', $sourceType),
        };
    }

    #[Computed]
    public function events()
    {
        return CashRegisterEvent::query()
            ->with('performer')
            ->when($this->eventFilter !== '', fn($query) => $query->where('event_type', $this->eventFilter))
            ->when($this->currencyFilter !== '', fn($query) => $query->where('currency', strtoupper($this->currencyFilter)))
            ->when($this->dateStart, fn($query) => $query->whereDate('performed_at', '>=', $this->dateStart))
            ->when($this->dateEnd, fn($query) => $query->whereDate('performed_at', '<=', $this->dateEnd))
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('source_type', 'like', $term)->orWhere('note', 'like', $term)->orWhere('currency', 'like', $term)->orWhereHas('performer', fn($performer) => $performer->where('name', 'like', $term));
                });
            })
            ->latest('performed_at')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function todaySummary(): array
    {
        $events = CashRegisterEvent::query()->whereDate('performed_at', today())->get();

        $cashIn = (float) $events->where('event_type', 'cash_in')->sum('amount');
        $cashOut = (float) $events->where('event_type', 'cash_out')->sum('amount');
        $opening = (float) $events->where('source_type', 'manual_operation')->filter(fn($e) => ($e->meta['operation_type'] ?? null) === 'ouverture')->sum('amount');

        return [
            'opening' => $opening,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'current_balance' => $opening + $cashIn - $cashOut,
        ];
    }
};
?>

<div class="space-y-5">
    <section
        class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/70 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Facturation', 'link' => route('facturation.index'), 'icon' => 'banknotes'],
                        ['label' => 'Journaux de caisse', 'icon' => 'bookmark-square'],
                    ]" />
                    <div class="space-y-2">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-emerald-600 dark:text-emerald-300">
                            Journal de caisse
                        </p>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            Operations de caisse & historique global
                        </h1>
                        <p class="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                            Suivi des entrees, sorties, ouvertures, clotures et ajustements de caisse.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-start gap-3 lg:items-end">
                    <span
                        class="inline-flex rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        Aujourd'hui : {{ now()->format('d/m/Y') }}
                    </span>
                    <div class="flex flex-wrap gap-2">
                        <flux:button icon="plus-circle" variant="primary" color="emerald">
                            Nouvelle operation
                        </flux:button>
                        <flux:button icon="printer" variant="ghost">
                            Imprimer rapport
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-4">
        <div
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Solde ouverture</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">
                {{ number_format($this->todaySummary['opening'], 2, ',', ' ') }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Aujourd'hui</p>
        </div>
        <div
            class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">Entrees</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">
                {{ number_format($this->todaySummary['cash_in'], 2, ',', ' ') }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Cash in</p>
        </div>
        <div
            class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Sorties</p>
            <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">
                {{ number_format($this->todaySummary['cash_out'], 2, ',', ' ') }}</p>
            <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">Cash out</p>
        </div>
        <div
            class="rounded-3xl border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">Solde courant</p>
            <p class="mt-3 text-3xl font-black text-sky-900 dark:text-sky-100">
                {{ number_format($this->todaySummary['current_balance'], 2, ',', ' ') }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">Solde estime</p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1fr,1.5fr]">
        <div
            class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="mb-5">
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Nouvelle operation manuelle</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Saisir une entree, sortie, ouverture ou cloture de
                    caisse.</p>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <x-select.styled label="Type operation" wire:model="operation_type" :options="$this->operationTypeOptions()"
                    select="label:label|value:value" />
                <x-select.styled label="Sens" wire:model="event_type" :options="[
                    ['label' => 'Cash In', 'value' => 'cash_in'],
                    ['label' => 'Cash Out', 'value' => 'cash_out'],
                ]"
                    select="label:label|value:value" />
                <x-number step="0.01" label="Montant" wire:model="amount" />
                <x-input label="Devise" wire:model="currency" />
                <x-input type="datetime-local" label="Date operation" wire:model="performed_at" />
                <x-input label="Reference" wire:model="reference" />
            </div>
            <x-textarea class="mt-3" rows="3" label="Note" wire:model="note" />
            <flux:button class="mt-4 w-full" wire:click="saveOperation" variant="primary" color="emerald"
                icon="banknotes">
                Enregistrer operation
            </flux:button>
        </div>

        <div class="space-y-4">
            <div
                class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Filtres du journal</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Affiner les mouvements de caisse consultes.
                    </p>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <x-input wire:model.live.debounce.400ms="search" label="Recherche"
                        placeholder="Source, note, agent..." />
                    <x-select.styled wire:model.live="eventFilter" label="Type" :options="$this->eventTypeOptions()"
                        select="label:label|value:value" />
                    <x-input wire:model.live.debounce.400ms="currencyFilter" label="Devise" placeholder="USD" />
                    <x-input type="date" wire:model.live="dateStart" label="Date debut" />
                    <x-input type="date" wire:model.live="dateEnd" label="Date fin" />
                </div>
            </div>

            <div
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Journal central</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Derniers mouvements enregistres dans la
                        caisse.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                            <tr
                                class="text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                <th class="px-5 py-3">Date</th>
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Source</th>
                                <th class="px-5 py-3">Agent</th>
                                <th class="px-5 py-3 text-right">Entree</th>
                                <th class="px-5 py-3 text-right">Sortie</th>
                                <th class="px-5 py-3">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($this->events as $event)
                                <tr>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-300">
                                        {{ optional($event->performed_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $event->event_type === 'cash_in' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                                            {{ $this->eventLabel($event->event_type) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            {{ $this->sourceLabel($event->source_type) }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">#{{ $event->source_id }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-300">
                                        {{ $event->performer?->name ?? '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-black text-emerald-700 dark:text-emerald-300">
                                        {{ $event->event_type === 'cash_in' ? number_format((float) $event->amount, 2, ',', ' ') . ' ' . $event->currency : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-black text-amber-700 dark:text-amber-300">
                                        {{ $event->event_type === 'cash_out' ? number_format((float) $event->amount, 2, ',', ' ') . ' ' . $event->currency : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $event->note ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"
                                        class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                                        Aucun evenement en caisse.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
