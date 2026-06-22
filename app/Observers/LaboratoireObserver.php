<?php

namespace App\Observers;

use App\Models\Laboratoire;
use App\Services\ClinicalMessagingService;

class LaboratoireObserver
{
    public function updated(Laboratoire $laboratoire): void
    {
        if (! $laboratoire->wasChanged('statut')) {
            return;
        }

        if ($laboratoire->statut !== 'terminé') {
            return;
        }

        app(ClinicalMessagingService::class)->sendLabResultsValidated($laboratoire);
    }
}
