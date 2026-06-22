<?php

namespace App\Http\Controllers;

use App\Models\facturation\Facturation;
use App\Services\ConsultationBillingService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class FacturationPdfController extends Controller
{
    public function __invoke(int $id, ConsultationBillingService $billing): Response
    {
        $facturation = Facturation::query()
            ->with([
                'dossierPatient',
                'payments' => fn ($query) => $query->whereNull('voided_at')->latest('paid_at'),
                'consultation.dossierPatient',
                'consultation.departement',
                'consultation.projet.assurance.categorisation',
                'consultation.assurance.categorisation',
                'consultation.user',
                'consultation.actes.departement',
                'consultation.actes.service',
            ])
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);

        $patient = $facturation->dossierPatient ?: $facturation->consultation?->dossierPatient;
        $consultation = $facturation->consultation;
        $hopital = current_hopital();

        $billingLines = $consultation
            ? $billing->billingLines($consultation)
            : collect();

        $categoryName = $consultation
            ? $billing->coverageCategoryName($consultation)
            : 'N/A';

        $grossAmount = (float) $billingLines->sum('amount');
        $assuranceAmount = (float) $billingLines->sum('assurance_amount');
        $totalHt = (float) $billingLines->sum('patient_amount');
        $tvaRate = 0.0;
        $tvaAmount = $totalHt * $tvaRate;
        $totalTtc = $totalHt + $tvaAmount;
        $paidAmount = (float) $facturation->payments->sum('amount');
        $remainingAmount = max(0, $totalTtc - $paidAmount);

        $html = View::make('pdf.facturation', [
            'facturation' => $facturation,
            'consultation' => $consultation,
            'patient' => $patient,
            'hopital' => $hopital,
            'actes' => $consultation?->actes ?? collect(),
            'billingLines' => $billingLines,
            'categoryName' => $categoryName,
            'assuranceName' => $consultation ? $billing->assuranceName($consultation) : 'Paiement direct',
            'coverageRate' => $consultation ? $billing->defaultCoverageRate($consultation) : 0.0,
            'grossAmount' => $grossAmount,
            'assuranceAmount' => $assuranceAmount,
            'totalHt' => $totalHt,
            'totalTtc' => $totalTtc,
            'tvaRate' => $tvaRate,
            'tvaAmount' => $tvaAmount,
            'paidAmount' => $paidAmount,
            'remainingAmount' => $remainingAmount,
            'latestPayment' => $facturation->payments->first(),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 96);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $dompdf->setPaper('A4');
        $dompdf->render();

        $fileName = sprintf('facture-%s.pdf', str_pad((string) $facturation->id, 6, '0', STR_PAD_LEFT));

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
