<?php

namespace App\Livewire;

use App\Jobs\ProcessConsultationImportJob;
use App\Models\ConsultationImport;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

class ConsultationImportManager extends Component
{
    use WithFileUploads;

    public $importFile;

    public bool $showModal = false;

    public ?int $activeImportId = null;

    public bool $tableRefreshed = false;

    protected function rules(): array
    {
        return [
            'importFile' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv,txt',
                'max:51200',
            ],
        ];
    }

    public function openModal(): void
    {
        $this->resetValidation();
        $this->importFile = null;
        $this->showModal = true;

        $latest = ConsultationImport::query()
            ->where('hopital_id', current_hopital_id())
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($latest && !$latest->isFinished()) {
            $this->activeImportId = $latest->id;
        }
    }

    public function startImport(): void
    {
        $this->validate();

        $hopitalId = current_hopital_id();

        abort_unless($hopitalId, 403, 'Aucun hopital selectionne.');

        $storedPath = $this->importFile->store('imports/uploads', 'local');

        $consultationImport = ConsultationImport::query()->create([
            'user_id' => auth()->id(),
            'hopital_id' => $hopitalId,
            'original_filename' => $this->importFile->getClientOriginalName(),
            'file_path' => $storedPath,
            'status' => ConsultationImport::STATUS_PENDING,
        ]);

        $this->activeImportId = $consultationImport->id;
        $this->tableRefreshed = false;
        $this->importFile = null;

        ProcessConsultationImportJob::dispatch($consultationImport->id)->onQueue('imports');

        Flux::toast(
            variant: 'success',
            heading: 'Import lance',
            text: 'Le fichier est en cours de traitement en arriere-plan. Vous pouvez fermer cette fenetre.',
        );
    }

    public function getActiveImportProperty(): ?ConsultationImport
    {
        if (!$this->activeImportId) {
            return null;
        }

        return ConsultationImport::query()
            ->where('hopital_id', current_hopital_id())
            ->find($this->activeImportId);
    }

    public function checkImportStatus(): void
    {
        $import = $this->activeImport;

        if ($import && $import->isFinished() && !$this->tableRefreshed) {
            $this->tableRefreshed = true;
            $this->dispatch('pg:eventRefresh-consultationTable');
        }
    }

    public function render()
    {
        return view('livewire.consultation-import-manager');
    }
}
