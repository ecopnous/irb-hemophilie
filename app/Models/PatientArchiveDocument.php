<?php

namespace App\Models;

use App\Enums\PatientArchiveCategory;
use App\Models\Concerns\ScopesByHopital;
use App\Models\configs\Hopital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PatientArchiveDocument extends Model
{
    use ScopesByHopital;
    use SoftDeletes;

    protected $fillable = [
        'dossier_patient_id',
        'user_id',
        'hopital_id',
        'title',
        'description',
        'category',
        'source_establishment',
        'document_date',
        'original_filename',
        'path',
        'mime_type',
        'size',
        'is_confidential',
    ];

    protected $casts = [
        'category' => PatientArchiveCategory::class,
        'document_date' => 'date',
        'is_confidential' => 'boolean',
        'size' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class, 'dossier_patient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function humanSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' Mo';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' Ko';
        }

        return $bytes . ' o';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function fileIcon(): string
    {
        if ($this->isImage()) {
            return 'photo';
        }

        if ($this->isPdf()) {
            return 'document-text';
        }

        return 'document';
    }

    public function fileExtension(): string
    {
        $ext = pathinfo($this->original_filename, PATHINFO_EXTENSION);

        return $ext ? strtoupper($ext) : 'FICHIER';
    }

    public function deleteFile(): void
    {
        Storage::disk('local')->delete($this->path);
    }

    public function uploaderName(): string
    {
        return $this->user?->name ?? 'Utilisateur inconnu';
    }
}
