<?php

namespace App\Imports;

use App\Models\ConsultationImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;

class ConsultationsCsvImport extends ImportConsultationsFromSheet implements ShouldQueue, WithChunkReading, WithEvents
{
    public int $timeout = 3600;

    public int $tries = 3;

    public function chunkSize(): int
    {
        return 1000;
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
