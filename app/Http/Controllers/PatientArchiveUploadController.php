<?php

namespace App\Http\Controllers;

use App\Models\DossierPatient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PatientArchiveUploadController extends Controller
{
    public function store(Request $request, int $id): JsonResponse
    {
        abort_unless(current_hopital_id(), 403, 'Selectionnez un hopital.');

        $patient = DossierPatient::query()
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($id);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:25600', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,tif,tiff'],
        ], [
            'file.required' => 'Veuillez selectionner un fichier.',
            'file.max' => 'Le fichier ne doit pas depasser 25 Mo.',
            'file.mimes' => 'Format non supporte. Utilisez PDF, images ou documents Office.',
        ]);

        $file = $validated['file'];
        $path = $file->store('patient-archives-staging/' . $patient->id, 'local');

        if (! $path || ! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages([
                'file' => 'Impossible d enregistrer le fichier. Veuillez reessayer.',
            ]);
        }

        return response()->json([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
        ]);
    }
}
