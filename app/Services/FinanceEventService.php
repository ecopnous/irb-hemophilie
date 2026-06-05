<?php

namespace App\Services;

use App\Models\facturation\CashOperation;
use App\Models\facturation\CashRegisterEvent;
use App\Models\facturation\Facturation;
use App\Models\facturation\Payment;
use App\Models\facturation\PaymentAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceEventService
{
    public function createManualPayment(Facturation $facturation, array $payload): Payment
    {
        return DB::transaction(function () use ($facturation, $payload) {
            $amount = (float) $payload['amount'];
            $currentDue = (float) $facturation->due_amount;

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le montant doit etre superieur a 0.']);
            }

            if ($amount > $currentDue) {
                throw ValidationException::withMessages(['amount' => 'Le montant depasse le reste a payer.']);
            }

            if (($payload['currency'] ?? $facturation->currency) !== $facturation->currency) {
                throw ValidationException::withMessages(['currency' => 'La devise du paiement doit correspondre a celle de la facture.']);
            }

            $payment = Payment::query()->create([
                'facturation_id' => $facturation->id,
                'consultation_id' => $facturation->consultation_id,
                'dossier_patient_id' => $facturation->dossier_patient_id,
                'acte_id' => $payload['acte_id'] ?? null,
                'amount' => $amount,
                'currency' => $facturation->currency,
                'payment_mode' => $payload['payment_mode'],
                'reference' => $payload['reference'] ?? null,
                'paid_at' => $payload['paid_at'],
                'comment' => $payload['comment'] ?? null,
                'created_by' => $payload['user_id'] ?? null,
                'updated_by' => $payload['user_id'] ?? null,
            ]);

            $newPaid = (float) $facturation->paid_amount + $amount;
            $newDue = max(0, (float) $facturation->total_amount - $newPaid);
            $newStatus = $newPaid <= 0 ? 'en_attente' : ($newDue > 0 ? 'partiel' : 'paye');

            $facturation->forceFill([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'status' => $newStatus,
                'updated_by' => $payload['user_id'] ?? null,
            ])->save();

            CashRegisterEvent::query()->create([
                'source_type' => 'payment',
                'source_id' => $payment->id,
                'event_type' => 'cash_in',
                'amount' => $amount,
                'currency' => $facturation->currency,
                'performed_by' => $payload['user_id'] ?? null,
                'performed_at' => $payload['paid_at'],
                'note' => $payload['comment'] ?? 'Encaissement manuel facture #' . $facturation->id,
                'meta' => [
                    'facturation_id' => $facturation->id,
                    'payment_mode' => $payload['payment_mode'],
                    'reference' => $payload['reference'] ?? null,
                ],
            ]);

            PaymentAudit::query()->create([
                'payment_id' => $payment->id,
                'action' => 'created',
                'new_amount' => $amount,
                'changes' => [
                    'payment_mode' => $payload['payment_mode'],
                    'reference' => $payload['reference'] ?? null,
                ],
                'performed_by' => $payload['user_id'] ?? null,
                'performed_at' => $payload['paid_at'],
                'note' => 'Creation paiement manuel',
            ]);

            return $payment;
        });
    }

    public function correctPaymentAmount(Payment $payment, float $newAmount, ?int $userId = null, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $newAmount, $userId, $note) {
            if ($newAmount <= 0) {
                throw ValidationException::withMessages(['new_amount' => 'Le montant corrige doit etre superieur a 0.']);
            }

            if ($payment->voided_at) {
                throw ValidationException::withMessages(['payment' => 'Impossible de corriger un paiement annule.']);
            }

            $invoice = $payment->facturation()->lockForUpdate()->firstOrFail();
            $oldAmount = (float) $payment->amount;
            $delta = $newAmount - $oldAmount;
            $newPaid = (float) $invoice->paid_amount + $delta;
            $newDue = max(0, (float) $invoice->total_amount - $newPaid);

            if ($newPaid > (float) $invoice->total_amount) {
                throw ValidationException::withMessages(['new_amount' => 'La correction depasse le total de la facture.']);
            }

            $payment->forceFill([
                'amount' => $newAmount,
                'updated_by' => $userId,
            ])->save();

            $invoice->forceFill([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'status' => $newPaid <= 0 ? 'en_attente' : ($newDue > 0 ? 'partiel' : 'paye'),
                'updated_by' => $userId,
            ])->save();

            if ($delta != 0.0) {
                CashRegisterEvent::query()->create([
                    'source_type' => 'payment_correction',
                    'source_id' => $payment->id,
                    'event_type' => $delta > 0 ? 'cash_in' : 'cash_out',
                    'amount' => abs($delta),
                    'currency' => $payment->currency,
                    'performed_by' => $userId,
                    'performed_at' => now(),
                    'note' => $note ?: 'Correction paiement #' . $payment->id,
                    'meta' => [
                        'old_amount' => $oldAmount,
                        'new_amount' => $newAmount,
                        'facturation_id' => $invoice->id,
                    ],
                ]);
            }

            PaymentAudit::query()->create([
                'payment_id' => $payment->id,
                'action' => 'corrected',
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'performed_by' => $userId,
                'performed_at' => now(),
                'note' => $note ?: 'Correction montant paiement',
            ]);

            return $payment->refresh();
        });
    }

    public function createCashOperation(array $payload): CashOperation
    {
        return DB::transaction(function () use ($payload) {
            if ((float) $payload['amount'] <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le montant doit etre superieur a 0.']);
            }

            $operation = CashOperation::query()->create([
                'operation_type' => $payload['operation_type'],
                'event_type' => $payload['event_type'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'performed_at' => $payload['performed_at'],
                'reference' => $payload['reference'] ?? null,
                'note' => $payload['note'] ?? null,
                'created_by' => $payload['user_id'] ?? null,
                'updated_by' => $payload['user_id'] ?? null,
            ]);

            CashRegisterEvent::query()->create([
                'source_type' => 'manual_operation',
                'source_id' => $operation->id,
                'event_type' => $payload['event_type'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'performed_by' => $payload['user_id'] ?? null,
                'performed_at' => $payload['performed_at'],
                'note' => $payload['note'] ?? ('Operation de caisse: ' . $payload['operation_type']),
                'meta' => [
                    'operation_type' => $payload['operation_type'],
                    'reference' => $payload['reference'] ?? null,
                ],
            ]);

            return $operation;
        });
    }
}
