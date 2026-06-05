<?php

namespace App\Http\Controllers;

use App\Models\facturation\Facturation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class FacturationPdfController extends Controller
{
    public function __invoke(int $id): Response
    {
        $facturation = Facturation::query()
            ->with([
                'dossierPatient',
                'payments' => fn ($query) => $query->whereNull('voided_at')->latest('paid_at'),
                'consultation.dossierPatient',
                'consultation.departement',
                'consultation.assurance',
                'consultation.projet',
                'consultation.user',
                'consultation.actes.departement',
                'consultation.actes.service',
            ])
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);

        $patient = $facturation->dossierPatient ?: $facturation->consultation?->dossierPatient;
        $consultation = $facturation->consultation;
        $hopital = current_hopital();

        $actes = $consultation?->actes ?? collect();
        $totalHt = (float) $actes->sum(fn ($acte) => (float) ($acte->pivot->montant ?? 0));
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
            'actes' => $actes,
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
