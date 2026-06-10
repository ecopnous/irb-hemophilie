<?php

namespace App\Models\facturation;

use App\Models\Configs\Hopital;
use App\Models\DossierPatient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceDocument extends Model
{
    protected $fillable = [
        'hopital_id',
        'dossier_patient_id',
        'beneficiary_type',
        'finance_client_id',
        'source_document_id',
        'document_type',
        'status',
        'number',
        'issue_date',
        'valid_until',
        'notes',
        'total_ht',
        'total_tva',
        'total_ttc',
        'currency',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function dossierPatient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class);
    }

    public function financeClient(): BelongsTo
    {
        return $this->belongsTo(FinanceClient::class);
    }

    public function beneficiaryLabel(): string
    {
        if ($this->beneficiary_type === 'client' && $this->financeClient) {
            return $this->financeClient->name;
        }

        $patient = $this->dossierPatient;

        if (! $patient) {
            return 'Non renseigne';
        }

        return trim(sprintf(
            '%s %s %s',
            strtoupper((string) $patient->nom),
            strtoupper((string) $patient->postnom),
            ucfirst((string) $patient->prenom),
        ));
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_document_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'source_document_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FinanceDocumentItem::class, 'finance_document_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
