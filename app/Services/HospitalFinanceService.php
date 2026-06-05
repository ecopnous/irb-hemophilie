<?php

namespace App\Services;

use App\Models\facturation\CashRegisterEvent;
use App\Models\hospitalisation\Hospitalisation;
use App\Models\hospitalisation\HospitalisationPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HospitalFinanceService
{
    public function pay(Hospitalisation $hospitalisation, array $payload): HospitalisationPayment
    {
        return DB::transaction(function () use ($hospitalisation, $payload) {
            $amount = (float) $payload['amount'];
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le montant doit etre superieur a 0.']);
            }

            if ($amount > (float) $hospitalisation->due_amount) {
                throw ValidationException::withMessages(['amount' => 'Le montant depasse le reste a payer.']);
            }

            $payment = HospitalisationPayment::query()->create([
                'hospitalisation_id' => $hospitalisation->id,
                'amount' => $amount,
                'currency' => $hospitalisation->currency ?: 'USD',
                'payment_mode' => $payload['payment_mode'],
                'reference' => $payload['reference'] ?? null,
                'paid_at' => $payload['paid_at'],
                'comment' => $payload['comment'] ?? null,
                'created_by' => $payload['user_id'] ?? null,
                'updated_by' => $payload['user_id'] ?? null,
            ]);

            $hospitalisation->forceFill([
                'paid_amount' => (float) $hospitalisation->paid_amount + $amount,
                'due_amount' => max(0, (float) $hospitalisation->total_amount - ((float) $hospitalisation->paid_amount + $amount)),
                'updated_by' => $payload['user_id'] ?? null,
            ]);
            $hospitalisation->payer = ((float) $hospitalisation->due_amount <= 0);
            $hospitalisation->save();

            CashRegisterEvent::query()->create([
                'source_type' => 'hospitalisation_payment',
                'source_id' => $payment->id,
                'event_type' => 'cash_in',
                'amount' => $amount,
                'currency' => $hospitalisation->currency ?: 'USD',
                'performed_by' => $payload['user_id'] ?? null,
                'performed_at' => $payload['paid_at'],
                'note' => $payload['comment'] ?? ('Paiement hospitalisation #' . $hospitalisation->id),
                'meta' => [
                    'hospitalisation_id' => $hospitalisation->id,
                    'payment_mode' => $payload['payment_mode'],
                ],
            ]);

            return $payment;
        });
    }
}
