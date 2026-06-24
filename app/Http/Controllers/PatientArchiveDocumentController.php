<?php

namespace App\Http\Controllers;

use App\Models\PatientArchiveDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientArchiveDocumentController extends Controller
{
    public function download(Request $request, int $id, PatientArchiveDocument $document): StreamedResponse
    {
        $document->load('patient');

        abort_unless(
            $document->dossier_patient_id === $id
            && $document->hopital_id === current_hopital_id(),
            403,
            'Acces refuse a ce document.',
        );

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download(
            $document->path,
            $document->original_filename,
        );
    }
}
