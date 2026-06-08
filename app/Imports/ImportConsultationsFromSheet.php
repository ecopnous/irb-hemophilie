<?php

namespace App\Imports;

use App\Models\ConsultationImport;
use App\Services\ConsultationImportCreator;
use App\Services\ConsultationImportRowMapper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class ImportConsultationsFromSheet implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(
        public int $consultationImportId,
        public int $hopitalId,
    ) {}

    public function onRow(Row $row): void
    {
        $import = ConsultationImport::query()->find($this->consultationImportId);

        if (!$import || $import->status === ConsultationImport::STATUS_FAILED) {
            return;
        }

        if ($import->status === ConsultationImport::STATUS_PENDING) {
            $import->update([
                'status' => ConsultationImport::STATUS_PROCESSING,
                'started_at' => now(),
            ]);
        }

        $raw = $row->toArray();

        if ($this->isSkippableRow($raw)) {
            return;
        }

        $mapper = app(ConsultationImportRowMapper::class);
        $attributes = $mapper->map($raw, $this->hopitalId);

        $validator = Validator::make($attributes, $mapper->rules());

        if ($validator->fails()) {
            $this->recordFailure($row->getIndex(), $raw, $validator->errors()->all());
            $import->increment('failed_count');
            $import->increment('processed_rows');

            return;
        }

        try {
            app(ConsultationImportCreator::class)->create($attributes);

            $import->increment('success_count');
        } catch (\Throwable $exception) {
            $this->recordFailure($row->getIndex(), $raw, [$exception->getMessage()]);
            $import->increment('failed_count');
        }

        $import->increment('processed_rows');
    }

    private function isSkippableRow(array $raw): bool
    {
        $nin = trim((string) ($raw['nin'] ?? ''));
        $ins = trim((string) ($raw['ins'] ?? ''));

        return $nin === '' && $ins === '';
    }

    private function recordFailure(int $rowNumber, array $raw, array $messages): void
    {
        $import = ConsultationImport::query()->find($this->consultationImportId);

        if (!$import) {
            return;
        }

        $path = $import->errors_file_path ?? $this->initializeErrorsFile($import);

        $line = [
            'ligne' => $rowNumber,
            'erreurs' => implode(' | ', $messages),
            'nin' => $raw['nin'] ?? '',
            'ins' => $raw['ins'] ?? '',
            'type' => $raw['type'] ?? '',
            'departement' => $raw['departement'] ?? '',
            'actes' => $raw['actes'] ?? '',
            'medecin' => $raw['medecin'] ?? '',
        ];

        Storage::disk('local')->append($path, $this->csvLine($line));
    }

    private function initializeErrorsFile(ConsultationImport $import): string
    {
        $path = "imports/errors/consultation-import-{$import->id}.csv";

        Storage::disk('local')->put($path, $this->csvLine([
            'ligne' => 'ligne',
            'erreurs' => 'erreurs',
            'nin' => 'nin',
            'ins' => 'ins',
            'type' => 'type',
            'departement' => 'departement',
            'actes' => 'actes',
            'medecin' => 'medecin',
        ]));

        $import->update(['errors_file_path' => $path]);

        return $path;
    }

    private function csvLine(array $values): string
    {
        return implode(',', array_map(function ($value) {
            $value = (string) $value;

            return '"' . str_replace('"', '""', $value) . '"';
        }, $values)) . PHP_EOL;
    }
}
