<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\facturation\CashRegisterEvent;
use App\Models\facturation\Facturation;
use App\Models\facturation\Payment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComptabiliteMetricsService
{
    /**
     * @return array{start: CarbonInterface, end: CarbonInterface, label: string}
     */
    public function resolvePeriod(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        return match ($period) {
            'today' => [
                'start' => today()->startOfDay(),
                'end' => today()->endOfDay(),
                'label' => "Aujourd'hui",
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
                'label' => 'Cette semaine',
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
                'label' => 'Cette année',
            ],
            'custom' => [
                'start' => Carbon::parse($customStart ?: today())->startOfDay(),
                'end' => Carbon::parse($customEnd ?: today())->endOfDay(),
                'label' => 'Période personnalisée',
            ],
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
                'label' => 'Ce mois',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $period = 'month', ?string $customStart = null, ?string $customEnd = null, ?int $hopitalId = null): array
    {
        $hopitalId ??= current_hopital_id();
        $range = $this->resolvePeriod($period, $customStart, $customEnd);

        return Cache::remember(
            $this->cacheKey($hopitalId, $period, $range['start'], $range['end']),
            now()->addMinutes(2),
            fn () => $this->buildDashboard($hopitalId, $range['start'], $range['end'], $range['label']),
        );
    }

    public function forgetDashboardCache(string $period = 'month', ?string $customStart = null, ?string $customEnd = null, ?int $hopitalId = null): void
    {
        $hopitalId ??= current_hopital_id();
        $range = $this->resolvePeriod($period, $customStart, $customEnd);

        Cache::forget($this->cacheKey($hopitalId, $period, $range['start'], $range['end']));
    }

    private function cacheKey(int $hopitalId, string $period, CarbonInterface $start, CarbonInterface $end): string
    {
        return sprintf('comptabilite.v1.%s.%s.%s.%s', $hopitalId, $period, $start->toDateString(), $end->toDateString());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboard(int $hopitalId, CarbonInterface $start, CarbonInterface $end, string $periodLabel): array
    {
        $today = today();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $billing = $this->billingTotals($hopitalId, $start, $end);
        $kpis = $this->kpis($hopitalId, $start, $end, $today, $monthStart, $monthEnd, $billing);
        $collections = $this->collectionAnalytics($hopitalId, $start, $end);
        $assurances = $this->assuranceAnalytics($hopitalId, $start, $end);
        $cash = $this->cashAnalytics($hopitalId, $start, $end);
        $invoices = $this->invoiceAnalytics($hopitalId, $start, $end);
        $alerts = $this->alerts($kpis, $invoices, $cash);
        $insights = $this->insights($kpis, $billing, $collections, $assurances, $alerts);

        return [
            'periodLabel' => $periodLabel,
            'generated_at' => now()->toIso8601String(),
            'kpis' => $kpis,
            'billing' => $billing,
            'collections' => $collections,
            'assurances' => $assurances,
            'cash' => $cash,
            'invoices' => $invoices,
            'alerts' => $alerts,
            'insights' => $insights,
        ];
    }

    /**
     * @return array{gross: float, patient: float, assurance: float, actes_count: int, consultations_count: int}
     */
    private function billingTotals(int $hopitalId, CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = $this->acteConsultationCoverageQuery($hopitalId, $start, $end)
            ->select([
                'acte_consultation.montant',
                'acte_consultation.prise_en_charge',
                'categorisations.pourcentage as default_coverage',
                'consultations.id as consultation_id',
            ])
            ->get();

        $gross = 0.0;
        $patient = 0.0;
        $assurance = 0.0;
        $consultationIds = [];

        foreach ($rows as $row) {
            $amount = (float) ($row->montant ?? 0);
            $coverage = (float) ($row->prise_en_charge ?? 0);
            if ($coverage <= 0) {
                $coverage = (float) ($row->default_coverage ?? 0);
            }

            $coverage = max(0, min(100, $coverage));
            $assurancePart = round($amount * $coverage / 100, 2);
            $patientPart = max(0, round($amount - $assurancePart, 2));

            $gross += $amount;
            $patient += $patientPart;
            $assurance += $assurancePart;
            $consultationIds[$row->consultation_id] = true;
        }

        return [
            'gross' => round($gross, 2),
            'patient' => round($patient, 2),
            'assurance' => round($assurance, 2),
            'actes_count' => $rows->count(),
            'consultations_count' => count($consultationIds),
        ];
    }

    /**
     * @param  array{gross: float, patient: float, assurance: float, actes_count: int, consultations_count: int}  $billing
     * @return array<string, mixed>
     */
    private function kpis(int $hopitalId, CarbonInterface $start, CarbonInterface $end, CarbonInterface $today, CarbonInterface $monthStart, CarbonInterface $monthEnd, array $billing): array
    {
        $paymentsToday = $this->paymentsSum($hopitalId, $today->copy()->startOfDay(), $today->copy()->endOfDay());
        $paymentsMonth = $this->paymentsSum($hopitalId, $monthStart, $monthEnd);
        $paymentsPeriod = $this->paymentsSum($hopitalId, $start, $end);

        $invoiceQuery = Facturation::query()->where('hopital_id', $hopitalId);
        $periodInvoiceQuery = (clone $invoiceQuery)->whereBetween('created_at', [$start, $end]);

        $statusCounts = (clone $periodInvoiceQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $unpaidPatient = max(0, $billing['patient'] - $paymentsPeriod);
        $collectionRate = $billing['patient'] > 0
            ? round(($paymentsPeriod / $billing['patient']) * 100, 1)
            : ($paymentsPeriod > 0 ? 100 : 0);

        return [
            'factures_period' => (clone $periodInvoiceQuery)->count(),
            'factures_today' => (clone $invoiceQuery)->whereDate('created_at', $today)->count(),
            'factures_paid' => (int) ($statusCounts['paye'] ?? 0),
            'factures_partial' => (int) ($statusCounts['partiel'] ?? 0),
            'factures_pending' => (int) ($statusCounts['en_attente'] ?? 0),
            'gross_billed' => $billing['gross'],
            'patient_share' => $billing['patient'],
            'assurance_share' => $billing['assurance'],
            'collected_today' => $paymentsToday,
            'collected_month' => $paymentsMonth,
            'collected_period' => $paymentsPeriod,
            'unpaid_patient' => round($unpaidPatient, 2),
            'unpaid_total' => (float) (clone $invoiceQuery)->sum('due_amount'),
            'collection_rate' => $collectionRate,
            'consultations_billed' => $billing['consultations_count'],
            'actes_billed' => $billing['actes_count'],
            'assurance_partners' => $this->acteConsultationCoverageQuery($hopitalId, $start, $end)
                ->whereNotNull(DB::raw('COALESCE(consultations.assurance_id, projets.assurance_id)'))
                ->distinct(DB::raw('COALESCE(consultations.assurance_id, projets.assurance_id)'))
                ->count(DB::raw('COALESCE(consultations.assurance_id, projets.assurance_id)')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectionAnalytics(int $hopitalId, CarbonInterface $start, CarbonInterface $end): array
    {
        $collectionTrend = $this->dailyPaymentTrend($hopitalId, $start, $end);
        $billingTrend = $this->dailyBillingTrend($hopitalId, $start, $end);

        $paymentModes = Payment::query()
            ->whereNull('voided_at')
            ->whereBetween('paid_at', [$start, $end])
            ->whereHas('facturation', fn ($q) => $q->where('hopital_id', $hopitalId))
            ->selectRaw('payment_mode, SUM(amount) as total')
            ->groupBy('payment_mode')
            ->pluck('total', 'payment_mode');

        $byDepartment = DB::table('payments')
            ->join('facturations', 'facturations.id', '=', 'payments.facturation_id')
            ->join('consultations', 'consultations.id', '=', 'facturations.consultation_id')
            ->join('departements', 'departements.id', '=', 'consultations.departement_id')
            ->whereNull('payments.voided_at')
            ->where('facturations.hopital_id', $hopitalId)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->selectRaw('departements.name as label, SUM(payments.amount) as value')
            ->groupBy('departements.name')
            ->orderByDesc('value')
            ->limit(8)
            ->get();

        $recentPayments = Payment::query()
            ->with(['facturation.dossierPatient', 'creator'])
            ->whereNull('voided_at')
            ->whereBetween('paid_at', [$start, $end])
            ->whereHas('facturation', fn ($q) => $q->where('hopital_id', $hopitalId))
            ->latest('paid_at')
            ->limit(8)
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'mode' => $this->paymentModeLabel((string) $payment->payment_mode),
                'paid_at' => $payment->paid_at?->format('d/m/Y H:i'),
                'patient' => $this->patientLabel($payment->facturation?->dossierPatient),
                'agent' => $payment->creator?->name ?? '—',
            ])
            ->all();

        return [
            'collection_trend' => $collectionTrend,
            'billing_trend' => $billingTrend,
            'payment_modes' => [
                'labels' => $paymentModes->keys()->map(fn ($m) => $this->paymentModeLabel((string) $m))->values()->all(),
                'values' => $paymentModes->values()->map(fn ($v) => (float) $v)->all(),
            ],
            'by_department' => $byDepartment,
            'recent_payments' => $recentPayments,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assuranceAnalytics(int $hopitalId, CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = $this->acteConsultationCoverageQuery($hopitalId, $start, $end)
            ->whereNotNull(DB::raw('COALESCE(consultations.assurance_id, projets.assurance_id)'))
            ->selectRaw('
                assurances.id,
                assurances.name,
                categorisations.name as categorie,
                categorisations.pourcentage as default_coverage,
                COUNT(DISTINCT consultations.dossier_patient_id) as patients_count,
                COUNT(DISTINCT consultations.id) as consultations_count,
                SUM(acte_consultation.montant) as gross_amount
            ')
            ->groupBy('assurances.id', 'assurances.name', 'categorisations.name', 'categorisations.pourcentage')
            ->orderByDesc('gross_amount')
            ->limit(10)
            ->get();

        $ranking = $rows->map(function ($row) use ($hopitalId, $start, $end) {
            $assuranceAmount = $this->assuranceAmountForAssurance((int) $row->id, $hopitalId, $start, $end);

            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'categorie' => (string) ($row->categorie ?? 'N/A'),
                'coverage' => (float) ($row->default_coverage ?? 0),
                'patients' => (int) $row->patients_count,
                'consultations' => (int) $row->consultations_count,
                'gross' => round((float) ($row->gross_amount ?? 0), 2),
                'assurance_amount' => $assuranceAmount,
            ];
        })->values()->all();

        $byAssuranceChart = [
            'labels' => collect($ranking)->pluck('name')->take(6)->all(),
            'values' => collect($ranking)->pluck('assurance_amount')->take(6)->all(),
        ];

        $byCategory = $this->acteConsultationCoverageQuery($hopitalId, $start, $end)
            ->whereNotNull(DB::raw('COALESCE(consultations.assurance_id, projets.assurance_id)'))
            ->selectRaw('COALESCE(categorisations.name, \'Sans catégorie\') as label, SUM(acte_consultation.montant) as value')
            ->groupBy('categorisations.name')
            ->orderByDesc('value')
            ->get();

        return [
            'ranking' => $ranking,
            'by_assurance' => $byAssuranceChart,
            'by_category' => [
                'labels' => $byCategory->pluck('label')->all(),
                'values' => $byCategory->pluck('value')->map(fn ($v) => (float) $v)->all(),
            ],
        ];
    }

    private function assuranceAmountForAssurance(int $assuranceId, int $hopitalId, CarbonInterface $start, CarbonInterface $end): float
    {
        $rows = $this->acteConsultationCoverageQuery($hopitalId, $start, $end)
            ->where(function ($query) use ($assuranceId) {
                $query->where('consultations.assurance_id', $assuranceId)
                    ->orWhere('projets.assurance_id', $assuranceId);
            })
            ->select([
                'acte_consultation.montant',
                'acte_consultation.prise_en_charge',
                'categorisations.pourcentage as default_coverage',
            ])
            ->get();

        $total = 0.0;

        foreach ($rows as $row) {
            $amount = (float) ($row->montant ?? 0);
            $coverage = (float) ($row->prise_en_charge ?? 0);
            if ($coverage <= 0) {
                $coverage = (float) ($row->default_coverage ?? 0);
            }

            $coverage = max(0, min(100, $coverage));
            $total += round($amount * $coverage / 100, 2);
        }

        return round($total, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function cashAnalytics(int $hopitalId, CarbonInterface $start, CarbonInterface $end): array
    {
        $cashIn = (float) CashRegisterEvent::query()
            ->where('event_type', 'cash_in')
            ->whereBetween('performed_at', [$start, $end])
            ->sum('amount');

        $cashOut = (float) CashRegisterEvent::query()
            ->where('event_type', 'cash_out')
            ->whereBetween('performed_at', [$start, $end])
            ->sum('amount');

        $trend = $this->dailyCashTrend($start, $end);

        return [
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'net' => round($cashIn - $cashOut, 2),
            'trend' => $trend,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceAnalytics(int $hopitalId, CarbonInterface $start, CarbonInterface $end): array
    {
        $statusCounts = Facturation::query()
            ->where('hopital_id', $hopitalId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recent = Facturation::query()
            ->with(['dossierPatient', 'consultation.departement', 'consultation.assurance'])
            ->where('hopital_id', $hopitalId)
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Facturation $facturation) => [
                'id' => $facturation->id,
                'reference' => $facturation->consultation?->reference ?? ('#' . $facturation->id),
                'patient' => $this->patientLabel($facturation->dossierPatient ?: $facturation->consultation?->dossierPatient),
                'departement' => $facturation->consultation?->departement?->name ?? '—',
                'assurance' => $facturation->consultation?->assurance?->name ?? 'Paiement direct',
                'total' => (float) $facturation->total_amount,
                'due' => (float) $facturation->due_amount,
                'status' => $this->statusLabel((string) $facturation->status),
                'created_at' => $facturation->created_at?->format('d/m/Y'),
            ])
            ->all();

        $topUnpaid = Facturation::query()
            ->with(['dossierPatient', 'consultation'])
            ->where('hopital_id', $hopitalId)
            ->where('due_amount', '>', 0)
            ->orderByDesc('due_amount')
            ->limit(8)
            ->get()
            ->map(fn (Facturation $facturation) => [
                'id' => $facturation->id,
                'reference' => $facturation->consultation?->reference ?? ('#' . $facturation->id),
                'patient' => $this->patientLabel($facturation->dossierPatient ?: $facturation->consultation?->dossierPatient),
                'due' => (float) $facturation->due_amount,
                'status' => $this->statusLabel((string) $facturation->status),
            ])
            ->all();

        return [
            'status' => [
                'labels' => ['Payées', 'Partielles', 'En attente'],
                'values' => [
                    (int) ($statusCounts['paye'] ?? 0),
                    (int) ($statusCounts['partiel'] ?? 0),
                    (int) ($statusCounts['en_attente'] ?? 0),
                ],
            ],
            'recent' => $recent,
            'top_unpaid' => $topUnpaid,
        ];
    }

    /**
     * @param  array<string, mixed>  $kpis
     * @return array<int, array{level: string, title: string, message: string}>
     */
    private function alerts(array $kpis, array $invoices, array $cash): array
    {
        $alerts = [];

        if ($kpis['unpaid_patient'] > 1000) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Impayés patients élevés',
                'message' => number_format($kpis['unpaid_patient'], 2, ',', ' ') . ' USD restent à recouvrer sur la période.',
            ];
        }

        if ($kpis['factures_pending'] > 10) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Factures en attente',
                'message' => $kpis['factures_pending'] . ' factures patient sont encore en attente de paiement.',
            ];
        }

        if ($kpis['collection_rate'] < 50 && $kpis['patient_share'] > 0) {
            $alerts[] = [
                'level' => 'danger',
                'title' => 'Taux d\'encaissement faible',
                'message' => 'Seulement ' . $kpis['collection_rate'] . '% de la part patient a été encaissée sur la période.',
            ];
        }

        if ($cash['cash_out'] > $cash['cash_in'] && $cash['cash_in'] > 0) {
            $alerts[] = [
                'level' => 'danger',
                'title' => 'Sorties de caisse supérieures',
                'message' => 'Les sorties de caisse dépassent les entrées sur la période sélectionnée.',
            ];
        }

        if (count($invoices['top_unpaid']) > 0 && ($invoices['top_unpaid'][0]['due'] ?? 0) > 500) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Gros impayé détecté',
                'message' => 'La facture ' . $invoices['top_unpaid'][0]['reference'] . ' présente un solde de '
                    . number_format($invoices['top_unpaid'][0]['due'], 2, ',', ' ') . ' USD.',
            ];
        }

        return $alerts;
    }

    /**
     * @return array{summary: string, highlights: array<int, string>}
     */
    private function insights(array $kpis, array $billing, array $collections, array $assurances, array $alerts): array
    {
        $highlights = [];

        if ($billing['assurance'] > 0) {
            $share = $billing['gross'] > 0 ? round(($billing['assurance'] / $billing['gross']) * 100, 1) : 0;
            $highlights[] = "La part assurance représente {$share}% du montant brut facturé.";
        }

        if ($kpis['collection_rate'] >= 80) {
            $highlights[] = 'Bon taux d\'encaissement patient sur la période (' . $kpis['collection_rate'] . '%).';
        }

        if (count($assurances['ranking']) > 0) {
            $top = $assurances['ranking'][0];
            $highlights[] = "L'assurance la plus active est {$top['name']} avec {$top['consultations']} consultations.";
        }

        if (count($alerts) === 0) {
            $highlights[] = 'Aucune alerte critique sur la période analysée.';
        }

        $summary = sprintf(
            '%d factures, %.0f USD facturés dont %.0f USD côté assurance et %.0f USD côté patient. Encaissement période : %.0f USD.',
            $kpis['factures_period'],
            $kpis['gross_billed'],
            $kpis['assurance_share'],
            $kpis['patient_share'],
            $kpis['collected_period'],
        );

        return [
            'summary' => $summary,
            'highlights' => $highlights,
        ];
    }

    private function paymentsSum(int $hopitalId, CarbonInterface $start, CarbonInterface $end): float
    {
        return (float) Payment::query()
            ->whereNull('voided_at')
            ->whereBetween('paid_at', [$start, $end])
            ->whereHas('facturation', fn ($q) => $q->where('hopital_id', $hopitalId))
            ->sum('amount');
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function dailyPaymentTrend(int $hopitalId, CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = Payment::query()
            ->whereNull('voided_at')
            ->whereBetween('paid_at', [$start, $end])
            ->whereHas('facturation', fn ($q) => $q->where('hopital_id', $hopitalId))
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($start, $end, $rows);
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function dailyBillingTrend(int $hopitalId, CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = DB::table('acte_consultation')
            ->join('consultations', 'consultations.id', '=', 'acte_consultation.consultation_id')
            ->where('consultations.hopital_id', $hopitalId)
            ->whereBetween('consultations.created_at', [$start, $end])
            ->selectRaw('DATE(consultations.created_at) as day, SUM(acte_consultation.montant) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($start, $end, $rows);
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function dailyCashTrend(CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = CashRegisterEvent::query()
            ->where('event_type', 'cash_in')
            ->whereBetween('performed_at', [$start, $end])
            ->selectRaw('DATE(performed_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($start, $end, $rows);
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function fillDailySeries(CarbonInterface $start, CarbonInterface $end, Collection $rows): array
    {
        $labels = [];
        $values = [];
        $cursor = $start->copy()->startOfDay();

        while ($cursor <= $end) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $values[] = (float) ($rows[$key] ?? 0);
            $cursor = $cursor->addDay();
        }

        return compact('labels', 'values');
    }

    private function paymentModeLabel(string $mode): string
    {
        return match ($mode) {
            'cash' => 'Espèces',
            'mobile_money' => 'Mobile Money',
            'carte' => 'Carte bancaire',
            'virement' => 'Virement',
            default => 'Autre',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'paye' => 'Payée',
            'partiel' => 'Partielle',
            'en_attente' => 'En attente',
            default => 'À facturer',
        };
    }

    private function patientLabel(mixed $patient): string
    {
        if (! $patient) {
            return 'Patient inconnu';
        }

        return trim(implode(' ', array_filter([
            strtoupper((string) $patient->nom),
            strtoupper((string) $patient->postnom),
            ucfirst((string) $patient->prenom),
        ])));
    }

    private function acteConsultationCoverageQuery(int $hopitalId, CarbonInterface $start, CarbonInterface $end)
    {
        return DB::table('acte_consultation')
            ->join('consultations', 'consultations.id', '=', 'acte_consultation.consultation_id')
            ->leftJoin('projets', 'projets.id', '=', 'consultations.projet_id')
            ->leftJoin('assurances', function ($join) {
                $join->whereRaw('assurances.id = COALESCE(consultations.assurance_id, projets.assurance_id)');
            })
            ->leftJoin('categorisations', 'categorisations.id', '=', 'assurances.categorisation_id')
            ->where('consultations.hopital_id', $hopitalId)
            ->whereBetween('consultations.created_at', [$start, $end]);
    }
}
