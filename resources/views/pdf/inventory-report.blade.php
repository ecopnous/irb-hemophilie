<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Inventaire</title>
    <style>
        @@page { margin: 16px 20px 24px 20px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #111; }
        .header { margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .subtitle { font-size: 10px; color: #555; }
        .stats { width: 100%; border-collapse: collapse; margin: 10px 0 12px 0; }
        .stats td { border: 1px solid #000; padding: 6px; }
        .label { font-size: 9px; color: #444; text-transform: uppercase; font-weight: bold; }
        .value { font-size: 12px; font-weight: bold; margin-top: 2px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { border: 1px solid #000; background: #000; color: #fff; font-size: 9px; padding: 5px; text-align: left; }
        .table td { border: 1px solid #000; padding: 4px 5px; font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
@php
    $money = fn(float $value): string => number_format($value, 2, ',', ' ');
@endphp

<div class="header">
    <div class="title">Rapport Inventaire des Equipements</div>
    <div class="subtitle">Date d'edition : {{ now()->format('d/m/Y H:i') }}</div>
</div>

<table class="stats">
    <tr>
        <td>
            <div class="label">Actifs</div>
            <div class="value">{{ number_format($stats['count']) }}</div>
        </td>
        <td>
            <div class="label">Valeur acquisition</div>
            <div class="value">{{ $money($stats['acquisition']) }}</div>
        </td>
        <td>
            <div class="label">Depreciation cumulee</div>
            <div class="value">{{ $money($stats['depreciation']) }}</div>
        </td>
        <td>
            <div class="label">Valeur nette comptable</div>
            <div class="value">{{ $money($stats['net']) }}</div>
        </td>
    </tr>
</table>

<table class="table">
    <thead>
    <tr>
        <th style="width: 10%;">N° inventaire</th>
        <th style="width: 14%;">Marque / Modele</th>
        <th style="width: 12%;">Reference / Serie</th>
        <th style="width: 12%;">Categorie</th>
        <th style="width: 12%;">Emplacement</th>
        <th style="width: 10%;">Departement</th>
        <th style="width: 10%;">Service</th>
        <th style="width: 8%;" class="text-right">Acquisition</th>
        <th style="width: 8%;" class="text-right">Depreciation</th>
        <th style="width: 8%;" class="text-right">VNC</th>
        <th style="width: 6%;" class="text-center">Etat</th>
    </tr>
    </thead>
    <tbody>
    @forelse($assets as $asset)
        <tr>
            <td>{{ $asset->inventory_number }}</td>
            <td>{{ trim(($asset->marque ?: '-') . ' ' . ($asset->modele ?: '-')) }}</td>
            <td>Ref: {{ $asset->reference ?: '-' }}<br>SN: {{ $asset->serial_number ?: '-' }}</td>
            <td>{{ $asset->category?->name ?: '-' }}</td>
            <td>{{ $asset->location?->name ?: '-' }}</td>
            <td>{{ $asset->location?->departement?->name ?: '-' }}</td>
            <td>{{ $asset->location?->service?->name ?: '-' }}</td>
            <td class="text-right">{{ $money((float) $asset->acquisition_cost) }}</td>
            <td class="text-right">{{ $money($asset->accumulatedDepreciationAmount()) }}</td>
            <td class="text-right">{{ $money($asset->netBookValue()) }}</td>
            <td class="text-center">{{ str_replace('_', ' ', $asset->status) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="11" class="text-center">Aucun equipement enregistre.</td>
        </tr>
    @endforelse
    </tbody>
</table>

</body>
</html>
