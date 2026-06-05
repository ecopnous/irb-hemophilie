<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\DossierPatient;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class PatientDossierPdfController extends Controller
{
    public function __invoke(int $id): Response
    {
        $patient = DossierPatient::query()
            ->with(['province', 'ville', 'commune'])
            ->where('hopital_id', current_hopital_id())
            ->findOrFail($id);

        $consultations = Consultation::query()
            ->with([
                'user',
                'departement',
                'actes.departement',
            ])
            ->where('hopital_id', current_hopital_id())
            ->where('dossier_patient_id', $patient->id)
            ->latest('created_at')
            ->get();

        $latestConsultation = $consultations->first();

        $labActs = collect();
        if ($latestConsultation) {
            $labActs = $latestConsultation->actes->filter(function ($acte) {
                $departementName = strtolower((string) ($acte->departement?->name ?? ''));
                return str_contains($departementName, 'labo');
            });
        }

        $examensDemandes = $labActs->pluck('name')->filter()->values();
        if ($examensDemandes->isEmpty() && $latestConsultation) {
            $examensDemandes = $latestConsultation->actes->pluck('name')->filter()->values();
        }

        $html = View::make('pdf.patient-dossier', [
            'patient' => $patient,
            'consultations' => $consultations,
            'latestConsultation' => $latestConsultation,
            'examensDemandes' => $examensDemandes,
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

        $fileName = sprintf('dossier-patient-%s.pdf', $patient->nin ?: $patient->id);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
