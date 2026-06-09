<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AnalyticsSummaryExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private array $metrics) {}

    public function title(): string
    {
        return 'KPI Analytics';
    }

    public function headings(): array
    {
        return ['Indicateur', 'Valeur'];
    }

    public function array(): array
    {
        $k = $this->metrics['kpis'] ?? [];

        return [
            ['Période', $this->metrics['periodLabel'] ?? ''],
            ['Patients aujourd\'hui', $k['patients_today'] ?? 0],
            ['Patients ce mois', $k['patients_month'] ?? 0],
            ['Hospitalisés', $k['hospitalized'] ?? 0],
            ['Consultations (période)', $k['consultations_total'] ?? 0],
            ['Recettes du jour', $k['revenue_today'] ?? 0],
            ['Recettes du mois', $k['revenue_month'] ?? 0],
            ['Dépenses du mois', $k['expenses_month'] ?? 0],
            ['Bénéfice net', $k['net_profit_month'] ?? 0],
            ['Factures impayées', $k['unpaid_invoices'] ?? 0],
            ['Taux occupation lits', ($k['bed_occupancy_rate'] ?? 0) . '%'],
        ];
    }
}
