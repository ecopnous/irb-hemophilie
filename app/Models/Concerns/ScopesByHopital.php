<?php

namespace App\Models\Concerns;

trait ScopesByHopital
{
    /**
     * Filtre les enregistrements de l'hôpital courant (session).
     */
    public function scopeWhereHopitalId($query, ?int $hopitalId)
    {
        if ($hopitalId === null) {
            return $query;
        }

        return $query->where($this->getTable().'.hopital_id', $hopitalId);
    }
}
