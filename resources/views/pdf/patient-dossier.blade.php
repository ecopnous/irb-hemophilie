<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Dossier patient</title>
    <style>
        @page {
            /* On définit une marge globale pour le document */
            margin: 20px 22px 50px 22px;
        }

        body {
            /* Utilisation d'Helvetica, la plus stable sur dompdf */
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #111827;
            background-color: #ffffff;
        }

        /* Utilitaires de structure */
        .w-100 {
            width: 100%;
        }

        .vertical-top {
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .line {
            border-bottom: 1px solid #6b7280;
            margin-top: 6px;
            margin-bottom: 6px;
            font-size: 1px;
            line-height: 1px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand {
            color: #0a66c2;
            font-weight: bold;
            font-size: 11px;
        }

        .small-muted {
            color: #4b5563;
            font-size: 9px;
        }

        /* Remplacement du float par un alignement de tableau */
        .qr-box {
            width: 55px;
            height: 55px;
            border: 1px solid #374151;
            text-align: center;
            line-height: 55px;
            font-size: 8px;
            color: #6b7280;
            display: inline-block;
        }

        .section-title {
            margin-top: 10px;
            margin-bottom: 4px;
            font-size: 14px;
            /* Réduit légèrement pour éviter les sauts de page surprises */
            color: #1f2937;
            font-weight: bold;
            text-transform: uppercase;
        }

        .blue-bar {
            background-color: #2893d1;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            padding: 3px 6px;
        }

        .box {
            border: 1px solid #374151;
            padding: 6px;
            background-color: #ffffff;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #4b5563;
        }

        .grid-table td,
        .grid-table th {
            border: 1px solid #4b5563;
            padding: 4px 6px;
            font-size: 9px;
            vertical-align: middle;
        }

        .cell-head {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #111827;
        }

        /* Footer optimisé pour la répétition dompdf */
        .footer {
            position: fixed;
            bottom: -35px;
            /* Positionné dans la marge inférieure du @page */
            left: 0px;
            right: 0px;
            height: 30px;
            font-size: 8px;
            color: #374151;
            border-top: 1px solid #dddddd;
            padding-top: 4px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            font-size: 8px;
            color: #374151;
            vertical-align: top;
        }
    </style>
</head>

<body>
    @php
        $age = $patient->date_naissance ? $patient->date_naissance->diff(now()) : null;
        $ageText = $age ? $age->y . ' ans et ' . $age->m . ' mois' : '-';
        $lastVisit = $latestConsultation?->created_at ? $latestConsultation->created_at->format('d/m/Y') : '-';
        $fullName = trim(
            implode(
                ' ',
                array_filter([
                    strtoupper((string) $patient->nom),
                    strtoupper((string) $patient->postnom),
                    ucfirst((string) $patient->prenom),
                ]),
            ),
        );
    @endphp

    <!-- En-tête sécurisé sans float -->
    <table class="header-table">
        <tr>
            <td class="vertical-top" style="width: 75%;">
                <div class="brand">ISENGA ROIS GRADI</div>
                <div style="margin-top: 3px;"><strong>Age:</strong> {{ $ageText }} (né le
                    {{ optional($patient->date_naissance)->format('d/m/Y') ?: '-' }})</div>
                <div><strong>N°:</strong> {{ $patient->nin ?: '-' }} | <strong>OD:</strong> {{ $patient->ins ?: '-' }}
                </div>
                <div><strong>Téléphone:</strong> {{ $patient->telephone ?: '-' }}</div>
                <div><strong>Adresse:</strong> {{ $patient->avenue ? 'Av. ' . $patient->avenue : '-' }},
                    {{ $patient->quartier ? $patient->quartier : '-' }}, {{ $patient->commune?->name ?: '-' }},
                    {{ $patient->ville?->name ?: '-' }}</div>
            </td>
            <td class="vertical-top text-right" style="width: 25%;">
                <div class="qr-box">QR</div>
                <div class="small-muted" style="margin-top: 5px;">Scanner pour partager</div>
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="section-title">Identite de Gradi</div>
    <table class="grid-table">
        <tr>
            <td class="cell-head" style="width: 25%;">Type de Dossier</td>
            <td style="width: 25%;">Patient standard</td>
            <td class="cell-head" style="width: 25%;">Points Santé</td>
            <td style="width: 25%;">0,00 PS</td>
        </tr>
        <tr>
            <td class="cell-head">Total Consultations</td>
            <td>{{ $consultations->count() }}</td>
            <td class="cell-head">Dernière Visite</td>
            <td>{{ $lastVisit }}</td>
        </tr>
        <tr>
            <td class="cell-head">Médecin Traitant</td>
            <td colspan="3">{{ strtoupper((string) ($latestConsultation?->user?->name ?: 'NON DEFINI')) }}</td>
        </tr>
    </table>

    <div class="section-title">Historique des Consultations ({{ $consultations->count() }} Consultation)</div>
    <div class="box">
        <table class="grid-table">
            <tr>
                <td class="cell-head" style="width: 25%;">Date</td>
                <td style="width: 25%;">{{ $latestConsultation?->created_at?->format('d/m/Y à H:i') ?: '-' }}</td>
                <td class="cell-head" style="width: 25%;">Référence</td>
                <td style="width: 25%;">{{ $latestConsultation?->reference ?: '-' }}</td>
            </tr>
            <tr>
                <td class="cell-head">Médecin</td>
                <td>{{ strtoupper((string) ($latestConsultation?->user?->name ?: '-')) }}</td>
                <td class="cell-head">Département</td>
                <td>{{ ucfirst((string) ($latestConsultation?->departement?->name ?: '-')) }}</td>
            </tr>
        </table>

        <div class="blue-bar" style="margin-top:8px;">Signes Vitaux</div>
        <table class="grid-table">
            <tr>
                <td style="width: 33.33%;"><strong>Poids:</strong>
                    {{ $latestConsultation?->poids !== null ? $latestConsultation->poids . ' kg' : '-' }}</td>
                <td style="width: 33.33%;"><strong>Température:</strong>
                    {{ $latestConsultation?->temperature !== null ? $latestConsultation->temperature . ' °C' : '-' }}
                </td>
                <td style="width: 33.33%;"><strong>TA:</strong>
                    {{ $latestConsultation?->systolite !== null && $latestConsultation?->diastolique !== null ? $latestConsultation->systolite . '/' . $latestConsultation->diastolique : '-' }}
                </td>
            </tr>
            <tr>
                <td><strong>FC:</strong>
                    {{ $latestConsultation?->frequence_cardiaque !== null ? $latestConsultation->frequence_cardiaque . ' bpm' : '-' }}
                </td>
                <td><strong>FR:</strong>
                    {{ $latestConsultation?->frequence_respiratoire !== null ? $latestConsultation->frequence_respiratoire . ' c/min' : '-' }}
                </td>
                <td><strong>SpO2:</strong>
                    {{ $latestConsultation?->saturation_oxygene !== null ? $latestConsultation->saturation_oxygene . ' %' : '-' }}
                </td>
            </tr>
            <tr>
                <td><strong>Taille:</strong>
                    {{ $latestConsultation?->taille !== null ? $latestConsultation->taille . ' cm' : '-' }}</td>
                <td><strong>Glycémie:</strong>
                    {{ $latestConsultation?->glycemie !== null ? $latestConsultation->glycemie : '-' }}</td>
                <td><strong>IMC:</strong> -</td>
            </tr>
        </table>

        <div class="blue-bar" style="margin-top:8px;">Examens Complémentaires Demandés</div>
        <table class="grid-table">
            <tr>
                <td class="cell-head" style="width: 120px;">Examens de laboratoire</td>
                <td>{{ $examensDemandes->isNotEmpty() ? $examensDemandes->implode(', ') : 'Aucun examen enregistré' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Pied de page structurellement sécurisé pour dompdf -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="width: 50%;">
                    <span style="color:#0a66c2; font-weight:bold;">ISENGA ROIS GRADI</span> | C.I-DX-01/2 ---<br>
                    <span style="font-style: italic;">*Toute reproduction est interdite sans autorisation
                        préalable.*</span>
                </td>
                <td class="text-right" style="width: 50%;">
                    INST. DE RECH. BIOMEDICALE 1 HEALTH (C)FAI<br>
                    C/ Mont Ngaliema | Tél : +243998467935 / +243 813 053 483 | Page 1
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
