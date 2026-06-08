<?php

namespace App\Imports;

use App\Models\ConsultationImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;

class ConsultationsImport implements ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
{
    public int $timeout = 3600;

    public int $tries = 3;

    public function __construct(
        public int $consultationImportId,
        public int $hopitalId,
    ) {}

    public function chunkSize(): int
    {
        return 1000;
    }

    public function sheets(): array
    {
        return [
            0 => new ImportConsultationsFromSheet(
                consultationImportId: $this->consultationImportId,
                hopitalId: $this->hopitalId,
            ),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                $import = ConsultationImport::query()->find($this->consultationImportId);

                if (!$import || $import->status === ConsultationImport::STATUS_FAILED) {
                    return;
                }

                $import->update([
                    'status' => ConsultationImport::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            },
            ImportFailed::class => function (ImportFailed $event) {
                $import = ConsultationImport::query()->find($this->consultationImportId);

                if (!$import) {
                    return;
                }

                $import->update([
                    'status' => ConsultationImport::STATUS_FAILED,
                    'error_message' => $event->getException()->getMessage(),
                    'completed_at' => now(),
                ]);
            },
        ];
    }
}
