<?php

namespace App\Models\facturation;

use App\Models\Configs\Acte;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'facturation_id',
        'consultation_id',
        'dossier_patient_id',
        'acte_id',
        'amount',
        'currency',
        'payment_mode',
        'reference',
        'paid_at',
        'comment',
        'created_by',
        'updated_by',
        'voided_at',
        'void_reason',
        'voided_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function facturation(): BelongsTo
    {
        return $this->belongsTo(Facturation::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function dossierPatient(): BelongsTo
    {
        return $this->belongsTo(DossierPatient::class);
    }

    public function acte(): BelongsTo
    {
        return $this->belongsTo(Acte::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PaymentAudit::class);
    }
}
