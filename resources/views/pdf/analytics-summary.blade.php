<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Analytics</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>Tableau de bord Analytics — {{ $hopital }}</h1>
    <p class="meta">Généré le {{ $generatedAt }} — {{ $metrics['periodLabel'] ?? '' }}</p>

    <table>
        <thead>
            <tr><th>Indicateur</th><th>Valeur</th></tr>
        </thead>
        <tbody>
            @foreach ([
                'Patients aujourd\'hui' => $metrics['kpis']['patients_today'] ?? 0,
                'Patients ce mois' => $metrics['kpis']['patients_month'] ?? 0,
                'Hospitalisés' => $metrics['kpis']['hospitalized'] ?? 0,
                'Consultations' => $metrics['kpis']['consultations_total'] ?? 0,
                'Recettes du mois' => number_format($metrics['kpis']['revenue_month'] ?? 0, 0, ',', ' ') . ' USD',
                'Factures impayées' => number_format($metrics['kpis']['unpaid_invoices'] ?? 0, 0, ',', ' ') . ' USD',
                'Occupation lits' => ($metrics['kpis']['bed_occupancy_rate'] ?? 0) . '%',
            ] as $label => $value)
                <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
