<?php

namespace App\Jobs;

use App\Imports\PatientsImport;
use App\Models\PatientImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessPatientImportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;

    public int $tries = 1;

    public function __construct(public int $patientImportId) {}

    public function handle(): void
    {
        $import = PatientImport::query()->find($this->patientImportId);

        if (!$import || $import->isFinished()) {
            return;
        }

        $import->update([
            'status' => PatientImport::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        try {
            $absolutePath = Storage::disk('local')->path($import->file_path);
            $import->update(['total_rows' => max(0, $this->estimateRowCount($absolutePath) - 1)]);

            $dispatch = Excel::import(
                new PatientsImport(
                    patientImportId: $import->id,
                    hopitalId: $import->hopital_id,
                    userId: $import->user_id,
                ),
                $import->file_path,
                'local',
            );

            if ($dispatch instanceof \Illuminate\Foundation\Bus\PendingDispatch) {
                $dispatch->allOnQueue('imports');
            }
        } catch (\Throwable $exception) {
            $import->update([
                'status' => PatientImport::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    private function estimateRowCount(string $absolutePath): int
    {
        try {
            $reader = IOFactory::createReaderForFile($absolutePath);
            $reader->setReadDataOnly(true);

            if (method_exists($reader, 'listWorksheetInfo')) {
                $info = $reader->listWorksheetInfo($absolutePath);

                return (int) ($info[0]['totalRows'] ?? 0);
            }
        } catch (\Throwable) {
            // Estimation optionnelle : l'import fonctionne sans total connu.
        }

        return 0;
    }
}
