<?php

use App\Models\facturation\Payment;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Historique des paiements'), Layout('layouts::app.other.facturation')] class extends Component {
    public ?string $date_start = null;
    public ?string $date_end = null;
    public ?string $payment_mode = null;
    public ?string $agent = null;
    public ?string $invoice = null;
    public ?string $patient = null;

    #[Computed]
    public function rows()
    {
        return Payment::query()
            ->with(['dossierPatient', 'creator', 'audits.performer'])
            ->when($this->date_start, fn($q) => $q->whereDate('paid_at', '>=', $this->date_start))
            ->when($this->date_end, fn($q) => $q->whereDate('paid_at', '<=', $this->date_end))
            ->when($this->payment_mode, fn($q) => $q->where('payment_mode', $this->payment_mode))
            ->when($this->agent, fn($q) => $q->where('created_by', $this->agent))
            ->when($this->invoice, fn($q) => $q->where('facturation_id', $this->invoice))
            ->when($this->patient, fn($q) => $q->where('dossier_patient_id', $this->patient))
            ->latest('paid_at')
            ->limit(150)
            ->get();
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = $this->rows();

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['id', 'facturation_id', 'patient_id', 'amount', 'currency', 'mode', 'paid_at', 'agent', 'voided_at']);

            foreach ($rows as $payment) {
                fputcsv($output, [
                    $payment->id,
                    $payment->facturation_id,
                    $payment->dossier_patient_id,
                    (float) $payment->amount,
                    $payment->currency,
                    $payment->payment_mode,
                    optional($payment->paid_at)->toDateTimeString(),
                    $payment->creator?->name,
                    optional($payment->voided_at)->toDateTimeString(),
                ]);
            }

            fclose($output);
        }, 'historique-paiements-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
};
?>

<div class="space-y-5 p-6">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">Historique des paiements</h1>
        <flux:button wire:click="exportCsv" icon="arrow-down-tray">Exporter CSV</flux:button>
    </div>

    <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
        <x-input type="date" label="Periode debut" wire:model.live="date_start" />
        <x-input type="date" label="Periode fin" wire:model.live="date_end" />
        <x-input label="Mode" placeholder="cash, carte..." wire:model.live.debounce.500ms="payment_mode" />
        <x-input label="Agent (ID)" wire:model.live.debounce.500ms="agent" />
        <x-input label="Facture (ID)" wire:model.live.debounce.500ms="invoice" />
        <x-input label="Patient (ID)" wire:model.live.debounce.500ms="patient" />
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/70">
        <div class="space-y-2">
            @forelse($this->rows as $payment)
                <div class="rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-700">
                    <p class="font-semibold text-slate-900 dark:text-white">
                        Paiement #{{ $payment->id }} - Facture #{{ $payment->facturation_id }} - {{ number_format((float) $payment->amount, 2, ',', ' ') }} {{ $payment->currency }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ optional($payment->paid_at)->format('d/m/Y H:i') }} • Mode: {{ $payment->payment_mode }} • Agent: {{ $payment->creator?->name ?? '-' }}
                    </p>
                    @if($payment->voided_at)
                        <p class="text-xs font-semibold text-red-600 dark:text-red-300">ANNULE</p>
                    @endif
                    @foreach($payment->audits->sortByDesc('performed_at')->take(2) as $audit)
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Audit: {{ $audit->action }} - {{ optional($audit->performed_at)->format('d/m/Y H:i') }}
                            @if(!is_null($audit->old_amount) || !is_null($audit->new_amount))
                                ({{ number_format((float) $audit->old_amount, 2, ',', ' ') }} -> {{ number_format((float) $audit->new_amount, 2, ',', ' ') }})
                            @endif
                            par {{ $audit->performer?->name ?? '-' }}
                        </p>
                    @endforeach
                </div>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400">Aucun paiement trouve.</p>
            @endforelse
        </div>
    </div>
</div>
