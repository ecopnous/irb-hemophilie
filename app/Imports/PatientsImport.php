<?php

namespace App\Imports;

use App\Models\PatientImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;

class PatientsImport implements ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
{
    public int $timeout = 3600;

    public int $tries = 3;

    public function __construct(
        public int $patientImportId,
        public int $hopitalId,
        public int $userId,
    ) {}

    public function chunkSize(): int
    {
        return 1000;
    }

    public function sheets(): array
    {
        return [
            0 => new ImportPatientsFromSheet(
                patientImportId: $this->patientImportId,
                hopitalId: $this->hopitalId,
                userId: $this->userId,
            ),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                $import = PatientImport::query()->find($this->patientImportId);

                if (!$import || $import->status === PatientImport::STATUS_FAILED) {
                    return;
                }

                $import->update([
                    'status' => PatientImport::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            },
            ImportFailed::class => function (ImportFailed $event) {
                $import = PatientImport::query()->find($this->patientImportId);

                if (!$import) {
                    return;
                }

                $import->update([
                    'status' => PatientImport::STATUS_FAILED,
                    'error_message' => $event->getException()->getMessage(),
                    'completed_at' => now(),
                ]);
            },
        ];
    }
}
