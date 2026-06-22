<?php

namespace App\Observers;

use App\Models\Consultation;
use App\Services\ClinicalMessagingService;
use App\Services\DashboardMetricsService;

class ConsultationObserver
{
    public function updated(Consultation $consultation): void
    {
        if ($consultation->wasChanged('is_clore') && $consultation->is_clore) {
            app(ClinicalMessagingService::class)->sendConsultationClosedSummary($consultation);
        }

        $this->forgetDashboardCache($consultation);
    }

    public function saved(Consultation $consultation): void
    {
        $this->forgetDashboardCache($consultation);
    }

    public function deleted(Consultation $consultation): void
    {
        $this->forgetDashboardCache($consultation);
    }

    private function forgetDashboardCache(Consultation $consultation): void
    {
        if (!$consultation->hopital_id) {
            return;
        }

        app(DashboardMetricsService::class)->forgetHopitalCache($consultation->hopital_id);
    }
}
