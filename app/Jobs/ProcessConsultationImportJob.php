<?php

namespace App\Jobs;

use App\Imports\ConsultationsCsvImport;
use App\Imports\ConsultationsImport;
use App\Models\ConsultationImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessConsultationImportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;

    public int $tries = 1;

    public function __construct(public int $consultationImportId) {}

    public function handle(): void
    {
        $import = ConsultationImport::query()->find($this->consultationImportId);

        if (!$import || $import->isFinished()) {
            return;
        }

        $import->update([
            'status' => ConsultationImport::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        try {
            $absolutePath = Storage::disk('local')->path($import->file_path);
            $import->update(['total_rows' => max(0, $this->estimateRowCount($absolutePath) - 1)]);

            $dispatch = Excel::import(
                $this->makeImport($import),
                $import->file_path,
                'local',
            );

            if ($dispatch instanceof \Illuminate\Foundation\Bus\PendingDispatch) {
                $dispatch->allOnQueue('imports');
            }
        } catch (\Throwable $exception) {
            $import->update([
                'status' => ConsultationImport::STATUS_FAILED,
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
            //
        }

        return 0;
    }

    private function makeImport(ConsultationImport $import): object
    {
        $extension = strtolower(pathinfo($import->file_path, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'txt', 'tsv'], true)) {
            return new ConsultationsCsvImport(
                consultationImportId: $import->id,
                hopitalId: $import->hopital_id,
            );
        }

        return new ConsultationsImport(
            consultationImportId: $import->id,
            hopitalId: $import->hopital_id,
        );
    }
}
