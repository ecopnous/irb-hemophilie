<?php

namespace App\Http\Controllers;

use App\Exports\ConsultationImportTemplateExport;
use App\Models\ConsultationImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsultationImportController extends Controller
{
    public function template()
    {
        return Excel::download(
            new ConsultationImportTemplateExport,
            'exemple-import-consultations.xlsx',
        );
    }

    public function errors(Request $request, ConsultationImport $consultationImport): StreamedResponse
    {
        abort_unless(
            $consultationImport->hopital_id === current_hopital_id(),
            403,
        );

        abort_unless(
            $consultationImport->errors_file_path && Storage::disk('local')->exists($consultationImport->errors_file_path),
            404,
        );

        return Storage::disk('local')->download(
            $consultationImport->errors_file_path,
            "erreurs-import-consultations-{$consultationImport->id}.csv",
        );
    }
}
