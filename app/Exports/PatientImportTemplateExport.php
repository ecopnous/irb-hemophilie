<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PatientImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PatientImportExamplesSheet,
            new PatientImportInstructionsSheet,
        ];
    }
}
