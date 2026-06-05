<?php

namespace App\Services;

use App\Models\LaboratoryConsumable;
use App\Models\LaboratoryStockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LaboratoryStockService
{
    public function moveStock(array $payload): LaboratoryStockMovement
    {
        return DB::transaction(function () use ($payload) {
            $consumable = LaboratoryConsumable::query()
                ->whereHopitalId(current_hopital_id())
                ->lockForUpdate()
                ->findOrFail($payload['laboratory_consumable_id']);

            $quantity = (int) $payload['quantity'];
            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'La quantite doit etre superieure a 0.',
                ]);
            }

            $before = (int) $consumable->current_stock;
            $after = match ($payload['movement_type']) {
                'in' => $before + $quantity,
                'out', 'loss', 'expired', 'transfer' => $before - $quantity,
                'adjustment' => $quantity,
                default => $before,
            };

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock insuffisant pour cette sortie.',
                ]);
            }

            $consumable->update([
                'current_stock' => $after,
            ]);

            return LaboratoryStockMovement::query()->create([
                'laboratory_consumable_id' => $consumable->id,
                'movement_type' => $payload['movement_type'],
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference' => $payload['reference'] ?? null,
                'lot_number' => $payload['lot_number'] ?? null,
                'expiration_date' => $payload['expiration_date'] ?? null,
                'destination' => $payload['destination'] ?? null,
                'reason' => $payload['reason'] ?? null,
                'created_by' => $payload['user_id'] ?? null,
            ]);
        });
    }
}
