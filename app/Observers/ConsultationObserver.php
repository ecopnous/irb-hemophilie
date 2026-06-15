<?php

namespace App\Observers;

use App\Models\Consultation;
use App\Services\DashboardMetricsService;

class ConsultationObserver
{
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
