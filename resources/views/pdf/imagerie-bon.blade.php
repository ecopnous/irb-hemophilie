<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon d'Imagerie</title>
    <style>
        @@page { margin: 15px 20px 40px 20px; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.3;
        }
        .w-100 { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .vertical-top { vertical-align: top; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .institution-title { font-size: 14px; font-weight: bold; }
        .institution-details { font-size: 10px; color: #333; margin-top: 3px; }
        .qr-placeholder { width: 60px; height: 60px; border: 1px solid #000; text-align: center; line-height: 60px; font-size: 9px; color: #666; display: inline-block; }
        .main-title-banner { background-color: #000; color: #fff; text-align: center; font-weight: bold; font-size: 13px; padding: 5px 0; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; }
        .info-table { width: 100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 12px; }
        .info-table td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; font-size: 9px; }
        .label-title { font-size: 8px; font-weight: bold; color: #333; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .value-text { font-size: 10px; font-weight: bold; color: #000; }
        .section-subtitle { font-size: 11px; font-weight: bold; margin-bottom: 6px; }
        .results-table { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        .results-table th { background-color: #000; color: #fff; font-weight: bold; font-size: 10px; padding: 5px 6px; text-align: left; border: 1px solid #000; }
        .results-table td { border: 1px solid #000; padding: 4px 6px; font-size: 10px; }
        .service-badge { border: 1px solid #000; padding: 1px 4px; font-size: 8px; font-weight: bold; display: inline-block; }
    </style>
</head>
<body>
@php
    $patientName = trim(implode(' ', array_filter([
        strtoupper((string) ($patient?->nom)),
        strtoupper((string) ($patient?->postnom)),
        ucfirst((string) ($patient?->prenom)),
    ])));
    $gender = ($patient?->genre === 'F') ? 'Feminin' : (($patient?->genre === 'M') ? 'Masculin' : '-');
@endphp

<table class="header-table">
    <tr>
        <td class="vertical-top" style="width: 80%;">
            <div class="institution-title">INST. DE RECH. BIOMEDICALE 1-HEALTH (CEFA)</div>
            <div class="institution-details">
                Adresse de l'etablissement<br>
                Ville<br>
                Email : info@irb.cd<br>
                Telephone : +243998457997; +243 813 053 469
            </div>
        </td>
        <td class="vertical-top text-right" style="width: 20%;">
            <div class="qr-placeholder">QR CODE</div>
        </td>
    </tr>
</table>

<div class="main-title-banner">Bon d'Imagerie</div>

<table class="info-table">
    <tr>
        <td style="width: 33.33%;">
            <span class="label-title">Patient</span>
            <div class="value-text">{{ $patientName !== '' ? $patientName : 'PATIENT INCONNU' }}</div>
        </td>
        <td style="width: 33.33%;">
            <span class="label-title">N° Consultation</span>
            <div class="value-text">{{ $consultation->reference ?: '-' }}</div>
        </td>
        <td style="width: 33.33%;">
            <span class="label-title">N° Dossier</span>
            <div class="value-text">{{ $patient?->nin ?: ($patient?->ins ?: '-') }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <span class="label-title">Genre / Age</span>
            <div class="value-text">{{ $gender }} / {{ $patient?->age ?: '-' }}</div>
        </td>
        <td>
            <span class="label-title">Date Consultation</span>
            <div class="value-text">{{ $consultation->created_at?->format('d/m/Y H:i') ?: '-' }}</div>
        </td>
        <td>
            <span class="label-title">Departement</span>
            <div class="value-text">{{ $consultation->departement?->name ?: '-' }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <span class="label-title">Prescripteur</span>
            <div class="value-text">{{ strtoupper((string) ($consultation->user?->name ?: 'NON RENSEIGNE')) }}</div>
        </td>
        <td colspan="2">
            <span class="label-title">Renseignement</span>
            <div class="value-text" style="font-weight: normal; color: #555;">
                {{ $imagerie?->renseignement ?: 'Non renseigne' }}
            </div>
        </td>
    </tr>
</table>

<div class="section-subtitle">Examens individuels ({{ $examens->count() }})</div>

<table class="results-table">
    <thead>
    <tr>
        <th class="text-center" style="width: 5%;">#</th>
        <th style="width: 25%;">Analyse</th>
        <th style="width: 20%;">Clinique</th>
        <th style="width: 20%;">Protocole</th>
        <th style="width: 20%;">Conclusion</th>
        <th class="text-center" style="width: 10%;">Service</th>
    </tr>
    </thead>
    <tbody>
    @forelse($examens as $exam)
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td style="font-weight: bold;">{{ strtoupper((string) $exam->name) }}</td>
            <td>{{ data_get($exam, 'pivot.clinique', '') }}</td>
            <td>{{ data_get($exam, 'pivot.protocole', '') }}</td>
            <td>{{ data_get($exam, 'pivot.cloture', '') }}</td>
            <td class="text-center">
                @if($exam->service?->name)
                    <div class="service-badge">{{ strtoupper((string) $exam->service->name) }}</div>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td class="text-center">1</td>
            <td style="font-weight: bold;">AUCUN EXAMEN D'IMAGERIE</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    @endforelse
    </tbody>
</table>

</body>
</html>
