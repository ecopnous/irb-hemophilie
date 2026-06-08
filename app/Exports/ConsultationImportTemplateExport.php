<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ConsultationImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ConsultationImportExamplesSheet,
            new ConsultationImportInstructionsSheet,
        ];
    }
}
