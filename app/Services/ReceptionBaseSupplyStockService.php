<?php

namespace App\Services;

use App\Models\ReceptionBaseSupply;
use App\Models\ReceptionBaseSupplyMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReceptionBaseSupplyStockService
{
    public function applyMovement(
        ReceptionBaseSupply $supply,
        string $movementType,
        int $quantity,
        ?string $reference = null,
        ?string $reason = null,
    ): ReceptionBaseSupplyMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La quantite doit etre superieure a zero.');
        }

        if (! in_array($movementType, ['entree', 'sortie', 'ajustement'], true)) {
            throw new InvalidArgumentException('Type de mouvement invalide.');
        }

        return DB::transaction(function () use ($supply, $movementType, $quantity, $reference, $reason) {
            $supply->refresh();
            $before = (int) $supply->current_stock;

            $after = match ($movementType) {
                'entree' => $before + $quantity,
                'sortie' => max(0, $before - $quantity),
                'ajustement' => $quantity,
            };

            if ($movementType === 'sortie' && $quantity > $before) {
                throw new InvalidArgumentException('Stock insuffisant pour cette sortie.');
            }

            $movement = ReceptionBaseSupplyMovement::query()->create([
                'reception_base_supply_id' => $supply->id,
                'hopital_id' => $supply->hopital_id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference' => $reference,
                'reason' => $reason,
                'created_by' => Auth::id(),
            ]);

            $supply->update([
                'current_stock' => $after,
                'updated_by' => Auth::id(),
            ]);

            return $movement;
        });
    }
}
