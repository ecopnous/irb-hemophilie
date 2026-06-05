<?php

use App\Models\hospitalisation\Hospitalisation;
use App\Services\HospitalFinanceService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Facturation hospitalisation'), Layout('layouts::app.other.hospital')] class extends Component {
    public ?int $hospitalisation_id = null;
    public ?float $amount = null;
    public string $payment_mode = 'cash';
    public ?string $reference = null;
    public ?string $paid_at = null;
    public ?string $comment = null;

    public function mount(): void
    {
        $this->paid_at = now()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function hospitalisations()
    {
        return Hospitalisation::query()
            ->with(['dossierPatient', 'service', 'chambre', 'payments'])
            ->whereHopitalId(current_hopital_id())
            ->latest('date_entree')
            ->get();
    }

    public function pay(HospitalFinanceService $service): void
    {
        $validated = $this->validate([
            'hospitalisation_id' => ['required', 'integer', 'exists:hospitalisations,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['required', 'in:cash,mobile_money,carte,virement,autre'],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $hospitalisation = Hospitalisation::query()
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($validated['hospitalisation_id']);

        $service->pay($hospitalisation, $validated + ['user_id' => auth()->id()]);
        $this->reset(['hospitalisation_id', 'amount', 'reference', 'comment']);
        $this->payment_mode = 'cash';
        $this->paid_at = now()->format('Y-m-d\TH:i');
    }
};
?>

<section class="w-full space-y-6 px-4 py-5 sm:px-6 lg:px-8">
    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <h2 class="text-xl font-black text-slate-900 dark:text-white">Encaissement hospitalisation</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <x-select.native wire:model="hospitalisation_id" label="Hospitalisation *"
                :options="$this->hospitalisations->map(fn($h) => ['label' => ($h->dossierPatient?->full_name ?: 'Patient') . ' - #' . $h->id, 'value' => $h->id])->values()->all()" />
            <x-input wire:model="amount" type="number" step="0.01" label="Montant *" />
            <x-select.native wire:model="payment_mode" label="Mode *"
                :options="[['label' => 'Cash', 'value' => 'cash'], ['label' => 'Mobile money', 'value' => 'mobile_money'], ['label' => 'Carte', 'value' => 'carte'], ['label' => 'Virement', 'value' => 'virement'], ['label' => 'Autre', 'value' => 'autre']]" />
            <x-input wire:model="reference" label="Reference" />
            <x-input wire:model="paid_at" type="datetime-local" label="Date paiement *" />
            <x-input wire:model="comment" label="Commentaire" />
        </div>
        <div class="mt-4 flex justify-end">
            <flux:button variant="primary" color="emerald" wire:click="pay">Enregistrer paiement</flux:button>
        </div>
    </div>

    <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
        <h2 class="text-xl font-black text-slate-900 dark:text-white">Suivi de facturation hospitalisation</h2>
        <div class="mt-4 space-y-3">
            @forelse ($this->hospitalisations as $h)
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white">{{ $h->dossierPatient?->full_name ?: 'Patient' }} - #{{ $h->id }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $h->service?->name ?: '-' }} · {{ $h->chambre?->name ?: '-' }} · Entree: {{ optional($h->date_entree)->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <div class="text-right text-sm">
                            <p>Total: <span class="font-semibold">{{ number_format((float) $h->total_amount, 2, ',', ' ') }} {{ $h->currency }}</span></p>
                            <p>Paye: <span class="font-semibold text-emerald-600">{{ number_format((float) $h->paid_amount, 2, ',', ' ') }}</span></p>
                            <p>Reste: <span class="font-semibold text-amber-600">{{ number_format((float) $h->due_amount, 2, ',', ' ') }}</span></p>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400">Aucune hospitalisation enregistree.</p>
            @endforelse
        </div>
    </div>
</section>
