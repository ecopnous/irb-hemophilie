<?php

namespace App\Imports;

use App\Models\PatientImport;
use App\Services\PatientImportRowMapper;
use App\Services\PatientNinGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class ImportPatientsFromSheet implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(
        public int $patientImportId,
        public int $hopitalId,
        public int $userId,
    ) {}

    public function onRow(Row $row): void
    {
        $import = PatientImport::query()->find($this->patientImportId);

        if (!$import || $import->status === PatientImport::STATUS_FAILED) {
            return;
        }

        if ($import->status === PatientImport::STATUS_PENDING) {
            $import->update([
                'status' => PatientImport::STATUS_PROCESSING,
                'started_at' => now(),
            ]);
        }

        $raw = $row->toArray();

        if ($this->isSkippableRow($raw)) {
            return;
        }

        $mapper = app(PatientImportRowMapper::class);
        $attributes = $mapper->map($raw, $this->hopitalId, $this->userId);

        $validator = Validator::make($attributes, $mapper->rules());

        if ($validator->fails()) {
            $this->recordFailure($row->getIndex(), $raw, $validator->errors()->all());
            $import->increment('failed_count');
            $import->increment('processed_rows');

            return;
        }

        try {
            app(PatientNinGenerator::class)->create($attributes);

            $import->increment('success_count');
        } catch (\Throwable $exception) {
            $this->recordFailure($row->getIndex(), $raw, [$exception->getMessage()]);
            $import->increment('failed_count');
        }

        $import->increment('processed_rows');
    }

    private function isSkippableRow(array $raw): bool
    {
        $prenom = trim((string) ($raw['prenom'] ?? ''));

        return $prenom === '';
    }

    private function recordFailure(int $rowNumber, array $raw, array $messages): void
    {
        $import = PatientImport::query()->find($this->patientImportId);

        if (!$import) {
            return;
        }

        $path = $import->errors_file_path ?? $this->initializeErrorsFile($import);

        $line = [
            'ligne' => $rowNumber,
            'erreurs' => implode(' | ', $messages),
            'prenom' => $raw['prenom'] ?? '',
            'nom' => $raw['nom'] ?? '',
            'postnom' => $raw['postnom'] ?? '',
            'genre' => $raw['genre'] ?? '',
            'telephone' => $raw['telephone'] ?? '',
            'email' => $raw['email'] ?? '',
            'ins' => $raw['ins'] ?? '',
        ];

        Storage::disk('local')->append($path, $this->csvLine($line));
    }

    private function initializeErrorsFile(PatientImport $import): string
    {
        $path = "imports/errors/import-{$import->id}.csv";

        Storage::disk('local')->put($path, $this->csvLine([
            'ligne' => 'ligne',
            'erreurs' => 'erreurs',
            'prenom' => 'prenom',
            'nom' => 'nom',
            'postnom' => 'postnom',
            'genre' => 'genre',
            'telephone' => 'telephone',
            'email' => 'email',
            'ins' => 'ins',
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
