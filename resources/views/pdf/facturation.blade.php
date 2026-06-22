<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Facture INTIA</title>
    <style>
        @@page {
            margin: 20px 30px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333333;
            background-color: #ffffff;
            font-size: 12px;
            line-height: 1.4;
        }

        .invoice-container {
            width: 100%;
        }

        .w-100 {
            width: 100%;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .vertical-top {
            vertical-align: top;
        }

        .logo-placeholder {
            width: 180px;
            height: 80px;
            background-color: #311b57;
            color: #ffffff;
            text-align: center;
            line-height: 80px;
            font-weight: bold;
            font-size: 24px;
        }

        .invoice-title {
            border: 1px solid #cccccc;
            padding: 8px 30px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            display: inline-block;
        }

        .invoice-details {
            font-size: 11px;
            color: #555555;
        }

        .address-table {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .company-name,
        .client-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #111111;
        }

        .address-text {
            color: #555555;
            line-height: 1.4;
        }

        .website-link {
            color: #0066cc;
            text-decoration: none;
        }

        .items-table {
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .items-table th {
            border-bottom: 2px solid #cc0000;
            padding: 6px 4px;
            font-weight: bold;
            color: #444444;
            font-size: 11px;
        }

        .items-table td {
            padding: 8px 4px;
        }

        .group-title {
            font-weight: bold;
            padding: 12px 4px 4px 4px !important;
            border-bottom: 1px solid #eeeeee;
        }

        .item-name {
            font-weight: bold;
            color: #111111;
        }

        .item-desc {
            font-size: 10px;
            color: #666666;
        }

        .tva-details {
            font-size: 9px;
            color: #777777;
            display: block;
        }

        .separator {
            border-bottom: 1px solid #cccccc;
            margin-top: 15px;
            margin-bottom: 25px;
        }

        .bottom-table {
            margin-top: 20px;
        }

        .bank-info h3 {
            font-size: 13px;
            margin-bottom: 10px;
            color: #333333;
            border-bottom: 1px solid #eeeeee;
            padding-bottom: 4px;
            width: 80%;
        }

        .bank-table td {
            padding: 3px 0;
            font-size: 11px;
            color: #555555;
        }

        .fin-row td {
            padding: 5px;
            border-bottom: 1px solid #eeeeee;
            font-size: 11px;
        }

        .fin-row.bold td {
            font-weight: bold;
            color: #000000;
        }

        .fin-row.gray-bg td {
            background-color: #fafafa;
        }

        .fin-row.sub-info td {
            font-size: 10px;
            color: #666666;
            border-bottom: none;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .payment-info-text {
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
        }

        .footer-legal {
            margin-top: 18px;
            border-top: 1px solid #dddddd;
            padding-top: 10px;
            font-size: 9px;
            color: #777777;
            text-align: center;
        }

        .created-with {
            background-color: #ef4444;
            color: #ffffff;
            padding: 2px 6px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $money = fn(float $value): string => number_format($value, 2, ',', ' ') .
            ' ' .
            ($facturation->currency ?: 'USD');
        $invoiceRef = 'F' . now()->format('Y') . '-' . str_pad((string) $facturation->id, 5, '0', STR_PAD_LEFT);
        $clientRef = $patient?->nin ?: ($patient?->ins ?: 'N/A');
        $hospitalName = $hopital['name'] ?? (current_hopital_nom() ?? 'Hopital');
        $hospitalAddress = trim(
            implode(
                ', ',
                array_filter([
                    isset($hopital['avenue']) ? 'Av. ' . $hopital['avenue'] : null,
                    isset($hopital['quartier']) ? 'Q. ' . $hopital['quartier'] : null,
                    isset($hopital['numero']) ? 'N ' . $hopital['numero'] : null,
                ]),
            ),
        );
        $patientName = trim(
            implode(
                ' ',
                array_filter([
                    strtoupper((string) $patient?->nom),
                    strtoupper((string) $patient?->postnom),
                    ucfirst((string) $patient?->prenom),
                ]),
            ),
        );
    @endphp

    <div class="invoice-container">
        <table class="w-100">
            <tr>
                <td class="vertical-top">
                    <div class="logo-placeholder">{{ strtoupper(mb_substr($hospitalName, 0, 6)) }}</div>
                </td>
                <td class="text-right vertical-top">
                    <div class="invoice-title">FACTURE</div>
                    <div class="invoice-details">
                        <strong>Reference :</strong> {{ $invoiceRef }}<br>
                        Version : 1.0<br>
                        <strong>Date de facturation :</strong>
                        {{ optional($facturation->created_at)->format('d/m/Y') }}<br>
                        Reference client : {{ $clientRef }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="w-100 address-table" style="table-layout: fixed;">
            <tr>
                <td class="vertical-top" style="width: 50%;">
                    <div class="company-name">{{ $hospitalName }}</div>
                    <div class="address-text">
                        {{ $hospitalAddress !== '' ? $hospitalAddress : 'Adresse non renseignee' }}<br>
                        RDC<br><br>
                        Tel. : -<br>
                        contact@hopital.local<br>
                        <span class="website-link">www.hopital.local</span>
                    </div>
                </td>
                <td class="vertical-top" style="width: 50%; padding-left: 40px;">
                    <div class="client-name">{{ $patientName !== '' ? $patientName : 'Patient inconnu' }}</div>
                    <div class="address-text">
                        {{ $patient?->avenue ? 'Av. ' . $patient->avenue : 'Adresse non renseignee' }}<br>
                        {{ $patient?->quartier ? 'Q. ' . $patient->quartier : '' }}
                        {{ $patient?->num_habitation ? 'N ' . $patient->num_habitation : '' }}<br>
                        RDC<br><br>
                        Tel. : {{ $patient?->telephone ?: '-' }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="w-100 items-table">
            <thead>
                <tr>
                    <th class="text-left" style="width: 45%;">Description</th>
                    <th class="text-right" style="width: 10%;">Brut</th>
                    <th class="text-right" style="width: 10%;">Categorie</th>
                    <th class="text-right" style="width: 10%;">Assurance</th>
                    <th class="text-right" style="width: 10%;">Patient</th>
                    <th class="text-right" style="width: 10%;">TVA</th>
                    <th class="text-right" style="width: 10%;">Total TTC</th>
                </tr>
            </thead>
            <tbody>
                @forelse($billingLines as $line)
                    @php
                        $amount = (float) ($line['amount'] ?? 0);
                        $linePatient = (float) ($line['patient_amount'] ?? 0);
                        $lineAssurance = (float) ($line['assurance_amount'] ?? 0);
                        $lineCoverage = (float) ($line['coverage'] ?? 0);
                        $lineTva = $linePatient * $tvaRate;
                        $lineTtc = $linePatient + $lineTva;
                    @endphp
                    <tr>
                        <td class="vertical-top">
                            <div class="item-name">{{ $line['acte']->name }}</div>
                            <div class="item-desc">
                                {{ $line['acte']->departement?->name ?? 'Departement non defini' }}
                                @if ($line['acte']->service?->name)
                                    - {{ $line['acte']->service->name }}
                                @endif
                            </div>
                        </td>
                        <td class="text-right vertical-top">{{ $money($amount) }}</td>
                        <td class="text-right vertical-top">{{ $categoryName }} ({{ number_format($lineCoverage, 0, ',', ' ') }}%)</td>
                        <td class="text-right vertical-top">{{ $money($lineAssurance) }}</td>
                        <td class="text-right vertical-top">{{ $money($linePatient) }}</td>
                        <td class="text-right vertical-top">{{ number_format($tvaRate * 100, 0, ',', ' ') }} %<span
                                class="tva-details">{{ $money($lineTva) }}</span></td>
                        <td class="text-right vertical-top">{{ $money($lineTtc) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-right">Aucun acte facture pour cette consultation.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="w-100" style="margin-bottom: 20px;">
            <tr>
                <td class="text-right" style="font-size: 12px; color: #333333;">
                    <strong>Total brut :</strong> {{ $money($grossAmount) }} &nbsp;&nbsp;&nbsp;
                    <strong>Part assurance :</strong> {{ $money($assuranceAmount) }} &nbsp;&nbsp;&nbsp;
                    <strong>Net patient :</strong> {{ $money($totalHt) }}
                </td>
            </tr>
        </table>

        <div class="separator"></div>

        <table class="w-100 bottom-table" style="table-layout: fixed;">
            <tr>
                <td class="vertical-top" style="width: 45%;">
                    <div class="bank-info">
                        <h3>Informations Bancaires</h3>
                        <table class="w-100 bank-table">
                            <tr>
                                <td style="width: 60px; font-weight: bold;">Banque</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold;">RIB</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold;">IBAN</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold;">BIC</td>
                                <td>-</td>
                            </tr>
                        </table>
                    </div>
                </td>

                <td class="vertical-top" style="width: 55%;">
                    <table class="w-100" style="border-collapse: collapse;">
                        <tr class="fin-row gray-bg">
                            <td><strong>Projet</strong></td>
                            <td class="text-right">{{ $consultation?->projet?->name ?? 'Aucun' }}</td>
                        </tr>
                        @if (!empty($assuranceName) && $assuranceName !== 'Paiement direct')
                            <tr class="fin-row gray-bg">
                                <td><strong>Assurance</strong></td>
                                <td class="text-right">{{ $assuranceName }}</td>
                            </tr>
                            <tr class="fin-row gray-bg">
                                <td><strong>Prise en charge</strong></td>
                                <td class="text-right">{{ $categoryName }} ({{ number_format((float) ($coverageRate ?? 0), 0, ',', ' ') }}%)</td>
                            </tr>
                        @endif
                        <tr class="fin-row gray-bg">
                            <td>Total HT</td>
                            <td class="text-right">{{ $money($totalHt) }}</td>
                        </tr>
                        <tr class="fin-row gray-bg">
                            <td>Total TTC</td>
                            <td class="text-right">{{ $money($totalTtc) }}</td>
                        </tr>

                        <tr class="fin-row bold">
                            <td style="padding-top: 10px;">Acompte TTC de</td>
                            <td class="text-right" style="padding-top: 10px;">{{ $money($paidAmount) }}</td>
                        </tr>
                        <tr class="fin-row sub-info">
                            <td style="padding-left: 15px;">Facture d'acompte</td>
                            <td class="text-right">
                                {{ optional($facturation->created_at)->format('d/m/Y') }}<br>{{ $invoiceRef }}</td>
                        </tr>

                        <tr class="fin-row bold">
                            <td style="padding-top: 10px;">Total HT</td>
                            <td class="text-right" style="padding-top: 10px;">{{ $money($totalHt) }}</td>
                        </tr>
                        <tr class="fin-row">
                            <td>TVA {{ number_format($tvaRate * 100, 0, ',', ' ') }} %</td>
                            <td class="text-right">{{ $money($tvaAmount) }}</td>
                        </tr>
                        <tr class="fin-row bold">
                            <td>Total TTC</td>
                            <td class="text-right">{{ $money($totalTtc) }}</td>
                        </tr>
                        <tr class="fin-row bold" style="font-size: 13px;">
                            <td style="border-bottom: 2px solid #333333;">Net a payer</td>
                            <td class="text-right" style="border-bottom: 2px solid #333333;">
                                {{ $money($remainingAmount) }}</td>
                        </tr>
                    </table>

                    <table class="w-100" style="margin-top: 15px;">
                        <tr>
                            <td class="text-right payment-info-text">
                                <strong>Date d'echeance :</strong>
                                {{ optional($facturation->created_at)->addDays(30)->format('d/m/Y') }}<br>
                                Mode de paiement :
                                {{ strtoupper((string) ($latestPayment?->payment_mode ?? 'N/A')) }}<br>
                                <span style="font-weight: bold; display: inline-block; margin-top: 10px;">Prestation
                                    medicale</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="footer-legal">
            Document genere par le module de facturation hospitaliere.<br>
            En cas de retard de paiement, des penalites peuvent etre appliquees selon la reglementation en vigueur.

            <table class="w-100" style="margin-top: 10px; font-size: 9px;">
                <tr>
                    <td class="text-left">
                        <span class="created-with">cree avec IN Fast</span>
                    </td>
                    <td class="text-right" style="color: #999999;">
                        {{ $invoiceRef }}<br>1 sur 1
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>

</html>
