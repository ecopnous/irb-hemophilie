<?php

namespace App\Services;

use App\Models\prescription\Pharmacie;
use App\Models\prescription\Prescription;
use App\Models\prescription\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacyStockService
{
    public function moveStock(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            $pharmacy = Pharmacie::query()->findOrFail($payload['pharmacie_id']);
            $medicament = $pharmacy->medicaments()->where('medicaments.id', $payload['medicament_id'])->first();

            if (!$medicament) {
                $pharmacy->medicaments()->attach($payload['medicament_id'], ['quantiter' => 0, 'montant' => 0]);
                $medicament = $pharmacy->medicaments()->where('medicaments.id', $payload['medicament_id'])->firstOrFail();
            }

            $before = (int) $medicament->pivot->quantiter;
            $qty = (int) $payload['quantity'];
            if ($qty <= 0) {
                throw ValidationException::withMessages(['quantity' => 'La quantite doit etre superieure a 0.']);
            }

            $after = match ($payload['movement_type']) {
                'in' => $before + $qty,
                'out', 'depreciation' => $before - $qty,
                'adjustment' => $qty,
                default => $before,
            };

            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock insuffisant pour cette sortie.']);
            }

            $pharmacy->medicaments()->updateExistingPivot($payload['medicament_id'], [
                'quantiter' => $after,
                'montant' => $payload['amount'] ?? $medicament->pivot->montant ?? 0,
            ]);

            return StockMovement::query()->create([
                'pharmacie_id' => $payload['pharmacie_id'],
                'medicament_id' => $payload['medicament_id'],
                'prescription_id' => $payload['prescription_id'] ?? null,
                'consultation_id' => $payload['consultation_id'] ?? null,
                'movement_type' => $payload['movement_type'],
                'quantity' => $qty,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference' => $payload['reference'] ?? null,
                'note' => $payload['note'] ?? null,
                'created_by' => $payload['user_id'] ?? null,
            ]);
        });
    }

    public function servePrescription(Prescription $prescription, int $pharmacieId, int $userId): void
    {
        DB::transaction(function () use ($prescription, $pharmacieId, $userId) {
            foreach ($prescription->medicaments as $medicament) {
                $remaining = max(0, (int) $medicament->pivot->nbr - (int) $medicament->pivot->qte_servie);
                if ($remaining <= 0) {
                    continue;
                }

                $this->moveStock([
                    'pharmacie_id' => $pharmacieId,
                    'medicament_id' => $medicament->id,
                    'movement_type' => 'out',
                    'quantity' => $remaining,
                    'prescription_id' => $prescription->id,
                    'consultation_id' => $prescription->consultation_id,
                    'reference' => $prescription->reference,
                    'note' => 'Sortie pour prescription',
                    'user_id' => $userId,
                ]);

                $prescription->medicaments()->updateExistingPivot($medicament->id, [
                    'qte_servie' => (int) $medicament->pivot->qte_servie + $remaining,
                ]);
            }

            $total = $prescription->medicaments()->sum('medicament_prescription.nbr');
            $served = $prescription->medicaments()->sum('medicament_prescription.qte_servie');
            $status = $served <= 0 ? 'draft' : ($served < $total ? 'partial' : 'served');

            $prescription->forceFill([
                'status' => $status,
                'served_at' => now(),
                'served_by' => $userId,
            ])->save();
        });
    }
}
