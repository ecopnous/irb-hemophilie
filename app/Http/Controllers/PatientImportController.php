<?php

namespace App\Http\Controllers;

use App\Exports\PatientImportTemplateExport;
use App\Models\PatientImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientImportController extends Controller
{
    public function template()
    {
        return Excel::download(
            new PatientImportTemplateExport,
            'exemple-import-patients.xlsx',
        );
    }

    public function errors(Request $request, PatientImport $patientImport): StreamedResponse
    {
        abort_unless(
            $patientImport->hopital_id === current_hopital_id(),
            403,
        );

        abort_unless(
            $patientImport->errors_file_path && Storage::disk('local')->exists($patientImport->errors_file_path),
            404,
        );

        return Storage::disk('local')->download(
            $patientImport->errors_file_path,
            "erreurs-import-patients-{$patientImport->id}.csv",
        );
    }
}
