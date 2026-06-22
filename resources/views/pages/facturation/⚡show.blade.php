<?php

use App\Models\facturation\Facturation;
use App\Models\facturation\CashRegisterEvent;
use App\Models\facturation\Payment;
use App\Services\ConsultationBillingService;
use App\Services\FinanceEventService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail facture')] class extends Component {
    public Facturation $facturation;
    public ?float $payment_amount = null;
    public string $payment_mode = 'cash';
    public ?string $payment_reference = null;
    public ?string $payment_date = null;
    public ?string $payment_comment = null;
    public ?int $edit_payment_id = null;
    public ?float $edit_payment_amount = null;
    public ?string $edit_payment_note = null;

    public function mount(int $id): void
    {
        $this->facturation = Facturation::query()
            ->with([
                'dossierPatient',
                'consultation.dossierPatient',
                'consultation.departement',
                'consultation.projet.assurance.categorisation',
                'consultation.assurance.categorisation',
                'consultation.user',
                'consultation.actes.departement',
                'consultation.actes.service',
            ])
            ->whereHas('consultation', fn ($query) => $query->whereHopitalId(current_hopital_id()))
            ->findOrFail($id);

        $this->syncInvoiceTotals();
        $this->payment_date = now()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function actes(): Collection
    {
        return $this->facturation->consultation?->actes ?? collect();
    }

    #[Computed]
    public function assuranceCoverageRate(): float
    {
        $consultation = $this->facturation->consultation;

        return $consultation
            ? app(ConsultationBillingService::class)->defaultCoverageRate($consultation)
            : 0.0;
    }

    #[Computed]
    public function assuranceCategoryName(): string
    {
        $consultation = $this->facturation->consultation;

        return $consultation
            ? app(ConsultationBillingService::class)->coverageCategoryName($consultation)
            : 'N/A';
    }

    #[Computed]
    public function coverageLabel(): string
    {
        $consultation = $this->facturation->consultation;

        return $consultation
            ? app(ConsultationBillingService::class)->coverageLabel($consultation)
            : 'Paiement direct';
    }

    #[Computed]
    public function assuranceName(): string
    {
        $consultation = $this->facturation->consultation;

        return $consultation
            ? app(ConsultationBillingService::class)->assuranceName($consultation)
            : 'Paiement direct';
    }

    #[Computed]
    public function billingLines(): Collection
    {
        $consultation = $this->facturation->consultation;

        if (! $consultation) {
            return collect();
        }

        return app(ConsultationBillingService::class)->billingLines($consultation);
    }

    #[Computed]
    public function grossAmount(): float
    {
        return (float) $this->billingLines->sum('amount');
    }

    #[Computed]
    public function totalAmount(): float
    {
        return (float) $this->billingLines->sum('patient_amount');
    }

    #[Computed]
    public function assuranceAmount(): float
    {
        return (float) $this->billingLines->sum('assurance_amount');
    }

    #[Computed]
    public function paidAmount(): float
    {
        return (float) $this->facturation->payments()->whereNull('voided_at')->sum('amount');
    }

    #[Computed]
    public function remainingAmount(): float
    {
        return max(0, $this->totalAmount - $this->paidAmount);
    }

    #[Computed]
    public function invoiceStatus(): string
    {
        return match (true) {
            $this->totalAmount <= 0 => 'a_facturer',
            $this->paidAmount <= 0 => 'en_attente',
            $this->paidAmount < $this->totalAmount => 'partiel',
            default => 'paye',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->invoiceStatus) {
            'a_facturer' => 'A facturer',
            'en_attente' => 'En attente',
            'partiel' => 'Paiement partiel',
            'paye' => 'Paye',
            default => 'Indetermine',
        };
    }

    public function statusClasses(): string
    {
        return match ($this->invoiceStatus) {
            'a_facturer' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            'en_attente' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            'partiel' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'paye' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    public function patientName(): string
    {
        $patient = $this->facturation->dossierPatient ?: $this->facturation->consultation?->dossierPatient;

        if (!$patient) {
            return 'Patient inconnu';
        }

        return trim(implode(' ', array_filter([
            strtoupper((string) $patient->nom),
            strtoupper((string) $patient->postnom),
            ucfirst((string) $patient->prenom),
        ])));
    }

    public function patientIdentifier(): string
    {
        $patient = $this->facturation->dossierPatient ?: $this->facturation->consultation?->dossierPatient;

        return (string) ($patient?->nin ?: $patient?->ins ?: '-');
    }

    public function money(float $value): string
    {
        return number_format($value, 2, ',', ' ');
    }

    #[Computed]
    public function payments(): Collection
    {
        return $this->facturation->payments()
            ->with(['creator', 'voider', 'audits'])
            ->latest('paid_at')
            ->limit(20)
            ->get();
    }

    public function savePayment(FinanceEventService $service): void
    {
        $validated = $this->validate([
            'payment_amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['required', 'in:cash,mobile_money,carte,virement,autre'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'payment_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->createManualPayment($this->facturation, [
            'amount' => (float) $validated['payment_amount'],
            'payment_mode' => $validated['payment_mode'],
            'reference' => $validated['payment_reference'],
            'paid_at' => $validated['payment_date'],
            'comment' => $validated['payment_comment'],
            'currency' => $this->facturation->currency ?: 'USD',
            'user_id' => Auth::id(),
        ]);

        $this->facturation->refresh();
        unset($this->payments);

        $this->payment_amount = null;
        $this->payment_reference = null;
        $this->payment_comment = null;
        $this->payment_date = now()->format('Y-m-d\TH:i');

        Flux::toast(heading: 'Paiement enregistre', text: 'Le paiement manuel a ete valide et la caisse a ete mise a jour.', variant: 'success');
    }

    public function voidPayment(int $paymentId): void
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'admin', 403);

        $payment = $this->facturation->payments()->whereKey($paymentId)->whereNull('voided_at')->firstOrFail();

        $payment->forceFill([
            'voided_at' => now(),
            'void_reason' => 'Annulation manuelle',
            'voided_by' => $user->id,
            'updated_by' => $user->id,
        ])->save();

        CashRegisterEvent::query()->create([
            'source_type' => 'payment_void',
            'source_id' => $payment->id,
            'event_type' => 'cash_out',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'performed_by' => $user->id,
            'performed_at' => now(),
            'note' => 'Annulation paiement #' . $payment->id,
            'meta' => ['facturation_id' => $this->facturation->id],
        ]);

        $payment->audits()->create([
            'action' => 'voided',
            'old_amount' => (float) $payment->amount,
            'new_amount' => 0,
            'performed_by' => $user->id,
            'performed_at' => now(),
            'note' => 'Annulation paiement',
        ]);

        $this->facturation->forceFill([
            'paid_amount' => max(0, (float) $this->facturation->paid_amount - (float) $payment->amount),
        ]);
        $this->facturation->due_amount = max(0, (float) $this->facturation->total_amount - (float) $this->facturation->paid_amount);
        $this->facturation->status = (float) $this->facturation->paid_amount <= 0 ? 'en_attente' : ((float) $this->facturation->due_amount > 0 ? 'partiel' : 'paye');
        $this->facturation->save();

        unset($this->payments);
        Flux::toast(heading: 'Paiement annule', text: 'L operation a ete annulee avec journalisation d audit.', variant: 'warning');
    }

    public function beginEditPayment(int $paymentId): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $payment = $this->facturation->payments()->whereKey($paymentId)->whereNull('voided_at')->firstOrFail();
        $this->edit_payment_id = $payment->id;
        $this->edit_payment_amount = (float) $payment->amount;
        $this->edit_payment_note = null;
    }

    public function savePaymentCorrection(FinanceEventService $service): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $this->validate([
            'edit_payment_id' => ['required', 'integer', 'exists:payments,id'],
            'edit_payment_amount' => ['required', 'numeric', 'gt:0'],
            'edit_payment_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = Payment::query()
            ->where('facturation_id', $this->facturation->id)
            ->findOrFail($validated['edit_payment_id']);

        $service->correctPaymentAmount(
            $payment,
            (float) $validated['edit_payment_amount'],
            Auth::id(),
            $validated['edit_payment_note']
        );

        $this->facturation->refresh();
        unset($this->payments);
        $this->reset(['edit_payment_id', 'edit_payment_amount', 'edit_payment_note']);

        Flux::toast(heading: 'Paiement corrige', text: 'La correction a ete enregistree avec audit.', variant: 'success');
    }

    protected function syncInvoiceTotals(): void
    {
        $total = $this->totalAmount;
        $paid = (float) $this->facturation->payments()->whereNull('voided_at')->sum('amount');

        $this->facturation->forceFill([
            'total_amount' => $total,
            'paid_amount' => $paid,
            'due_amount' => max(0, $total - $paid),
            'status' => $paid <= 0 ? 'en_attente' : (($total - $paid) > 0 ? 'partiel' : 'paye'),
            'currency' => $this->facturation->currency ?: 'USD',
            'updated_by' => Auth::id(),
        ])->save();

        $this->facturation->refresh();
    }
};
?>

<div class="space-y-6">
    <section class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/70 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">
        <div class="flex flex-col gap-6 px-6 py-6 md:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <x-breadcrumbs :items="[
                        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                        ['label' => 'Facturation', 'link' => route('facturation.index'), 'icon' => 'banknotes'],
                        ['label' => 'Facture #' . $facturation->id, 'icon' => 'receipt-percent'],
                    ]" />

                    <div class="space-y-2">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-emerald-600 dark:text-emerald-300">
                            Facturation
                        </p>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            Facture #{{ $facturation->id }}
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Detail de la consultation facturee, des actes enregistres et de l'etat de paiement.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-start gap-3 lg:items-end">
                    <span class="inline-flex rounded-full px-3 py-1.5 text-sm font-bold {{ $this->statusClasses() }}">
                        {{ $this->statusLabel() }}
                    </span>
                    <flux:button href="{{ route('facturation.pdf', $facturation->id) }}" target="_blank" variant="primary" color="indigo" icon="printer">
                        Imprimer PDF
                    </flux:button>
                    <div class="text-sm text-slate-500 dark:text-slate-400 lg:text-right">
                        <p>Reference consultation: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $facturation->consultation?->reference ?? '-' }}</span></p>
                        <p>Date facture: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ optional($facturation->created_at)->format('d/m/Y H:i') }}</span></p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-white/70 bg-white/85 px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Patient</p>
                    <p class="mt-2 text-lg font-black text-slate-900 dark:text-white">{{ $this->patientName() }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $this->patientIdentifier() }}</p>
                </div>

                <div class="rounded-2xl border border-white/70 bg-white/85 px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Departement</p>
                    <p class="mt-2 text-lg font-black text-slate-900 dark:text-white">{{ $facturation->consultation?->departement?->name ?? '-' }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $facturation->consultation?->mois ?? '-' }}</p>
                </div>

                <div class="rounded-2xl border border-white/70 bg-white/85 px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Prise en charge</p>
                    <p class="mt-2 text-lg font-black text-slate-900 dark:text-white">{{ $this->coverageLabel() }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        @if ($this->assuranceName() !== 'Paiement direct')
                            {{ $this->assuranceName() }} —
                        @endif
                        {{ $this->assuranceCategoryName }} @if ($this->assuranceCoverageRate > 0)({{ number_format($this->assuranceCoverageRate, 0, ',', ' ') }}%)@endif
                    </p>
                </div>

                <div class="rounded-2xl border border-white/70 bg-white/85 px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Medecin</p>
                    <p class="mt-2 text-lg font-black text-slate-900 dark:text-white">{{ $facturation->consultation?->user?->name ?? 'Non assigne' }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $facturation->consultation?->type ?? '-' }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Montant brut</p>
            <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $this->money($this->grossAmount) }}</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Part assurance</p>
            <p class="mt-3 text-3xl font-black text-emerald-900 dark:text-emerald-100">{{ $this->money($this->assuranceAmount) }}</p>
        </div>

        <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">Reste a payer</p>
            <p class="mt-3 text-3xl font-black text-amber-900 dark:text-amber-100">{{ $this->money($this->remainingAmount) }}</p>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.5fr,1fr]">
        <section class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Actes factures</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Liste detaillee des actes, categorie et prise en charge.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900/70">
                        <tr class="text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            <th class="px-5 py-3">Acte</th>
                            <th class="px-5 py-3">Service</th>
                            <th class="px-5 py-3">Ref</th>
                            <th class="px-5 py-3">Categorie</th>
                            <th class="px-5 py-3 text-right">Brut</th>
                            <th class="px-5 py-3 text-right">Assurance</th>
                            <th class="px-5 py-3 text-right">Patient</th>
                            <th class="px-5 py-3 text-center">Paiement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($this->billingLines as $line)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $line['acte']->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $line['acte']->departement?->name ?? 'Sans departement' }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ $line['acte']->service?->name ?? '-' }}</td>
                                <td class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ $line['acte']->pivot->ref ?? '-' }}</td>
                                <td class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ $this->assuranceCategoryName }} ({{ number_format((float) $line['coverage'], 0, ',', ' ') }}%)</td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-900 dark:text-white">{{ $this->money((float) $line['amount']) }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->money((float) $line['assurance_amount']) }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-900 dark:text-white">{{ $this->money((float) $line['patient_amount']) }}</td>
                                <td class="px-5 py-4 text-center">
                                    @if ($line['acte']->pivot->payer ?? false)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Paye</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">En attente</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                                    Aucun acte n'est encore rattache a cette facture.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-6">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Resume financier</h2>
                <div class="mt-5 space-y-4">
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-900/70">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Total brut</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $this->money($this->grossAmount) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3 dark:bg-emerald-500/10">
                        <span class="text-sm text-emerald-700 dark:text-emerald-300">Part assurance</span>
                        <span class="font-bold text-emerald-900 dark:text-emerald-100">{{ $this->money($this->assuranceAmount) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-amber-50 px-4 py-3 dark:bg-amber-500/10">
                        <span class="text-sm text-amber-700 dark:text-amber-300">Reste a payer</span>
                        <span class="font-bold text-amber-900 dark:text-amber-100">{{ $this->money($this->remainingAmount) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Informations complementaires</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Type de consultation</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ ucfirst((string) ($facturation->consultation?->type ?? '-')) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Periode</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $facturation->consultation?->mois ?? '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Projet</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $facturation->consultation?->projet?->name ?? 'Aucun' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Prise en charge</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">
                            @if ($facturation->consultation?->projet)
                                {{ $facturation->consultation->projet->name }}
                                @if ($this->assuranceName() !== 'Paiement direct')
                                    <span class="block text-xs font-normal text-slate-500 dark:text-slate-400">
                                        {{ $this->assuranceName() }} — {{ $this->assuranceCategoryName }} ({{ number_format($this->assuranceCoverageRate, 0, ',', ' ') }}%)
                                    </span>
                                @endif
                            @else
                                {{ $this->assuranceName() }}
                                @if ($this->assuranceCoverageRate > 0)
                                    — {{ $this->assuranceCategoryName }} ({{ number_format($this->assuranceCoverageRate, 0, ',', ' ') }}%)
                                @endif
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-[1.75rem] border border-sky-200 bg-sky-50/80 p-5 shadow-sm dark:border-sky-500/20 dark:bg-sky-500/10">
                <p class="text-sm font-semibold text-sky-900 dark:text-sky-100">Encaissement manuel</p>

                <div class="mt-3 grid gap-3">
                    <x-number step="0.01" label="Montant" wire:model="payment_amount" />
                    <x-select.styled label="Mode de paiement" wire:model="payment_mode" :options="[
                        ['label' => 'Cash', 'value' => 'cash'],
                        ['label' => 'Mobile money', 'value' => 'mobile_money'],
                        ['label' => 'Carte', 'value' => 'carte'],
                        ['label' => 'Virement', 'value' => 'virement'],
                        ['label' => 'Autre', 'value' => 'autre'],
                    ]"
                        select="label:label|value:value" />
                    <x-input label="Reference (optionnelle)" wire:model="payment_reference" />
                    <x-input type="datetime-local" label="Date de paiement" wire:model="payment_date" />
                    <x-textarea label="Commentaire" wire:model="payment_comment" rows="2" />
                    <flux:button wire:click="savePayment" variant="primary" color="indigo" icon="banknotes">
                        Enregistrer le paiement
                    </flux:button>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                <h2 class="text-lg font-black text-slate-900 dark:text-white">Historique des paiements</h2>
                <div class="mt-4 space-y-3">
                    @forelse($this->payments as $payment)
                        <div class="rounded-2xl border border-slate-200 p-3 text-sm dark:border-slate-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ $this->money((float) $payment->amount) }} {{ $payment->currency }} - {{ strtoupper($payment->payment_mode) }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ optional($payment->paid_at)->format('d/m/Y H:i') }} • Agent: {{ $payment->creator?->name ?? '-' }}
                                    </p>
                                    @if($payment->reference)
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Ref: {{ $payment->reference }}</p>
                                    @endif
                                    @if($payment->voided_at)
                                        <p class="text-xs font-semibold text-red-600 dark:text-red-300">
                                            Annule le {{ optional($payment->voided_at)->format('d/m/Y H:i') }} par {{ $payment->voider?->name ?? '-' }}
                                        </p>
                                    @endif
                                </div>
                                @if(!$payment->voided_at && (auth()->user()?->role === 'admin'))
                                    <div class="flex gap-2">
                                        <flux:button size="xs" wire:click="beginEditPayment({{ $payment->id }})" variant="ghost">
                                            Corriger
                                        </flux:button>
                                        <flux:button size="xs" wire:click="voidPayment({{ $payment->id }})" variant="danger">
                                            Annuler
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                            @if($payment->audits->isNotEmpty())
                                <div class="mt-2 border-t border-slate-200 pt-2 dark:border-slate-700">
                                    @foreach($payment->audits->sortByDesc('performed_at')->take(3) as $audit)
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                            {{ optional($audit->performed_at)->format('d/m/Y H:i') }} • {{ $audit->action }}
                                            @if(!is_null($audit->old_amount) || !is_null($audit->new_amount))
                                                • {{ number_format((float) $audit->old_amount, 2, ',', ' ') }} -> {{ number_format((float) $audit->new_amount, 2, ',', ' ') }}
                                            @endif
                                        </p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">Aucun paiement enregistre pour cette facture.</p>
                    @endforelse
                </div>
            </div>

            @if($edit_payment_id)
                <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                        Correction paiement #{{ $edit_payment_id }}
                    </p>
                    <div class="mt-3 space-y-3">
                        <x-number step="0.01" label="Nouveau montant" wire:model="edit_payment_amount" />
                        <x-textarea label="Motif correction" rows="2" wire:model="edit_payment_note" />
                        <flux:button wire:click="savePaymentCorrection" variant="primary" color="emerald">Valider correction</flux:button>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
