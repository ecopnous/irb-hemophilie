<?php

namespace App\Observers;

use App\Models\Imagerie;
use App\Services\ClinicalMessagingService;

class ImagerieObserver
{
    public function created(Imagerie $imagerie): void
    {
        if ($imagerie->statut === 'terminé') {
            app(ClinicalMessagingService::class)->sendImagingResultsValidated($imagerie);
        }
    }

    public function updated(Imagerie $imagerie): void
    {
        if (! $imagerie->wasChanged('statut')) {
            return;
        }

        if ($imagerie->statut !== 'terminé') {
            return;
        }

        app(ClinicalMessagingService::class)->sendImagingResultsValidated($imagerie);
    }
}
