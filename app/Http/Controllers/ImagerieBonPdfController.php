<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Imagerie;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class ImagerieBonPdfController extends Controller
{
    public function __invoke(int $id): Response
    {
        $consultation = Consultation::query()
            ->whereHopitalId(current_hopital_id())
            ->with([
                'dossierPatient',
                'user',
                'departement',
                'imagerie',
                'actes' => fn ($query) => $query->with('departement', 'service'),
            ])
            ->find($id);

        if (!$consultation) {
            $imagerie = Imagerie::query()
                ->where('hopital_id', current_hopital_id())
                ->findOrFail($id);

            $consultation = Consultation::query()
                ->whereHopitalId(current_hopital_id())
                ->with([
                    'dossierPatient',
                    'user',
                    'departement',
                    'imagerie',
                    'actes' => fn ($query) => $query->with('departement', 'service'),
                ])
                ->findOrFail($imagerie->consultation_id);
        }

        $examens = $consultation->actes
            ->filter(function ($acte) {
                $departement = $acte->departement;
                if (!$departement) {
                    return false;
                }

                $name = strtolower((string) $departement->name);
                $ref = strtolower((string) ($departement->ref ?? ''));

                return str_contains($name, 'imagerie') || $ref === 'img';
            })
            ->values();

        abort_unless($consultation->imagerie || $examens->isNotEmpty(), 404, 'Aucun bon d imagerie lie a cette consultation.');

        $html = View::make('pdf.imagerie-bon', [
            'consultation' => $consultation,
            'patient' => $consultation->dossierPatient,
            'imagerie' => $consultation->imagerie,
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

        $fileName = sprintf('bon-imagerie-%s.pdf', $consultation->reference ?: $consultation->id);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
