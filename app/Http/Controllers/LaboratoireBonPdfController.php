<?php

namespace App\Http\Controllers;

use App\Models\Laboratoire;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class LaboratoireBonPdfController extends Controller
{
    public function __invoke(int $id): Response
    {
        $labo = Laboratoire::query()
            ->with([
                'consultation.dossierPatient',
                'consultation.user',
                'consultation.departement',
                'consultation.actes' => fn($query) => $query
                    ->wherePivot('ref', 'labo')
                    ->with('service'),
                'userPreleveur',
            ])
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);

        $consultation = $labo->consultation;
        $patient = $consultation?->dossierPatient;
        $examens = $consultation?->actes ?? collect();

        $html = View::make('pdf.laboratoire-bon', [
            'labo' => $labo,
            'consultation' => $consultation,
            'patient' => $patient,
            'examens' => $examens,
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

        $fileName = sprintf('bon-laboratoire-%s.pdf', $consultation?->reference ?: $labo->id);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
